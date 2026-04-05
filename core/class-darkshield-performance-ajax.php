<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Performance_Ajax {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register() {
        add_action( 'wp_ajax_darkshield_perf_extract', array( $this, 'handle_extract' ) );
        add_action( 'wp_ajax_darkshield_perf_check_url', array( $this, 'handle_check' ) );
        add_action( 'wp_ajax_darkshield_perf_save_client', array( $this, 'handle_save_client' ) );
        add_action( 'wp_ajax_darkshield_perf_save_frontend', array( $this, 'handle_save_frontend' ) );
        add_action( 'wp_ajax_darkshield_perf_get_frontend', array( $this, 'handle_get_frontend' ) );
    }

    public function handle_extract() {
        $this->verify();
        $url  = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : home_url( '/' );
        $perf = new DarkShield_Performance();
        wp_send_json_success( $perf->extract_resources( $url ) );
    }

    public function handle_check() {
        $this->verify();
        $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        if ( empty( $url ) ) {
            wp_send_json_error( array( 'message' => 'No URL.' ) );
        }
        $perf = new DarkShield_Performance();
        wp_send_json_success( $perf->check_url( $url ) );
    }

    public function handle_save_client() {
        $this->verify();
        $raw = isset( $_POST['entries'] ) ? wp_unslash( $_POST['entries'] ) : '';
        if ( empty( $raw ) ) {
            wp_send_json_error( array( 'message' => 'No data.' ) );
        }
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) {
            wp_send_json_error( array( 'message' => 'Invalid data.' ) );
        }
        set_transient( 'darkshield_client_perf_' . get_current_user_id(), $data, HOUR_IN_SECONDS );
        wp_send_json_success( array( 'message' => 'Saved.', 'count' => count( $data ) ) );
    }

    public function handle_save_frontend() {
        $this->verify();

        $raw_entries = isset( $_POST['entries'] ) ? wp_unslash( $_POST['entries'] ) : '';
        $raw_meta    = isset( $_POST['meta'] ) ? wp_unslash( $_POST['meta'] ) : '';

        if ( empty( $raw_entries ) ) {
            wp_send_json_error( array( 'message' => 'No data.' ) );
        }

        $entries = json_decode( $raw_entries, true );
        $meta    = json_decode( $raw_meta, true );

        if ( ! is_array( $entries ) ) {
            wp_send_json_error( array( 'message' => 'Invalid data.' ) );
        }

        $page_url = isset( $meta['url'] ) ? esc_url_raw( $meta['url'] ) : 'unknown';
        $key      = 'darkshield_front_perf_' . md5( $page_url );

        $stored = array(
            'entries'   => $entries,
            'meta'      => $meta,
            'collected' => current_time( 'mysql' ),
            'user_id'   => get_current_user_id(),
        );

        set_transient( $key, $stored, DAY_IN_SECONDS );
        set_transient( 'darkshield_front_perf_latest', $key, DAY_IN_SECONDS );
        set_transient( 'darkshield_front_perf_latest_url', $page_url, DAY_IN_SECONDS );

        wp_send_json_success( array(
            'message'  => 'Saved.',
            'count'    => count( $entries ),
            'page_url' => $page_url,
            'view_url' => admin_url( 'admin.php?page=darkshield-performance&tab=frontend' ),
        ) );
    }

    public function handle_get_frontend() {
        $this->verify();

        $url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

        if ( ! empty( $url ) ) {
            $key = 'darkshield_front_perf_' . md5( $url );
        } else {
            $key = get_transient( 'darkshield_front_perf_latest' );
        }

        if ( empty( $key ) ) {
            wp_send_json_error( array( 'message' => 'No data found. Analyze a page first.' ) );
        }

        $data = get_transient( $key );
        if ( empty( $data ) ) {
            wp_send_json_error( array( 'message' => 'Data expired or not found.' ) );
        }

        wp_send_json_success( $data );
    }

    private function verify() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
        }
        if ( ! check_ajax_referer( 'darkshield_nonce', '_wpnonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
        }
    }
}
