<?php
/**
 * Admin display partial for the Equalify URL Feed settings page.
 *
 * Variables available from the calling method:
 *   $feed_url      (string)  — public CSV feed URL
 *   $include_pdfs  (bool)    — whether PDF media library files are included
 *   $all_urls      (array)   — all site URLs with 'url' and 'type' keys
 *   $disabled_urls (array)   — URLs currently excluded from the feed
 *   $page_urls     (array)   — subset of $all_urls for the current page
 *   $current_page  (int)
 *   $total_pages   (int)
 *   $total         (int)     — total number of URLs
 *   $per_page      (int)
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/admin/partials
 */
?>
<div class="wrap" id="equalify-url-feed-page">

	<h1><?php esc_html_e( 'Equalify URL Feed', 'equalify-wp-integration' ); ?></h1>

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
		$start = ( $current_page - 1 ) * $per_page + 1;
		$end   = min( $current_page * $per_page, $total );
		printf(
			/* translators: 1: start, 2: end, 3: total */
			esc_html__( 'URLs in Feed (%1$d–%2$d of %3$d)', 'equalify-wp-integration' ),
			$start,
			$end,
			$total
		);
		?>
	</h2>

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
				$base_url = add_query_arg( 'page', 'equalify-url-feed', admin_url( 'options-general.php' ) );

				// Previous
				if ( $current_page > 1 ) {
					printf(
						'<a class="button" href="%s">&laquo; %s</a> ',
						esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ),
						esc_html__( 'Previous', 'equalify-wp-integration' )
					);
				}

				// Page numbers (show up to 10 around current page)
				$range = 4;
				$p_start = max( 1, $current_page - $range );
				$p_end   = min( $total_pages, $current_page + $range );

				for ( $p = $p_start; $p <= $p_end; $p++ ) {
					if ( $p === $current_page ) {
						printf( '<span class="button button-primary" style="cursor:default;">%d</span> ', $p );
					} else {
						printf(
							'<a class="button" href="%s">%d</a> ',
							esc_url( add_query_arg( 'paged', $p, $base_url ) ),
							$p
						);
					}
				}

				// Next
				if ( $current_page < $total_pages ) {
					printf(
						'<a class="button" href="%s">%s &raquo;</a>',
						esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ),
						esc_html__( 'Next', 'equalify-wp-integration' )
					);
				}
				?>
			</div>
		</div>
		<?php endif; ?>

	<?php endif; ?>
</div>
