<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Block_Updates {

    public function register() {
        // Disable core update checks
        add_filter( 'pre_site_transient_update_core', array( $this, 'empty_update' ) );
        add_filter( 'pre_site_transient_update_plugins', array( $this, 'empty_update' ) );
        add_filter( 'pre_site_transient_update_themes', array( $this, 'empty_update' ) );

        // Disable auto-updates
        add_filter( 'automatic_updater_disabled', '__return_true' );
        add_filter( 'auto_update_core', '__return_false' );
        add_filter( 'auto_update_plugin', '__return_false' );
        add_filter( 'auto_update_theme', '__return_false' );
        add_filter( 'auto_update_translation', '__return_false' );

        // Remove update nag
        remove_action( 'admin_notices', 'update_nag', 3 );
        remove_action( 'admin_notices', 'maintenance_nag', 10 );

        // Block update cron
        remove_action( 'wp_version_check', 'wp_version_check' );
        remove_action( 'wp_update_plugins', 'wp_update_plugins' );
        remove_action( 'wp_update_themes', 'wp_update_themes' );

        // Block HTTP requests to wordpress.org
        add_filter( 'pre_http_request', array( $this, 'block_wp_org' ), 10, 3 );
    }

    public function empty_update() {
        return (object) array(
            'last_checked'    => time(),
            'version_checked' => get_bloginfo( 'version' ),
            'updates'         => array(),
            'translations'    => array(),
            'response'        => array(),
            'no_update'       => array(),
        );
    }

    public function block_wp_org( $preempt, $parsed_args, $url ) {
        if ( false !== $preempt ) {
            return $preempt;
        }

        $domain = DarkShield_Utils::extract_domain( $url );
        $wp_domains = array(
            'api.wordpress.org',
            'downloads.wordpress.org',
            'planet.wordpress.org',
            's.w.org',
            'ps.w.org',
            'ts.w.org',
        );

        if ( in_array( $domain, $wp_domains, true ) ) {
            $logger = new DarkShield_Logger();
            $logger->log( $url, $domain, 'updates', 'blocker_updates', DarkShield_Utils::get_mode(), true );

            return new WP_Error(
                'darkshield_updates_blocked',
                sprintf( __( 'DarkShield: Updates blocked for %s', 'darkshield' ), $domain )
            );
        }

        return $preempt;
    }
}
