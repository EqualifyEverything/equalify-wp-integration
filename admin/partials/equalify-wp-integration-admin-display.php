<?php
/**
 * Admin display partial for the Equalify Integration settings page.
 *
 * Variables available from the calling method:
 *   $logo_url      (string)  — absolute URL to logo.svg
 *   $feed_url      (string)  — public CSV feed URL
 *   $include_pdfs  (bool)    — whether PDF media library files are included
 *   $all_urls      (array)   — all site URLs with 'url' and 'type' keys
 *   $disabled_urls (array)   — URLs currently excluded from the feed
 *   $page_urls     (array)   — subset of $all_urls for the current page
 *   $search        (string)  — current search query (empty string if none)
 *   $current_page  (int)
 *   $total_pages   (int)
 *   $total         (int)     — number of URLs after search filtering
 *   $total_all     (int)     — unfiltered total number of URLs in the feed
 *   $per_page      (int)
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/admin/partials
 */
?>
<div class="wrap" id="equalify-integration-page">

	<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Equalify', 'equalify-wp-integration' ); ?>" style="max-width:220px;display:block;margin:12px 0 4px;" />
	<h1 class="screen-reader-text"><?php esc_html_e( 'Equalify Integration', 'equalify-wp-integration' ); ?></h1>

	<!-- CSV Feed URL -->
	<div class="equalify-feed-url-box card" style="max-width:800px;padding:16px 20px;margin-top:16px;">
		<h2 style="margin-top:0;"><?php esc_html_e( 'CSV Feed URL', 'equalify-wp-integration' ); ?></h2>
		<p><?php esc_html_e( 'Use this URL in Equalify to keep your accessibility audits in sync with your latest content.', 'equalify-wp-integration' ); ?></p>
		<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
			<input
				id="equalify-feed-url-input"
				type="text"
				readonly
				value="<?php echo esc_url( $feed_url ); ?>"
				style="flex:1;min-width:300px;font-family:monospace;"
				class="regular-text"
			/>
			<button
				type="button"
				id="equalify-copy-url-btn"
				class="button button-primary"
				data-url="<?php echo esc_url( $feed_url ); ?>"
			>
				<?php esc_html_e( 'Copy to Clipboard', 'equalify-wp-integration' ); ?>
			</button>
			<span id="equalify-copy-notice" style="display:none;color:#3c763d;font-weight:600;">
				<?php esc_html_e( 'Copied!', 'equalify-wp-integration' ); ?>
			</span>
		</div>
	</div>

	<!-- Options -->
	<div class="card" style="max-width:800px;padding:16px 20px;margin-top:20px;">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Options', 'equalify-wp-integration' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'equalify_save_options' ); ?>
			<input type="hidden" name="action" value="equalify_save_options" />
			<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
				<input
					type="checkbox"
					name="include_pdfs"
					value="1"
					<?php checked( $include_pdfs ); ?>
				/>
				<?php esc_html_e( 'Include direct file URLs of PDF files in the media library', 'equalify-wp-integration' ); ?>
			</label>
			<p style="margin-bottom:0;">
				<?php submit_button( __( 'Save Options', 'equalify-wp-integration' ), 'primary', 'submit', false ); ?>
			</p>
		</form>
	</div>

	<!-- URL Table -->
	<h2 style="margin-top:28px;">
		<?php
		$start = $total > 0 ? ( $current_page - 1 ) * $per_page + 1 : 0;
		$end   = min( $current_page * $per_page, $total );
		if ( $search !== '' ) {
			printf(
				/* translators: 1: start, 2: end, 3: filtered total, 4: search query, 5: unfiltered total */
				esc_html__( 'URLs in Feed (%1$d–%2$d of %3$d matching "%4$s", %5$d total)', 'equalify-wp-integration' ),
				$start, $end, $total, esc_html( $search ), $total_all
			);
		} else {
			printf(
				/* translators: 1: start, 2: end, 3: total */
				esc_html__( 'URLs in Feed (%1$d–%2$d of %3$d)', 'equalify-wp-integration' ),
				$start, $end, $total
			);
		}
		?>
	</h2>

	<!-- Search -->
	<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" style="margin-bottom:12px;">
		<input type="hidden" name="page" value="equalify-integration" />
		<div style="display:flex;gap:6px;align-items:center;max-width:900px;">
			<input
				type="search"
				name="s"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Search URLs…', 'equalify-wp-integration' ); ?>"
				class="regular-text"
				style="flex:1;"
			/>
			<?php submit_button( __( 'Search', 'equalify-wp-integration' ), 'secondary', '', false ); ?>
			<?php if ( $search !== '' ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'page', 'equalify-integration', admin_url( 'options-general.php' ) ) ); ?>" class="button">
					<?php esc_html_e( 'Clear', 'equalify-wp-integration' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</form>

	<?php if ( empty( $all_urls ) ) : ?>
		<p><?php esc_html_e( 'No published URLs found on this site.', 'equalify-wp-integration' ); ?></p>
	<?php else : ?>

		<table class="wp-list-table widefat fixed striped" style="max-width:900px;">
			<thead>
				<tr>
					<th scope="col" style="width:60%;"><?php esc_html_e( 'URL', 'equalify-wp-integration' ); ?></th>
					<th scope="col" style="width:10%;"><?php esc_html_e( 'Type', 'equalify-wp-integration' ); ?></th>
					<th scope="col" style="width:15%;"><?php esc_html_e( 'Status', 'equalify-wp-integration' ); ?></th>
					<th scope="col" style="width:15%;"><?php esc_html_e( 'Action', 'equalify-wp-integration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $page_urls as $item ) :
					$is_disabled = in_array( $item['url'], $disabled_urls, true );
				?>
				<tr>
					<td style="word-break:break-all;">
						<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $item['url'] ); ?>
						</a>
					</td>
					<td><code><?php echo esc_html( $item['type'] ); ?></code></td>
					<td>
						<?php if ( $is_disabled ) : ?>
							<span style="color:#a00;"><?php esc_html_e( 'Disabled', 'equalify-wp-integration' ); ?></span>
						<?php else : ?>
							<span style="color:#3c763d;"><?php esc_html_e( 'Enabled', 'equalify-wp-integration' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'equalify_toggle_url' ); ?>
							<input type="hidden" name="action" value="equalify_toggle_url" />
							<input type="hidden" name="url" value="<?php echo esc_attr( $item['url'] ); ?>" />
							<input type="hidden" name="toggle_action" value="<?php echo $is_disabled ? 'enable' : 'disable'; ?>" />
							<input type="hidden" name="paged" value="<?php echo esc_attr( $current_page ); ?>" />
							<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
							<button type="submit" class="button button-small">
								<?php echo $is_disabled
									? esc_html__( 'Enable', 'equalify-wp-integration' )
									: esc_html__( 'Disable', 'equalify-wp-integration' ); ?>
							</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav" style="max-width:900px;">
			<div class="tablenav-pages" style="float:none;margin-top:8px;">
				<?php
				$base_args = [ 'page' => 'equalify-integration' ];
				if ( $search !== '' ) {
					$base_args['s'] = $search;
				}
				$base_url = add_query_arg( $base_args, admin_url( 'options-general.php' ) );

				$paged_url = fn( $p ) => esc_url( add_query_arg( 'paged', $p, $base_url ) );

				// First
				if ( $current_page > 1 ) {
					printf( '<a class="button" href="%s">&laquo;&laquo; %s</a> ', $paged_url( 1 ), esc_html__( 'First', 'equalify-wp-integration' ) );
				} else {
					printf( '<span class="button" disabled aria-disabled="true">&laquo;&laquo; %s</span> ', esc_html__( 'First', 'equalify-wp-integration' ) );
				}

				// Previous
				if ( $current_page > 1 ) {
					printf( '<a class="button" href="%s">&laquo; %s</a> ', $paged_url( $current_page - 1 ), esc_html__( 'Previous', 'equalify-wp-integration' ) );
				} else {
					printf( '<span class="button" disabled aria-disabled="true">&laquo; %s</span> ', esc_html__( 'Previous', 'equalify-wp-integration' ) );
				}

				// Page numbers (up to 9 around current page)
				$range   = 4;
				$p_start = max( 1, $current_page - $range );
				$p_end   = min( $total_pages, $current_page + $range );

				for ( $p = $p_start; $p <= $p_end; $p++ ) {
					if ( $p === $current_page ) {
						printf( '<span class="button button-primary" style="cursor:default;">%d</span> ', $p );
					} else {
						printf( '<a class="button" href="%s">%d</a> ', $paged_url( $p ), $p );
					}
				}

				// Next
				if ( $current_page < $total_pages ) {
					printf( '<a class="button" href="%s">%s &raquo;</a> ', $paged_url( $current_page + 1 ), esc_html__( 'Next', 'equalify-wp-integration' ) );
				} else {
					printf( '<span class="button" disabled aria-disabled="true">%s &raquo;</span> ', esc_html__( 'Next', 'equalify-wp-integration' ) );
				}

				// Last
				if ( $current_page < $total_pages ) {
					printf( '<a class="button" href="%s">%s &raquo;&raquo;</a>', $paged_url( $total_pages ), esc_html__( 'Last', 'equalify-wp-integration' ) );
				} else {
					printf( '<span class="button" disabled aria-disabled="true">%s &raquo;&raquo;</span>', esc_html__( 'Last', 'equalify-wp-integration' ) );
				}
				?>
			</div>
		</div>
		<?php endif; ?>

	<?php endif; ?>
</div>
