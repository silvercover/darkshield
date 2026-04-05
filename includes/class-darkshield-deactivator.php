<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Deactivator {

    public static function deactivate() {
        wp_clear_scheduled_hook( 'darkshield_daily_cleanup' );
    }
}
