<?php

/**
 * Shared URL-fetching logic used by both the public feed and admin page.
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/includes
 */

class Equalify_Wp_Integration_URLs {

	/**
	 * Returns an array of all site URLs with their type (html|pdf).
	 *
	 * Each entry is an associative array with keys 'url' and 'type'.
	 *
	 * @param    bool    $include_pdfs    Whether to include PDF files from the media library.
	 * @return   array
	 */
	public static function get_all( $include_pdfs = 1 ) {
		$urls = [];

		// All published posts, pages, and public custom post types.
		$post_types = array_values(
			array_diff( get_post_types( [ 'public' => true ] ), [ 'attachment' ] )
		);

		$posts = get_posts( [
			'post_type'   => $post_types,
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'post_type',
			'order'       => 'ASC',
		] );

		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post->ID );
			if ( $permalink ) {
				$urls[] = [
					'url'  => $permalink,
					'type' => 'html',
				];
			}
		}

		// PDF attachments — only included when the option is enabled.
		if ( $include_pdfs ) {
			$pdf_posts = get_posts( [
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'application/pdf',
				'numberposts'    => -1,
			] );

			foreach ( $pdf_posts as $post ) {
				$file_url = wp_get_attachment_url( $post->ID );
				if ( $file_url ) {
					$urls[] = [
						'url'  => $file_url,
						'type' => 'pdf',
					];
				}
			}
		}

		return $urls;
	}
}
