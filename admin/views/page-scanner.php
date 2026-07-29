<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$darkshield_page_title    = __( 'Scanner', 'darkshield' );
$darkshield_page_subtitle = __( 'Find external URLs hiding in your theme, plugins, and database.', 'darkshield' );
?>
<div class="wrap darkshield">
	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-page-header.php'; ?>

	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div>

		<div class="card">
			<h2><?php esc_html_e( 'Scan for External URLs', 'darkshield' ); ?></h2>
			<p><?php esc_html_e( 'Scan theme, plugin, and core files or database for external URLs.', 'darkshield' ); ?></p>

			<p>
				<button type="button" id="darkshield-start-scan" class="button button-primary"><?php esc_html_e( 'Scan Files', 'darkshield' ); ?></button>
				<button type="button" id="darkshield-scan-db" class="button"><?php esc_html_e( 'Scan Database', 'darkshield' ); ?></button>
				<button type="button" id="darkshield-clear-scan" class="button" style="color:#a00;"><?php esc_html_e( 'Clear Results', 'darkshield' ); ?></button>
				<span id="darkshield-scan-status" style="margin-left:10px;"></span>
			</p>

			<p id="darkshield-scan-controls" style="display:none;margin-top:10px;">
				<button type="button" id="darkshield-pause-scan" class="button" style="min-width:100px;"><?php esc_html_e( 'Pause', 'darkshield' ); ?></button>
				<button type="button" id="darkshield-stop-scan" class="button" style="color:#a00;min-width:100px;"><?php esc_html_e( 'Stop', 'darkshield' ); ?></button>
				<span style="margin-left:10px;color:#666;font-size:13px;"><?php esc_html_e( 'Stopping keeps results found so far.', 'darkshield' ); ?></span>
			</p>

			<div id="darkshield-scan-progress" style="display:none;margin-top:15px;">
				<div class="darkshield-progress-wrap">
					<div id="darkshield-progress-bar" class="darkshield-progress-bar">0%</div>
				</div>
				<p id="darkshield-progress-text" style="margin:5px 0 0;font-size:12px;color:#666;"></p>
			</div>
		</div>

		<div id="darkshield-scan-results">
			<?php
			$scanner = new DarkShield_Scanner();
			echo wp_kses_post( $scanner->get_results_html() );
			?>
		</div>

	</div>
</div>
