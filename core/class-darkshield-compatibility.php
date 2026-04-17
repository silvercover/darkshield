<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Compatibility {

    private $issues = array();

    public function register() {
        add_action( 'admin_notices', array( $this, 'show_notices' ) );
    }

    public function show_notices() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'darkshield' ) === false ) {
            return;
        }
        if ( 'normal' === DarkShield_Utils::get_mode() ) {
            return;
        }

        $this->check_all();

        foreach ( $this->issues as $issue ) {
            $class = 'notice-info';
            if ( 'error' === $issue['type'] ) {
                $class = 'notice-error';
            } elseif ( 'warning' === $issue['type'] ) {
                $class = 'notice-warning';
            }
            echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>';
            echo '<strong>🛡️ DarkShield:</strong> ';
            echo wp_kses_post( $issue['message'] );
            echo '</p></div>';
        }
    }

    public function check_all() {
        $this->issues = array();
        $this->check_cache_plugins();
        $this->check_telegram_plugins();
        $this->check_woocommerce();
        $this->check_elementor();
    }

    private function check_cache_plugins() {
        $caches = array(
            'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
            'wp-rocket/wp-rocket.php'             => 'WP Rocket',
            'w3-total-cache/w3-total-cache.php'   => 'W3 Total Cache',
            'wp-super-cache/wp-cache.php'         => 'WP Super Cache',
            'autoptimize/autoptimize.php'          => 'Autoptimize',
            'wp-fastest-cache/wpFastestCache.php'  => 'WP Fastest Cache',
        );

        $active = get_option( 'active_plugins', array() );
        foreach ( $caches as $path => $name ) {
            if ( in_array( $path, $active, true ) ) {
                $this->issues[] = array(
                    'type'    => 'info',
                     /* translators: %s: name of the detected cache plugin */
                    'message' => sprintf(
                        __( '%s detected. Cache will be purged automatically when you change DarkShield mode.', 'darkshield' ),
                        '<strong>' . $name . '</strong>'
                    ),
                );
            }
        }
    }

    private function check_telegram_plugins() {
        $active = get_option( 'active_plugins', array() );
        $found  = array();

        foreach ( $active as $plugin ) {
            if ( stripos( $plugin, 'telegram' ) !== false ) {
                $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
                $found[] = ! empty( $data['Name'] ) ? $data['Name'] : $plugin;
            }
        }

        if ( empty( $found ) ) {
            return;
        }

        $names   = implode( ', ', $found );
        $allowed = DarkShield_Utils::get_setting( 'allow_messenger', 1 );

        if ( $allowed ) {
            $this->issues[] = array(
                'type'    => 'info',
                /* translators: %s: comma-separated list of detected Telegram plugin names */
                'message' => sprintf( __( 'Telegram plugin(s) detected: %s. Messenger APIs are allowed.', 'darkshield' ), '<strong>' . esc_html( $names ) . '</strong>' ),
            );
        } else {
            $this->issues[] = array(
                'type'    => 'error',
                /* translators: %s: comma-separated list of detected Telegram plugin names */
                'message' => sprintf( __( 'Telegram plugin(s) detected: %s. Messenger APIs are BLOCKED. Enable "Allow Messenger APIs" in settings or add api.telegram.org to whitelist.', 'darkshield' ), '<strong>' . esc_html( $names ) . '</strong>' ),
            );
        }
    }

    private function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        if ( 'offline' === DarkShield_Utils::get_mode() && DarkShield_Utils::get_setting( 'block_heartbeat', 0 ) ) {
            $this->issues[] = array(
                'type'    => 'warning',
                'message' => __( 'WooCommerce detected with Heartbeat disabled. Live cart updates may not work.', 'darkshield' ),
            );
        }
    }

    private function check_elementor() {
        if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
            return;
        }
        if ( 'offline' === DarkShield_Utils::get_mode() ) {
            $this->issues[] = array(
                'type'    => 'warning',
                'message' => __( 'Elementor detected in Offline mode. Some external widgets may not render correctly.', 'darkshield' ),
            );
        }
    }

    public static function purge_caches() {
        // LiteSpeed
        if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
            LiteSpeed_Cache_API::purge_all();
        }
        // WP Rocket
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }
        // W3 Total Cache
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }
        // WP Super Cache
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }
        // Autoptimize
        if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
            autoptimizeCache::clearall();
        }
        // WP Fastest Cache
        if ( function_exists( 'wpfc_clear_all_cache' ) ) {
            wpfc_clear_all_cache();
        }
        // Object cache
        wp_cache_flush();
    }
}
