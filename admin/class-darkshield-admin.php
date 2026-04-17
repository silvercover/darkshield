<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Admin {

    private $current_hook = '';

    public function __construct() {
        add_action( 'admin_init', array( $this, 'handle_exports' ) );
    }

    // ========================================
    // Menu Pages
    // ========================================

    public function add_menu_pages() {
        add_menu_page( __( 'DarkShield', 'darkshield' ), __( 'DarkShield', 'darkshield' ), 'manage_options', 'darkshield', array( $this, 'render_dashboard' ), 'dashicons-shield', 80 );
        add_submenu_page( 'darkshield', __( 'Dashboard', 'darkshield' ), __( 'Dashboard', 'darkshield' ), 'manage_options', 'darkshield', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'darkshield', __( 'Scanner', 'darkshield' ), __( 'Scanner', 'darkshield' ), 'manage_options', 'darkshield-scanner', array( $this, 'render_scanner' ) );
        add_submenu_page( 'darkshield', __( 'Performance', 'darkshield' ), __( 'Performance', 'darkshield' ), 'manage_options', 'darkshield-performance', array( $this, 'render_performance' ) );
        add_submenu_page( 'darkshield', __( 'Settings', 'darkshield' ), __( 'Settings', 'darkshield' ), 'manage_options', 'darkshield-settings', array( $this, 'render_settings' ) );
        add_submenu_page( 'darkshield', __( 'Log', 'darkshield' ), __( 'Log', 'darkshield' ), 'manage_options', 'darkshield-log', array( $this, 'render_log' ) );
        add_submenu_page( 'darkshield', __( 'Whitelist', 'darkshield' ), __( 'Whitelist', 'darkshield' ), 'manage_options', 'darkshield-whitelist', array( $this, 'render_whitelist' ) );
        add_submenu_page( 'darkshield', __( 'About', 'darkshield' ), __( 'About', 'darkshield' ), 'manage_options', 'darkshield-about', array( $this, 'render_about' ) );
    }

    // ========================================
    // Enqueue — Direct Print (bypass blockers)
    // ========================================

    public function enqueue_assets( $hook ) {
        $darkshield_pages = array(
            'toplevel_page_darkshield',
            'darkshield_page_darkshield-scanner',
            'darkshield_page_darkshield-performance',
            'darkshield_page_darkshield-settings',
            'darkshield_page_darkshield-log',
            'darkshield_page_darkshield-whitelist',
            'darkshield_page_darkshield-about',
        );

        $is_ds_page = in_array( $hook, $darkshield_pages, true );

        if ( ! $is_ds_page ) {
            $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
            $ds_slugs = array(
                'darkshield', 'darkshield-scanner', 'darkshield-performance',
                'darkshield-settings', 'darkshield-log', 'darkshield-whitelist', 'darkshield-about',
            );
            $is_ds_page = in_array( $page, $ds_slugs, true );
        }

        if ( ! $is_ds_page ) {
            $is_ds_page = ( strpos( $hook, 'darkshield' ) !== false );
        }

        if ( ! $is_ds_page ) {
            return;
        }

        $this->current_hook = $hook;

        // Use wp_enqueue for CSS
        wp_enqueue_style(
            'darkshield-admin',
            DARKSHIELD_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            DARKSHIELD_VERSION
        );

        // Config must be inline — enqueue a dummy then add inline
        wp_register_script( 'darkshield-config', false, array(), DARKSHIELD_VERSION, false );
        wp_enqueue_script( 'darkshield-config' );
        wp_add_inline_script( 'darkshield-config', $this->get_config_js() );

        // Main admin script
        wp_enqueue_script(
            'darkshield-admin',
            DARKSHIELD_PLUGIN_URL . 'assets/js/admin-script.js',
            array( 'jquery', 'darkshield-config' ),
            DARKSHIELD_VERSION,
            true
        );

        // Page-specific scripts
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        if ( 'darkshield-scanner' === $page || strpos( $this->current_hook, 'scanner' ) !== false ) {
            wp_enqueue_script(
                'darkshield-scanner',
                DARKSHIELD_PLUGIN_URL . 'assets/js/scanner.js',
                array( 'jquery', 'darkshield-admin' ),
                DARKSHIELD_VERSION,
                true
            );
        }

        if ( 'darkshield-performance' === $page || strpos( $this->current_hook, 'performance' ) !== false ) {
            wp_enqueue_script(
                'darkshield-performance',
                DARKSHIELD_PLUGIN_URL . 'assets/js/performance.js',
                array( 'jquery', 'darkshield-admin' ),
                DARKSHIELD_VERSION,
                true
            );
        }
    }

    /**
     * Generate config JS string.
     */
    private function get_config_js() {
        $config = array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'darkshield_nonce' ),
            'plugin_url' => DARKSHIELD_PLUGIN_URL,
            'strings'    => array(
                'confirm_mode'        => __( 'Are you sure you want to change the mode?', 'darkshield' ),
                'scanning'            => __( 'Scanning...', 'darkshield' ),
                'scanning_files'      => __( 'Scanning files', 'darkshield' ),
                'scanning_db'         => __( 'Scanning database', 'darkshield' ),
                'scan_complete'       => __( 'Scan complete.', 'darkshield' ),
                /* translators: %total% is the number of external URLs found, %domains% is the number of unique domains */
                'scan_found'          => __( 'Found %total% external URLs across %domains% unique domains.', 'darkshield' ),
                'confirm_clear'       => __( 'Are you sure you want to clear all scan results?', 'darkshield' ),
                'no_results'          => __( 'No scan results yet.', 'darkshield' ),
                'error'               => __( 'An error occurred.', 'darkshield' ),
                'pause'               => __( 'Pause', 'darkshield' ),
                'resume'              => __( 'Resume', 'darkshield' ),
                'paused_status'       => __( 'Paused', 'darkshield' ),
                'confirm_stop'        => __( 'Stop scan? Results so far will be kept.', 'darkshield' ),
                'scan_stopped'        => __( 'Scan stopped.', 'darkshield' ),
                /* translators: %completed% is the number of completed batches, %total% is total batches */
                'scan_stopped_detail' => __( 'Stopped at %completed% of %total% batches.', 'darkshield' ),
            ),
        );

        return 'var darkshield_ajax = ' . wp_json_encode( $config ) . ';';
    }

    // ========================================
    // Admin Bar
    // ========================================

    public function admin_bar_status( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $mode  = DarkShield_Utils::get_mode();
        $icons = array( 'normal' => '🟢', 'national' => '🟡', 'offline' => '🔴' );
        $icon  = isset( $icons[ $mode ] ) ? $icons[ $mode ] : '⚪';

        $wp_admin_bar->add_node( array(
            'id'    => 'darkshield-status',
            'title' => $icon . ' DarkShield: ' . DarkShield_Utils::get_mode_label(),
            'href'  => admin_url( 'admin.php?page=darkshield' ),
        ) );

        $subs = array(
            'darkshield'             => __( 'Dashboard', 'darkshield' ),
            'darkshield-settings'    => __( 'Settings', 'darkshield' ),
            'darkshield-log'         => __( 'Log', 'darkshield' ),
            'darkshield-performance' => __( 'Performance', 'darkshield' ),
        );
        foreach ( $subs as $slug => $label ) {
            $wp_admin_bar->add_node( array(
                'parent' => 'darkshield-status',
                'id'     => 'ds-bar-' . $slug,
                'title'  => $label,
                'href'   => admin_url( 'admin.php?page=' . $slug ),
            ) );
        }

        if ( ! is_admin() ) {
            $wp_admin_bar->add_node( array(
                'parent' => 'darkshield-status',
                'id'     => 'darkshield-analyze-page',
                'title'  => '🔍 ' . __( 'Analyze This Page', 'darkshield' ),
                'href'   => '#darkshield-analyze',
                'meta'   => array(
                    'class' => 'darkshield-analyze-btn',
                    'title' => __( 'Collect Resource Timing data for this page', 'darkshield' ),
                ),
            ) );
        }
    }

    // ========================================
    // Frontend Performance Script
    // ========================================

    public function enqueue_frontend_perf() {
        if ( is_admin() || ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
            return;
        }

        wp_enqueue_script(
            'darkshield-front-perf',
            DARKSHIELD_PLUGIN_URL . 'assets/js/frontend-perf.js',
            array( 'jquery' ),
            DARKSHIELD_VERSION,
            true
        );

        wp_localize_script( 'darkshield-front-perf', 'darkshield_front_perf', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'darkshield_nonce' ),
        ) );
    }

    // ========================================
    // Export Handlers
    // ========================================

    public function handle_exports() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['darkshield_export_log'] ) && $this->verify_nonce( 'darkshield_log_actions' ) ) {
            $this->export_log_csv();
        }

        if ( isset( $_POST['darkshield_export_whitelist'] ) && $this->verify_nonce( 'darkshield_whitelist_action' ) ) {
            $this->export_whitelist_txt();
        }
    }

    private function export_log_csv() {
        global $wpdb;
        $table = $wpdb->prefix . 'darkshield_log';
        if ( ! DarkShield_Utils::table_exists( $table ) ) {
            return;
        }

        $where = array();
        $args  = array();

        $val = $this->input( 'log_domain' );
        if ( '' !== $val ) {
            $where[] = 'domain LIKE %s';
            $args[]  = '%' . $wpdb->esc_like( $val ) . '%';
        }

        $val = $this->input( 'log_type' );
        if ( '' !== $val ) {
            $where[] = 'type = %s';
            $args[]  = $val;
        }

        $val = $this->input( 'log_source' );
        if ( '' !== $val ) {
            $where[] = 'source = %s';
            $args[]  = $val;
        }

        $val = $this->input( 'log_mode' );
        if ( '' !== $val ) {
            $where[] = 'mode = %s';
            $args[]  = $val;
        }

        $val = $this->input( 'log_status' );
        if ( '' !== $val ) {
            $where[] = 'blocked = %d';
            $args[]  = (int) $val;
        }

        $val = $this->input( 'log_date_from' );
        if ( '' !== $val ) {
            $where[] = 'DATE(created_at) >= %s';
            $args[]  = $val;
        }

        $val = $this->input( 'log_date_to' );
        if ( '' !== $val ) {
            $where[] = 'DATE(created_at) <= %s';
            $args[]  = $val;
        }

        if ( ! empty( $where ) ) {
            $where_clause = implode( ' AND ', $where );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe prefix
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$table}` WHERE {$where_clause} ORDER BY created_at DESC",
                    $args
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- No user input
            $rows = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC" );
        }

        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=darkshield-log-' . gmdate( 'Y-m-d-His' ) . '.csv' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Writing to php://output for CSV download, not filesystem
        $out = fopen( 'php://output', 'w' );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fprintf
        fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
        fputcsv( $out, array( 'ID', 'URL', 'Domain', 'Type', 'Source', 'Mode', 'Blocked', 'Date' ) );

        foreach ( (array) $rows as $r ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
            fputcsv( $out, array(
                $r->id,
                $r->url,
                $r->domain,
                $r->type,
                $r->source,
                $r->mode,
                $r->blocked ? 'Yes' : 'No',
                $r->created_at,
            ) );
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing php://output stream, not filesystem
        fclose( $out );
        exit;
    }

    private function export_whitelist_txt() {
        $wl = new DarkShield_Whitelist();

        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=darkshield-whitelist-' . gmdate( 'Y-m-d' ) . '.txt' );

        echo esc_html( implode( "\n", $wl->export() ) );
        exit;
    }

    // ========================================
    // Helpers
    // ========================================

    private function input( $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
        }
        if ( isset( $_GET[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
        }
        return '';
    }

    private function verify_nonce( $action ) {
        return isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $action );
    }

    // ========================================
    // Page Renderers
    // ========================================

    public function render_dashboard()   { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-dashboard.php'; }
    public function render_scanner()     { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-scanner.php'; }
    public function render_performance() { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-performance.php'; }
    public function render_settings()    { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-settings.php'; }
    public function render_log()         { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-log.php'; }
    public function render_whitelist()   { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-whitelist.php'; }
    public function render_about()       { include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-about.php'; }
}
