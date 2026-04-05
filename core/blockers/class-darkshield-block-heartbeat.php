<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Block_Heartbeat {

    public function register() {
        $mode = DarkShield_Utils::get_mode();

        if ( 'offline' === $mode ) {
            // Completely disable heartbeat in offline mode
            add_action( 'init', array( $this, 'disable_heartbeat' ), 1 );
        } else {
            // Reduce frequency in national mode
            add_filter( 'heartbeat_settings', array( $this, 'limit_heartbeat' ) );
        }
    }

    public function disable_heartbeat() {
        wp_deregister_script( 'heartbeat' );
    }

    public function limit_heartbeat( $settings ) {
        $settings['interval'] = 120; // 120 seconds instead of 15
        return $settings;
    }
}
