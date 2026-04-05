<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Utils {

    // ========================================
    // Mode
    // ========================================

    public static function get_mode() {
        static $mode = null;
        if ( null !== $mode ) {
            return $mode;
        }
        $mode = get_option( 'darkshield_mode', 'normal' );
        if ( ! in_array( $mode, array( 'normal', 'national', 'offline' ), true ) ) {
            $mode = 'normal';
        }
        return $mode;
    }

    public static function set_mode( $new_mode ) {
        if ( ! in_array( $new_mode, array( 'normal', 'national', 'offline' ), true ) ) {
            return false;
        }
        $old = self::get_mode();
        update_option( 'darkshield_mode', $new_mode );

        if ( $old !== $new_mode && class_exists( 'DarkShield_Compatibility' ) ) {
            DarkShield_Compatibility::purge_caches();
        }

        do_action( 'darkshield_mode_changed', $new_mode, $old );
        return true;
    }

    public static function get_mode_label( $mode = null ) {
        if ( null === $mode ) {
            $mode = self::get_mode();
        }
        $labels = array(
            'normal'   => __( 'Normal', 'darkshield' ),
            'national' => __( 'National', 'darkshield' ),
            'offline'  => __( 'Offline', 'darkshield' ),
        );
        return isset( $labels[ $mode ] ) ? $labels[ $mode ] : $labels['normal'];
    }

    public static function is_blocking_active() {
        return 'normal' !== self::get_mode();
    }

    // ========================================
    // Settings
    // ========================================

    public static function get_settings() {
        static $settings = null;
        if ( null !== $settings ) {
            return $settings;
        }
        $defaults = array(
            'block_fonts' => 1, 'block_cdn' => 1, 'block_analytics' => 1,
            'block_updates' => 1, 'block_gravatar' => 1, 'block_embeds' => 1,
            'block_recaptcha' => 1, 'block_heartbeat' => 0, 'block_email' => 0,
            'allow_messenger' => 1, 'log_enabled' => 1, 'log_retention' => 30,
        );
        $settings = wp_parse_args( get_option( 'darkshield_settings', array() ), $defaults );
        return $settings;
    }

    public static function get_setting( $key, $default = null ) {
        $s = self::get_settings();
        return isset( $s[ $key ] ) ? $s[ $key ] : $default;
    }

    // ========================================
    // Domain Helpers
    // ========================================

    public static function extract_domain( $url ) {
        if ( empty( $url ) ) {
            return '';
        }
        if ( strpos( $url, '//' ) === 0 ) {
            $url = 'https:' . $url;
        }
        if ( strpos( $url, '://' ) === false ) {
            $url = 'https://' . $url;
        }
        $parsed = wp_parse_url( $url );
        return isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
    }

    public static function get_site_domain() {
        static $domain = null;
        if ( null === $domain ) {
            $domain = self::extract_domain( home_url() );
        }
        return $domain;
    }

    public static function is_local_domain( $domain ) {
        if ( empty( $domain ) ) {
            return true;
        }
        $domain = strtolower( $domain );

        if ( in_array( $domain, array( 'localhost', '127.0.0.1', '0.0.0.0', '::1' ), true ) ) {
            return true;
        }

        $local_tlds = array( '.local', '.localhost', '.test', '.example', '.invalid', '.dev' );
        foreach ( $local_tlds as $tld ) {
            if ( substr( $domain, -strlen( $tld ) ) === $tld ) {
                return true;
            }
        }

        if ( filter_var( $domain, FILTER_VALIDATE_IP ) ) {
            if ( ! filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return true;
            }
        }

        return false;
    }

    public static function is_iranian_domain( $domain ) {
        $domain = strtolower( $domain );

        if ( substr( $domain, -3 ) === '.ir' ) {
            return true;
        }

        static $iranian = null;
        if ( null === $iranian ) {
            $file = DARKSHIELD_PLUGIN_DIR . 'data/iranian-domains.php';
            $iranian = file_exists( $file ) ? include $file : array();
            if ( ! is_array( $iranian ) ) {
                $iranian = array();
            }
        }

        if ( in_array( $domain, $iranian, true ) ) {
            return true;
        }

        foreach ( $iranian as $ir ) {
            if ( substr( $domain, -( strlen( $ir ) + 1 ) ) === '.' . $ir ) {
                return true;
            }
        }

        return false;
    }

    // ========================================
    // Known Domains
    // ========================================

    public static function get_known_domains() {
        static $domains = null;
        if ( null !== $domains ) {
            return $domains;
        }
        $file = DARKSHIELD_PLUGIN_DIR . 'data/known-domains.php';
        $domains = file_exists( $file ) ? include $file : array();
        if ( ! is_array( $domains ) ) {
            $domains = array();
        }
        return $domains;
    }

    // ========================================
    // Whitelist
    // ========================================

    public static function is_whitelisted( $domain ) {
        static $wl = null;
        if ( null === $wl ) {
            $wl = get_option( 'darkshield_whitelist', array() );
            if ( ! is_array( $wl ) ) {
                $wl = array();
            }
        }
        $domain = strtolower( $domain );
        if ( in_array( $domain, $wl, true ) ) {
            return true;
        }
        foreach ( $wl as $w ) {
            if ( substr( $domain, -( strlen( $w ) + 1 ) ) === '.' . $w ) {
                return true;
            }
        }
        return false;
    }

    // ========================================
    // Messenger Domains
    // ========================================

    public static function is_messenger_domain( $domain ) {
        $known = self::get_known_domains();
        if ( ! isset( $known['messenger'] ) || ! is_array( $known['messenger'] ) ) {
            return false;
        }
        $domain = strtolower( $domain );
        if ( in_array( $domain, $known['messenger'], true ) ) {
            return true;
        }
        foreach ( $known['messenger'] as $m ) {
            if ( substr( $domain, -( strlen( $m ) + 1 ) ) === '.' . $m ) {
                return true;
            }
        }
        return false;
    }

    // ========================================
    // Allowed Services (SMS, Payment, etc.)
    // ========================================

    public static function get_allowed_services() {
        static $services = null;
        if ( null !== $services ) {
            return $services;
        }
        $raw = get_option( 'darkshield_allowed_services', '' );
        if ( empty( $raw ) ) {
            $services = array();
            return $services;
        }
        $services = array();
        foreach ( explode( "\n", $raw ) as $line ) {
            $d = strtolower( trim( $line ) );
            if ( ! empty( $d ) && strpos( $d, '.' ) !== false ) {
                $services[] = $d;
            }
        }
        return $services;
    }

    public static function is_allowed_service( $domain ) {
        $services = self::get_allowed_services();
        if ( empty( $services ) ) {
            return false;
        }
        $domain = strtolower( $domain );
        if ( in_array( $domain, $services, true ) ) {
            return true;
        }
        foreach ( $services as $s ) {
            if ( substr( $domain, -( strlen( $s ) + 1 ) ) === '.' . $s ) {
                return true;
            }
        }
        return false;
    }

    // ========================================
    // Protected Handles
    // ========================================

    public static function is_protected_handle( $handle ) {
        global $darkshield_protected_handles;
        if ( ! is_array( $darkshield_protected_handles ) ) {
            return false;
        }
        return in_array( $handle, $darkshield_protected_handles, true );
    }

    // ========================================
    // Internal URL Detection
    // ========================================

    public static function is_internal_url( $url ) {
        if ( empty( $url ) ) {
            return true;
        }
        if ( strpos( $url, '//' ) === false ) {
            return true;
        }

        $check = $url;
        if ( strpos( $check, '//' ) === 0 ) {
            $check = 'https:' . $check;
        }

        $domain = self::extract_domain( $check );

        if ( empty( $domain ) ) {
            return true;
        }
        if ( $domain === self::get_site_domain() ) {
            return true;
        }
        if ( self::is_local_domain( $domain ) ) {
            return true;
        }

        return false;
    }

    // ========================================
    // Should Block — Main Decision
    // ========================================

    public static function should_block( $url ) {
        $mode = self::get_mode();

        if ( 'normal' === $mode ) {
            return false;
        }

        if ( self::is_internal_url( $url ) ) {
            return false;
        }

        $domain = self::extract_domain( $url );

        if ( empty( $domain ) ) {
            return false;
        }

        if ( self::is_whitelisted( $domain ) ) {
            return false;
        }

        if ( self::is_allowed_service( $domain ) ) {
            return false;
        }

        if ( self::get_setting( 'allow_messenger', 1 ) && self::is_messenger_domain( $domain ) ) {
            return false;
        }

        if ( 'national' === $mode ) {
            return ! self::is_iranian_domain( $domain );
        }

        if ( 'offline' === $mode ) {
            return true;
        }

        return false;
    }

    // ========================================
    // Database Helpers
    // ========================================

    public static function table_exists( $table_name ) {
        static $cache = array();
        if ( isset( $cache[ $table_name ] ) ) {
            return $cache[ $table_name ];
        }
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
        $cache[ $table_name ] = ( $result === $table_name );
        return $cache[ $table_name ];
    }

    public static function ensure_tables() {
        global $wpdb;
        $log  = $wpdb->prefix . 'darkshield_log';
        $scan = $wpdb->prefix . 'darkshield_scan_results';

        if ( ! self::table_exists( $log ) || ! self::table_exists( $scan ) ) {
            if ( class_exists( 'DarkShield_Activator' ) ) {
                DarkShield_Activator::create_tables();
            }
        }
    }

    // ========================================
    // Misc Helpers
    // ========================================

    public static function truncate( $string, $max = 100, $suffix = '...' ) {
        if ( mb_strlen( $string ) <= $max ) {
            return $string;
        }
        return mb_substr( $string, 0, $max - mb_strlen( $suffix ) ) . $suffix;
    }

    public static function format_bytes( $bytes, $decimals = 1 ) {
        if ( $bytes <= 0 ) {
            return '0 B';
        }
        $units  = array( 'B', 'KB', 'MB', 'GB' );
        $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
        return sprintf( "%.{$decimals}f %s", $bytes / pow( 1024, $factor ), $units[ $factor ] );
    }

    public static function is_rest_request() {
        return ( defined( 'REST_REQUEST' ) && REST_REQUEST );
    }

    public static function is_ajax_request() {
        return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
    }

    public static function is_cron_request() {
        return ( defined( 'DOING_CRON' ) && DOING_CRON );
    }
}
