<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mode     = DarkShield_Utils::get_mode();
$settings = DarkShield_Utils::get_settings();

// Handle mode switch
if (
	isset( $_POST['darkshield_switch_mode'] ) &&
	isset( $_POST['_wpnonce'] ) &&
	wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'darkshield_switch_mode' )
) {
	$new_mode = sanitize_text_field( wp_unslash( $_POST['darkshield_switch_mode'] ) );
	DarkShield_Utils::set_mode( $new_mode );
	$mode = $new_mode;
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Mode updated.', 'darkshield' ) . '</p></div>';
}

$mode_info = array(
	'normal'   => array( '🟢', '#16a34a', __( 'Normal', 'darkshield' ), __( 'All requests allowed', 'darkshield' ) ),
	'national' => array( '🟡', '#d97706', __( 'National', 'darkshield' ), __( 'Only Iranian domains allowed', 'darkshield' ) ),
	'offline'  => array( '🔴', '#dc2626', __( 'Offline', 'darkshield' ), __( 'All external requests blocked', 'darkshield' ) ),
);
$mi        = isset( $mode_info[ $mode ] ) ? $mode_info[ $mode ] : $mode_info['normal'];

// Stats
global $wpdb;
$log_table  = $wpdb->prefix . 'darkshield_log';
$scan_table = $wpdb->prefix . 'darkshield_scan_results';

$log_total   = DarkShield_Utils::table_exists( $log_table ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table}" ) : 0;
$log_blocked = DarkShield_Utils::table_exists( $log_table ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE blocked = 1" ) : 0;
$scan_total  = DarkShield_Utils::table_exists( $scan_table ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$scan_table}" ) : 0;
$last_scan   = get_option( 'darkshield_last_scan', '' );
$wl_count    = count( get_option( 'darkshield_whitelist', array() ) );
$svc_count   = count( DarkShield_Utils::get_allowed_services() );

$rules_table = $wpdb->prefix . 'darkshield_rules';
$rules_exist = DarkShield_Utils::table_exists( $rules_table );
$allow_rules = $rules_exist ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rules_table} WHERE enabled = 1 AND action = 'allow'" ) : 0;
$deny_rules  = $rules_exist ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rules_table} WHERE enabled = 1 AND action = 'deny'" ) : 0;

$log_blocked_7d = DarkShield_Utils::table_exists( $log_table )
	? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE blocked = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" )
	: 0;

$top_domains = array();
if ( DarkShield_Utils::table_exists( $log_table ) ) {
	$top_domains = $wpdb->get_results(
		"SELECT domain, COUNT(*) as cnt FROM {$log_table} WHERE blocked = 1 GROUP BY domain ORDER BY cnt DESC LIMIT 5"
	);
}

$darkshield_trend_data = array();
if ( DarkShield_Utils::table_exists( $log_table ) ) {
	$rows = $wpdb->get_results(
		"SELECT DATE(created_at) as d, COUNT(*) as total, SUM(blocked) as blocked FROM {$log_table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY d ASC"
	);
	foreach ( $rows as $row ) {
		$darkshield_trend_data[] = array(
			'd'       => $row->d,
			'total'   => (int) $row->total,
			'blocked' => (int) $row->blocked,
		);
	}
}

/**
 * Format date — Shamsi if wp-parsidate or wp-jalali active.
 */
function darkshield_format_date( $date_string ) {
	if ( empty( $date_string ) ) {
		return __( 'Never', 'darkshield' );
	}

	$has_parsidate = ( function_exists( 'parsidate' ) || class_exists( 'WP_Parsidate' ) );
	$has_jalali    = ( function_exists( 'jdate' ) || class_exists( 'WP_Jalali' ) );

	if ( $has_parsidate && function_exists( 'parsidate' ) ) {
		$timestamp = strtotime( $date_string );
		return parsidate( 'Y/m/d H:i', $timestamp );
	}

	if ( $has_jalali && function_exists( 'jdate' ) ) {
		$timestamp = strtotime( $date_string );
		return jdate( 'Y/m/d H:i', $timestamp );
	}

	// Fallback: WordPress date format
	$timestamp = strtotime( $date_string );
	return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
}

/**
 * Check if Jalali/Parsidate is active.
 */
function darkshield_has_jalali() {
	return ( function_exists( 'parsidate' ) || class_exists( 'WP_Parsidate' ) || function_exists( 'jdate' ) || class_exists( 'WP_Jalali' ) );
}
?>

