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
	'normal'   => array( '🟢', '#00a32a', __( 'Normal — All requests allowed', 'darkshield' ) ),
	'national' => array( '🟡', '#dba617', __( 'National — Only Iranian domains allowed', 'darkshield' ) ),
	'offline'  => array( '🔴', '#d63638', __( 'Offline — All external blocked', 'darkshield' ) ),
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

<div class="wrap">
	<h1>🛡️ <?php esc_html_e( 'DarkShield — Dashboard', 'darkshield' ); ?></h1>

	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div style="margin-top:20px;">

		<!-- Current Mode -->
		<div class="card" style="max-width:100%;padding:20px;margin-bottom:20px;border-left:4px solid <?php echo esc_attr( $mi[1] ); ?>;">
			<h2 style="margin-top:0;"><?php echo esc_html( $mi[0] . ' ' . __( 'Current Mode:', 'darkshield' ) . ' ' . DarkShield_Utils::get_mode_label() ); ?></h2>
			<p><?php echo esc_html( $mi[2] ); ?></p>

			<form method="post" style="display:flex;gap:10px;margin-top:15px;">
				<?php wp_nonce_field( 'darkshield_switch_mode' ); ?>
				<?php foreach ( $mode_info as $key => $info ) : ?>
					<?php if ( $key !== $mode ) : ?>
						<button type="submit" name="darkshield_switch_mode" value="<?php echo esc_attr( $key ); ?>"
							class="button" style="border-color:<?php echo esc_attr( $info[1] ); ?>;">
							<?php echo esc_html( $info[0] . ' ' . DarkShield_Utils::get_mode_label( $key ) ); ?>
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
			</form>
		</div>

		<!-- Stats -->
		<div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:20px;">
			<div class="card" style="flex:1;min-width:140px;padding:15px;">
				<h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Log Entries', 'darkshield' ); ?></h3>
				<p style="margin:0;font-size:22px;font-weight:bold;"><?php echo esc_html( number_format_i18n( $log_total ) ); ?></p>
			</div>
			<div class="card" style="flex:1;min-width:140px;padding:15px;">
				<h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Blocked', 'darkshield' ); ?></h3>
				<p style="margin:0;font-size:22px;font-weight:bold;color:#d63638;"><?php echo esc_html( number_format_i18n( $log_blocked ) ); ?></p>
			</div>
			<div class="card" style="flex:1;min-width:140px;padding:15px;">
				<h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Scan Results', 'darkshield' ); ?></h3>
				<p style="margin:0;font-size:22px;font-weight:bold;color:#2271b1;"><?php echo esc_html( number_format_i18n( $scan_total ) ); ?></p>
			</div>
			<div class="card" style="flex:1;min-width:140px;padding:15px;">
				<h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Whitelist', 'darkshield' ); ?></h3>
				<p style="margin:0;font-size:22px;font-weight:bold;"><?php echo esc_html( $wl_count ); ?></p>
			</div>
			<div class="card" style="flex:1;min-width:140px;padding:15px;">
				<h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Services', 'darkshield' ); ?></h3>
				<p style="margin:0;font-size:22px;font-weight:bold;color:#00a32a;"><?php echo esc_html( $svc_count ); ?></p>
			</div>
		</div>

		<!-- Active Blockers -->
		<div class="card" style="max-width:100%;padding:20px;margin-bottom:20px;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Active Blockers', 'darkshield' ); ?></h2>
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
				<span style="display:inline-block;margin:3px 6px 3px 0;padding:4px 10px;background:#f0f0f0;border-radius:3px;font-size:12px;color:<?php echo esc_attr( $color ); ?>;">
					<?php echo esc_html( $icon . ' ' . $label ); ?>
				</span>
			<?php endforeach; ?>
		</div>

		<!-- Quick Links -->
		<div class="card" style="max-width:100%;padding:20px;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Quick Links', 'darkshield' ); ?></h2>
			<div style="display:flex;gap:10px;flex-wrap:wrap;">
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