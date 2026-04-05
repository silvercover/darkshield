<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1>🛡️ <?php esc_html_e( 'DarkShield — About', 'darkshield' ); ?></h1>
    <?php include DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

    <div style="margin-top:20px;">

        <div class="card" style="max-width:800px;padding:30px;">
            <h2 style="margin-top:0;">🛡️ DarkShield v<?php echo esc_html( DARKSHIELD_VERSION ); ?></h2>
            <p style="font-size:15px;"><?php esc_html_e( 'Block external requests, protect privacy, and improve performance.', 'darkshield' ); ?></p>

            <hr>

            <h3><?php esc_html_e( 'Features', 'darkshield' ); ?></h3>
            <ul style="list-style:disc;padding-left:20px;line-height:2;">
                <li><?php esc_html_e( 'Three modes: Normal, National (Iranian only), Offline (all blocked)', 'darkshield' ); ?></li>
                <li><?php esc_html_e( '9 specialized blockers: Fonts, CDN, Analytics, Updates, Gravatar, Embeds, reCAPTCHA, Heartbeat, Email', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Whitelist and Allowed Services for SMS, Telegram, Payment gateways', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'File and database scanner for external URLs', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Performance analyzer with server-side ping and client-side Resource Timing', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Detailed logging with filters and CSV export', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Cache plugin compatibility (auto-purge on mode change)', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Output buffer filtering for HTML resources', 'darkshield' ); ?></li>
            </ul>

            <hr>

            <h3><?php esc_html_e( 'System Info', 'darkshield' ); ?></h3>
            <table class="form-table">
                <tr><th><?php esc_html_e( 'Plugin Version', 'darkshield' ); ?></th><td><?php echo esc_html( DARKSHIELD_VERSION ); ?></td></tr>
                <tr><th><?php esc_html_e( 'WordPress', 'darkshield' ); ?></th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'PHP', 'darkshield' ); ?></th><td><?php echo esc_html( phpversion() ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Current Mode', 'darkshield' ); ?></th><td><?php echo esc_html( DarkShield_Utils::get_mode_label() ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Blocking Active', 'darkshield' ); ?></th><td><?php echo DarkShield_Utils::is_blocking_active() ? '✅ ' . esc_html__( 'Yes', 'darkshield' ) : '❌ ' . esc_html__( 'No', 'darkshield' ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Whitelisted Domains', 'darkshield' ); ?></th><td><?php echo esc_html( count( get_option( 'darkshield_whitelist', array() ) ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Allowed Services', 'darkshield' ); ?></th><td><?php echo esc_html( count( DarkShield_Utils::get_allowed_services() ) ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Log Enabled', 'darkshield' ); ?></th><td><?php echo DarkShield_Utils::get_setting( 'log_enabled', 1 ) ? '✅' : '❌'; ?></td></tr>
                <tr><th><?php esc_html_e( 'Last Scan', 'darkshield' ); ?></th><td><?php echo esc_html( get_option( 'darkshield_last_scan', __( 'Never', 'darkshield' ) ) ); ?></td></tr>
            </table>

            <hr>

            <h3><?php esc_html_e( 'Block Decision Priority', 'darkshield' ); ?></h3>
            <ol style="line-height:2;">
                <li><?php esc_html_e( 'Normal mode → never block', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Internal URL (same site, localhost) → never block', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Whitelisted domain → never block', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Allowed service (SMS, Payment, etc.) → never block', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Messenger API + setting enabled → never block', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'National mode + Iranian domain → allow', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'National mode + foreign domain → block', 'darkshield' ); ?></li>
                <li><?php esc_html_e( 'Offline mode → block everything else', 'darkshield' ); ?></li>
            </ol>
        </div>

    </div>
</div>