<?php
$darkshield_page_title    = __( 'Dashboard', 'darkshield' );
$darkshield_page_subtitle = __( 'Privacy shield, performance analyzer, and traffic control for your site.', 'darkshield' );
?>
<div class="wrap darkshield">
	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-page-header.php'; ?>

	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div>

		<!-- Current Mode -->
		<div class="darkshield-mode-banner" style="--ds-mode-color:<?php echo esc_attr( $mi[1] ); ?>;">
			<h2 class="darkshield-card-title"><?php echo esc_html( $mi[0] ); ?> <?php esc_html_e( 'Current Mode:', 'darkshield' ); ?> <?php echo esc_html( $mi[2] ); ?></h2>
			<p class="darkshield-card-subtitle"><?php echo esc_html( $mi[3] ); ?></p>

			<form method="post" class="darkshield-mode-grid">
				<?php wp_nonce_field( 'darkshield_switch_mode' ); ?>
				<?php foreach ( $mode_info as $key => $info ) : ?>
					<?php $is_active = ( $key === $mode ); ?>
					<button type="<?php echo $is_active ? 'button' : 'submit'; ?>"
						<?php echo $is_active ? '' : 'name="darkshield_switch_mode" value="' . esc_attr( $key ) . '"'; ?>
						class="darkshield-mode-card <?php echo $is_active ? 'is-active' : ''; ?>"
						style="--ds-mode-color:<?php echo esc_attr( $info[1] ); ?>;">
						<span class="darkshield-mode-card-icon"><?php echo esc_html( $info[0] ); ?></span>
						<span class="darkshield-mode-card-body">
							<span class="darkshield-mode-card-title"><?php echo esc_html( $info[2] ); ?></span>
							<span class="darkshield-mode-card-desc"><?php echo esc_html( $info[3] ); ?></span>
						</span>
					</button>
				<?php endforeach; ?>
			</form>
		</div>

		<!-- Stats -->
		<div class="darkshield-stats-row">
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Log Entries', 'darkshield' ); ?></h3>
				<p><?php echo esc_html( number_format_i18n( $log_total ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Blocked', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#dc2626;"><?php echo esc_html( number_format_i18n( $log_blocked ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Scan Results', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#0284c7;"><?php echo esc_html( number_format_i18n( $scan_total ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Whitelist', 'darkshield' ); ?></h3>
				<p><?php echo esc_html( $wl_count ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Services', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#16a34a;"><?php echo esc_html( $svc_count ); ?></p>
			</div>
		</div>

		<!-- Rule / Trend KPIs -->
		<div class="darkshield-stats-row">
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Allow Rules', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#16a34a;"><?php echo esc_html( number_format_i18n( $allow_rules ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Deny Rules', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#dc2626;"><?php echo esc_html( number_format_i18n( $deny_rules ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Blocked (7 days)', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#dc2626;"><?php echo esc_html( number_format_i18n( $log_blocked_7d ) ); ?></p>
			</div>
		</div>

		<!-- Trend Chart -->
		<div class="card">
			<h2><?php esc_html_e( 'Blocked Requests — Last 14 Days', 'darkshield' ); ?></h2>
			<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-trend-chart.php'; ?>
		</div>

		<?php if ( ! empty( $top_domains ) ) : ?>
			<!-- Top Blocked Domains -->
			<div class="card">
				<h2><?php esc_html_e( 'Most Blocked Domains', 'darkshield' ); ?></h2>
				<?php foreach ( $top_domains as $td ) : ?>
					<span class="darkshield-pill" style="color:#dc2626;">
						<?php echo esc_html( $td->domain . ' (' . $td->cnt . ')' ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Active Blockers -->
		<div class="card">
			<h2><?php esc_html_e( 'Active Blockers', 'darkshield' ); ?></h2>
			<?php
			$labels = array(
				'block_fonts'     => 'Fonts',
				'block_cdn'       => 'CDN',
				'block_analytics' => 'Analytics',
				'block_updates'   => 'Updates',
				'block_gravatar'  => 'Gravatar',
				'block_embeds'    => 'Embeds',
				'block_recaptcha' => 'reCAPTCHA',
				'block_heartbeat' => 'Heartbeat',
				'block_email'     => 'Email',
				'block_emoji'     => 'Emoji',
				'allow_messenger' => 'Messenger',
			);
			foreach ( $labels as $key => $label ) :
				$on    = DarkShield_Utils::get_setting( $key, 0 );
				$color = $on ? '#00a32a' : '#999';
				$icon  = $on ? '✓' : '✗';
				?>
				<span class="darkshield-pill" style="color:<?php echo esc_attr( $color ); ?>;">
					<?php echo esc_html( $icon . ' ' . $label ); ?>
				</span>
			<?php endforeach; ?>
		</div>

		<!-- Quick Links -->
		<div class="card">
			<h2><?php esc_html_e( 'Quick Links', 'darkshield' ); ?></h2>
			<div class="darkshield-actions-row">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-scanner' ) ); ?>" class="button">🔍 <?php esc_html_e( 'Run Scanner', 'darkshield' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-performance' ) ); ?>" class="button">🚀 <?php esc_html_e( 'Performance', 'darkshield' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-settings' ) ); ?>" class="button">⚙️ <?php esc_html_e( 'Settings', 'darkshield' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-log' ) ); ?>" class="button">📋 <?php esc_html_e( 'View Log', 'darkshield' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-whitelist' ) ); ?>" class="button">📝 <?php esc_html_e( 'Whitelist', 'darkshield' ); ?></a>
			</div>

			<?php if ( $last_scan ) : ?>
				<p style="margin-top:10px;color:#666;font-size:12px;">
					<?php
					/* translators: %s: date and time of last scan */
					printf(
						esc_html__( 'Last scan: %s', 'darkshield' ),
						esc_html( darkshield_format_date( $last_scan ) )
					);
					?>
					<?php if ( darkshield_has_jalali() ) : ?>
						<span style="color:#999;font-size:11px;">(<?php esc_html_e( 'Shamsi', 'darkshield' ); ?>)</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<p style="margin-top:5px;color:#666;font-size:12px;">
				<?php
				/* translators: %1$s: plugin version number, %2$s: current mode label */
				printf(
					esc_html__( 'Plugin version: %1$s | Mode: %2$s', 'darkshield' ),
					esc_html( DARKSHIELD_VERSION ),
					esc_html( DarkShield_Utils::get_mode_label() )
				);
				?>
			</p>
		</div>

	</div>
</div>