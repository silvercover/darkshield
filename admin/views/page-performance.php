<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'server';
$latest_url   = get_transient( 'darkshield_front_perf_latest_url' );
$latest_key   = get_transient( 'darkshield_front_perf_latest' );
$has_frontend = ! empty( $latest_key ) && get_transient( $latest_key );
?>

<?php
$darkshield_page_title    = __( 'Performance Analyzer', 'darkshield' );
$darkshield_page_subtitle = __( 'Measure real load times across your server, frontend, and admin.', 'darkshield' );
?>
<div class="wrap darkshield">
	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-page-header.php'; ?>

	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div>

		<!-- Sub Tabs -->
		<nav class="darkshield-nav" aria-label="<?php esc_attr_e( 'Performance views', 'darkshield' ); ?>">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-performance&tab=server' ) ); ?>"
				class="darkshield-nav-item <?php echo 'server' === $tab ? 'is-active' : ''; ?>">
				<span class="darkshield-nav-icon" aria-hidden="true">📡</span>
				<span class="darkshield-nav-label"><?php esc_html_e( 'Server-Side', 'darkshield' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-performance&tab=frontend' ) ); ?>"
				class="darkshield-nav-item <?php echo 'frontend' === $tab ? 'is-active' : ''; ?>"
				style="position:relative;">
				<span class="darkshield-nav-icon" aria-hidden="true">🌐</span>
				<span class="darkshield-nav-label"><?php esc_html_e( 'Frontend (Client)', 'darkshield' ); ?></span>
				<?php if ( $has_frontend ) : ?>
					<span style="position:absolute;top:-4px;right:-4px;background:#16a34a;color:#fff;border-radius:50%;width:10px;height:10px;font-size:7px;line-height:10px;text-align:center;">✓</span>
				<?php endif; ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-performance&tab=admin' ) ); ?>"
				class="darkshield-nav-item <?php echo 'admin' === $tab ? 'is-active' : ''; ?>">
				<span class="darkshield-nav-icon" aria-hidden="true">⚙️</span>
				<span class="darkshield-nav-label"><?php esc_html_e( 'Admin Page', 'darkshield' ); ?></span>
			</a>
		</nav>

		<?php if ( 'server' === $tab ) : ?>

			<!-- Server-Side Analysis -->
			<div class="card">
				<h2>📡 <?php esc_html_e( 'Server-Side Analysis', 'darkshield' ); ?></h2>
				<p><?php esc_html_e( 'Fetches page HTML from server, extracts resources, pings each one.', 'darkshield' ); ?></p>
				<div style="display:flex;gap:10px;align-items:center;margin-top:15px;">
					<input type="url" id="darkshield-perf-url" value="<?php echo esc_attr( home_url( '/' ) ); ?>" class="regular-text" />
					<button type="button" id="darkshield-perf-start" class="button button-primary"><?php esc_html_e( 'Analyze Page', 'darkshield' ); ?></button>
				</div>
				<span id="darkshield-perf-status" style="display:block;margin-top:8px;font-size:13px;"></span>
			</div>
			<div id="darkshield-perf-results"></div>

		<?php elseif ( 'frontend' === $tab ) : ?>

			<!-- Frontend Client-Side Results -->
			<div class="card">
				<h2>🌐 <?php esc_html_e( 'Frontend Performance Data', 'darkshield' ); ?></h2>
				<p><?php esc_html_e( 'Real browser timing data collected from your site\'s frontend pages.', 'darkshield' ); ?></p>

				<div class="darkshield-notice darkshield-notice-info" style="margin-top:15px;">
					<h3 style="margin-top:0;">📋 <?php esc_html_e( 'How to collect data:', 'darkshield' ); ?></h3>
					<ol style="margin:10px 0 0 20px;line-height:2;">
						<li><?php esc_html_e( 'Go to any frontend page of your site (homepage, product page, etc.)', 'darkshield' ); ?></li>
						<li><?php esc_html_e( 'Wait for the page to fully load', 'darkshield' ); ?></li>
						<li><?php esc_html_e( 'In the admin bar, click: DarkShield → 🔍 Analyze This Page', 'darkshield' ); ?></li>
						<li><?php esc_html_e( 'A notification will appear with a link to view results here', 'darkshield' ); ?></li>
					</ol>
				</div>

				<?php if ( $has_frontend ) : ?>
					<div class="darkshield-notice darkshield-notice-success" style="margin-top:15px;">
						<strong>✅ <?php esc_html_e( 'Data available!', 'darkshield' ); ?></strong>
						<?php if ( $latest_url ) : ?>
							<br><span style="color:#666;font-size:13px;">
								<?php
								/* translators: %s: URL of the analyzed page */
								printf( esc_html__( 'Page: %s', 'darkshield' ), '<code>' . esc_html( $latest_url ) . '</code>' );
								?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div id="darkshield-frontend-results">
				<?php if ( $has_frontend ) : ?>
					<p style="color:#666;"><span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span><?php esc_html_e( 'Loading results...', 'darkshield' ); ?></p>
				<?php else : ?>
					<div class="card" style="padding:20px;">
						<p><?php esc_html_e( 'No frontend data yet. Visit a page and click "Analyze This Page" in the admin bar.', 'darkshield' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

		<?php elseif ( 'admin' === $tab ) : ?>

			<!-- Admin Page Client-Side -->
			<div class="card">
				<h2>⚙️ <?php esc_html_e( 'Admin Page Analysis', 'darkshield' ); ?></h2>
				<p><?php esc_html_e( 'Collect Resource Timing data for this admin page.', 'darkshield' ); ?></p>
				<button type="button" id="darkshield-perf-client" class="button button-primary"><?php esc_html_e( 'Collect Browser Data', 'darkshield' ); ?></button>
			</div>
			<div id="darkshield-perf-client-results"></div>

		<?php endif; ?>

	</div>
</div>