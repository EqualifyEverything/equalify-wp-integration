<?php

/**
 * Shared URL-fetching logic used by both the public feed and admin page.
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/includes
 */

class Equalify_Wp_Integration_URLs {

	// -------------------------------------------------------------------------
	// Cache helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns true only when a real external object cache is active.
	 * The default WordPress DB-backed cache is not reliable enough to cache
	 * query results, so we skip it entirely on environments without one.
	 */
	private static function using_cache(): bool {
		return (bool) wp_using_ext_object_cache();
	}

	/**
	 * Returns the current cache generation counter for this site, initialising
	 * it to 1 if absent. All data cache keys embed this value, so a single
	 * wp_cache_incr (see flush_cache) instantly invalidates every cached entry
	 * without needing to track individual keys.
	 *
	 * The key is scoped to the current blog ID so each subsite in a multisite
	 * network maintains its own independent generation.
	 */
	private static function cache_gen(): int {
		$key = 'equalify_urls_' . get_current_blog_id();
		$gen = wp_cache_get( $key, 'equalify' );
		if ( false === $gen ) {
			wp_cache_add( $key, 1, 'equalify' );
			return 1;
		}
		return (int) $gen;
	}

	/**
	 * Bumps the generation counter, effectively invalidating all cached URL data
	 * for the current site. Called whenever posts change or the include_pdfs
	 * option changes.
	 */
	public static function flush_cache(): void {
		if ( self::using_cache() ) {
			wp_cache_incr( 'equalify_urls_' . get_current_blog_id(), 1, 'equalify' );
		}
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Returns one page of URLs for the admin table, plus counts for the heading and pagination.
	 * Uses DB-level pagination to avoid loading all posts into memory.
	 *
	 * Posts and PDFs are treated as a single ordered list (HTML first, PDFs after).
	 * The offset math splits the page window across the two query types as needed.
	 *
	 * @param  bool   $include_pdfs
	 * @param  int    $per_page
	 * @param  int    $current_page  1-based, unclamped (out-of-range returns empty urls with correct totals).
	 * @param  string $search        URL substring to filter by. HTML posts match on post_name; PDFs match on guid.
	 * @return array { urls: array, total: int, total_all: int }
	 */
	public static function get_paged( $include_pdfs, $per_page, $current_page, $search = '' ) {
		if ( self::using_cache() ) {
			$cache_key    = 'paged_' . self::cache_gen() . '_' . md5( serialize( [ $include_pdfs, $per_page, $current_page, $search ] ) );
			$cached       = wp_cache_get( $cache_key, 'equalify' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$post_types = self::html_post_types();

		$post_total = self::count_html( $post_types, $search );
		$pdf_total  = $include_pdfs ? self::count_pdfs( $search ) : 0;
		$total      = $post_total + $pdf_total;
		$total_all  = ( $search !== '' )
			? self::count_html( $post_types, '' ) + ( $include_pdfs ? self::count_pdfs( '' ) : 0 )
			: $total;

		$offset     = ( $current_page - 1 ) * $per_page;
		$remaining  = $per_page;
		$pdf_offset = 0;
		$urls       = [];

		// Fetch from HTML posts if the window overlaps the post range.
		if ( $offset < $post_total && $remaining > 0 ) {
			$to_fetch   = min( $remaining, $post_total - $offset );
			$urls       = self::fetch_html( $post_types, $offset, $to_fetch, $search );
			$remaining -= count( $urls );
			// $pdf_offset stays 0: we start PDFs from the beginning on this page.
		} elseif ( $offset >= $post_total ) {
			// Window is entirely within the PDF range.
			$pdf_offset = $offset - $post_total;
		}

		// Fetch PDFs to fill the rest of the page.
		if ( $include_pdfs && $remaining > 0 && $pdf_total > $pdf_offset ) {
			$urls = array_merge( $urls, self::fetch_pdfs( $pdf_offset, $remaining, $search ) );
		}

		$result = compact( 'urls', 'total', 'total_all' );

		if ( self::using_cache() ) {
			wp_cache_set( $cache_key, $result, 'equalify', 300 );
		}

		return $result;
	}

	/**
	 * Iterates all URLs in chunks and calls $callback for each one.
	 * Keeps memory usage flat regardless of site size.
	 * For use in the CSV endpoint.
	 *
	 * @param  bool     $include_pdfs
	 * @param  callable $callback  Receives each ['url' => ..., 'type' => ...] item.
	 */
	public static function stream_all( $include_pdfs, callable $callback ) {
		$post_types = self::html_post_types();
		$chunk_size = 200;

		$use_cache = self::using_cache();
		$gen       = $use_cache ? self::cache_gen() : 0;

		// Stream HTML posts.
		$page = 1;
		do {
			$chunk_key = 'stream_html_' . $gen . '_' . $page;
			$chunk     = $use_cache ? wp_cache_get( $chunk_key, 'equalify' ) : false;

			if ( false === $chunk ) {
				$query = new WP_Query( [
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => $chunk_size,
					'paged'          => $page,
					'no_found_rows'  => true,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				] );

				$chunk = [];
				foreach ( $query->posts as $post ) {
					$permalink = get_permalink( $post->ID );
					if ( $permalink ) {
						$chunk[] = [ 'post_id' => $post->ID, 'url' => $permalink, 'type' => 'html' ];
					}
				}
				wp_reset_postdata();

				if ( $use_cache ) {
					wp_cache_set( $chunk_key, $chunk, 'equalify', 300 );
				}
			}

			foreach ( $chunk as $item ) {
				$callback( $item );
			}

			$page++;
		} while ( count( $chunk ) === $chunk_size );

		if ( ! $include_pdfs ) {
			return;
		}

		// Stream PDF attachments.
		$page = 1;
		do {
			$chunk_key = 'stream_pdf_' . $gen . '_' . $page;
			$chunk     = $use_cache ? wp_cache_get( $chunk_key, 'equalify' ) : false;

			if ( false === $chunk ) {
				$query = new WP_Query( [
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'post_mime_type' => 'application/pdf',
					'posts_per_page' => $chunk_size,
					'paged'          => $page,
					'no_found_rows'  => true,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				] );

				$chunk = [];
				foreach ( $query->posts as $post ) {
					$file_url = wp_get_attachment_url( $post->ID );
					if ( $file_url ) {
						$chunk[] = [ 'post_id' => $post->ID, 'url' => $file_url, 'type' => 'pdf' ];
					}
				}
				wp_reset_postdata();

				if ( $use_cache ) {
					wp_cache_set( $chunk_key, $chunk, 'equalify', 300 );
				}
			}

			foreach ( $chunk as $item ) {
				$callback( $item );
			}

			$page++;
		} while ( count( $chunk ) === $chunk_size );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private static function html_post_types() {
		return array_values(
			array_diff( get_post_types( [ 'public' => true ] ), [ 'attachment' ] )
		);
	}

	/**
	 * Counts published HTML posts, optionally filtered by post_name LIKE search.
	 */
	private static function count_html( $post_types, $search ) {
		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		];

		$filter = null;
		if ( $search !== '' ) {
			$args['_equalify_search'] = $search;
			$filter = self::make_name_like_filter();
			add_filter( 'posts_where', $filter, 10, 2 );
		}

		$q = new WP_Query( $args );

		if ( $filter ) {
			remove_filter( 'posts_where', $filter, 10 );
		}

		return (int) $q->found_posts;
	}

	/**
	 * Counts PDF attachments, optionally filtered by guid LIKE search.
	 */
	private static function count_pdfs( $search ) {
		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'application/pdf',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		];

		$filter = null;
		if ( $search !== '' ) {
			$args['_equalify_search'] = $search;
			$filter = self::make_guid_like_filter();
			add_filter( 'posts_where', $filter, 10, 2 );
		}

		$q = new WP_Query( $args );

		if ( $filter ) {
			remove_filter( 'posts_where', $filter, 10 );
		}

		return (int) $q->found_posts;
	}

	/**
	 * Fetches a slice of published HTML posts by raw DB offset.
	 */
	private static function fetch_html( $post_types, $offset, $limit, $search ) {
		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'no_found_rows'  => true,
			'orderby'        => 'post_type',
			'order'          => 'ASC',
		];

		$filter = null;
		if ( $search !== '' ) {
			$args['_equalify_search'] = $search;
			$filter = self::make_name_like_filter();
			add_filter( 'posts_where', $filter, 10, 2 );
		}

		$query = new WP_Query( $args );

		if ( $filter ) {
			remove_filter( 'posts_where', $filter, 10 );
		}

		$urls = [];
		foreach ( $query->posts as $post ) {
			$permalink = get_permalink( $post->ID );
			if ( $permalink ) {
				$urls[] = [ 'post_id' => $post->ID, 'url' => $permalink, 'type' => 'html' ];
			}
		}

		wp_reset_postdata();
		return $urls;
	}

	/**
	 * Fetches a slice of PDF attachments by raw DB offset.
	 */
	private static function fetch_pdfs( $offset, $limit, $search ) {
		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'application/pdf',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		];

		$filter = null;
		if ( $search !== '' ) {
			$args['_equalify_search'] = $search;
			$filter = self::make_guid_like_filter();
			add_filter( 'posts_where', $filter, 10, 2 );
		}

		$query = new WP_Query( $args );

		if ( $filter ) {
			remove_filter( 'posts_where', $filter, 10 );
		}

		$urls = [];
		foreach ( $query->posts as $post ) {
			$file_url = wp_get_attachment_url( $post->ID );
			if ( $file_url ) {
				$urls[] = [ 'post_id' => $post->ID, 'url' => $file_url, 'type' => 'pdf' ];
			}
		}

		wp_reset_postdata();
		return $urls;
	}

	/**
	 * Returns a posts_where closure that constrains results to post_name LIKE %search%.
	 * Reads the search term from the '_equalify_search' WP_Query arg.
	 */
	private static function make_name_like_filter() {
		return function ( $where, $query ) {
			$s = $query->get( '_equalify_search' );
			if ( $s ) {
				global $wpdb;
				$where .= $wpdb->prepare(
					" AND {$wpdb->posts}.post_name LIKE %s",
					'%' . $wpdb->esc_like( $s ) . '%'
				);
			}
			return $where;
		};
	}

	/**
	 * Returns a posts_where closure that constrains results to guid LIKE %search%.
	 * Reads the search term from the '_equalify_search' WP_Query arg.
	 */
	private static function make_guid_like_filter() {
		return function ( $where, $query ) {
			$s = $query->get( '_equalify_search' );
			if ( $s ) {
				global $wpdb;
				$where .= $wpdb->prepare(
					" AND {$wpdb->posts}.guid LIKE %s",
					'%' . $wpdb->esc_like( $s ) . '%'
				);
			}
			return $where;
		};
	}
}
