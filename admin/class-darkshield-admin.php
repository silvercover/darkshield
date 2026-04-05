<?php
if (! defined('ABSPATH')) {
    exit;
}

class DarkShield_Admin
{

    private $current_hook = '';

    public function __construct()
    {
        add_action('admin_init', array($this, 'handle_exports'));
    }

    // ========================================
    // Menu Pages
    // ========================================

    public function add_menu_pages()
    {
        add_menu_page(__('DarkShield', 'darkshield'), __('DarkShield', 'darkshield'), 'manage_options', 'darkshield', array($this, 'render_dashboard'), 'dashicons-shield', 80);
        add_submenu_page('darkshield', __('Dashboard', 'darkshield'), __('Dashboard', 'darkshield'), 'manage_options', 'darkshield', array($this, 'render_dashboard'));
        add_submenu_page('darkshield', __('Scanner', 'darkshield'), __('Scanner', 'darkshield'), 'manage_options', 'darkshield-scanner', array($this, 'render_scanner'));
        add_submenu_page('darkshield', __('Performance', 'darkshield'), __('Performance', 'darkshield'), 'manage_options', 'darkshield-performance', array($this, 'render_performance'));
        add_submenu_page('darkshield', __('Settings', 'darkshield'), __('Settings', 'darkshield'), 'manage_options', 'darkshield-settings', array($this, 'render_settings'));
        add_submenu_page('darkshield', __('Log', 'darkshield'), __('Log', 'darkshield'), 'manage_options', 'darkshield-log', array($this, 'render_log'));
        add_submenu_page('darkshield', __('Whitelist', 'darkshield'), __('Whitelist', 'darkshield'), 'manage_options', 'darkshield-whitelist', array($this, 'render_whitelist'));
        add_submenu_page('darkshield', __('About', 'darkshield'), __('About', 'darkshield'), 'manage_options', 'darkshield-about', array($this, 'render_about'));
    }

    // ========================================
    // Enqueue — Direct Print (bypass blockers)
    // ========================================

    public function enqueue_assets($hook)
    {
        $pages = array(
            'toplevel_page_darkshield',
            'darkshield_page_darkshield-scanner',
            'darkshield_page_darkshield-performance',
            'darkshield_page_darkshield-settings',
            'darkshield_page_darkshield-log',
            'darkshield_page_darkshield-whitelist',
            'darkshield_page_darkshield-about',
        );

        if (! in_array($hook, $pages, true)) {
            return;
        }

        $this->current_hook = $hook;

        add_action('admin_head', array($this, 'print_css'), 1);
        add_action('admin_head', array($this, 'print_config'), 2);
        add_action('admin_footer', array($this, 'print_js'), 1);
    }

    public function print_css()
    {
        $url = DARKSHIELD_PLUGIN_URL . 'assets/css/admin-style.css?ver=' . DARKSHIELD_VERSION;
        echo '<link rel="stylesheet" id="darkshield-css" href="' . esc_url($url) . '" type="text/css" media="all" />' . "\n";
    }

    public function print_config()
    {
        $config = array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('darkshield_nonce'),
            'plugin_url' => DARKSHIELD_PLUGIN_URL,
            'strings'    => array(
                'confirm_mode'        => __('Are you sure you want to change the mode?', 'darkshield'),
                'scanning'            => __('Scanning...', 'darkshield'),
                'scanning_files'      => __('Scanning files', 'darkshield'),
                'scanning_db'         => __('Scanning database', 'darkshield'),
                'scan_complete'       => __('Scan complete.', 'darkshield'),
                'scan_found'          => __('Found %total% external URLs across %domains% unique domains.', 'darkshield'),
                'confirm_clear'       => __('Are you sure you want to clear all scan results?', 'darkshield'),
                'no_results'          => __('No scan results yet.', 'darkshield'),
                'error'               => __('An error occurred.', 'darkshield'),
                'pause'               => __('Pause', 'darkshield'),
                'resume'              => __('Resume', 'darkshield'),
                'paused_status'       => __('Paused', 'darkshield'),
                'confirm_stop'        => __('Stop scan? Results so far will be kept.', 'darkshield'),
                'scan_stopped'        => __('Scan stopped.', 'darkshield'),
                'scan_stopped_detail' => __('Stopped at %completed% of %total% batches.', 'darkshield'),
            ),
        );

        echo '<script type="text/javascript" id="darkshield-config">' . "\n";
        echo 'var darkshield_ajax = ' . wp_json_encode($config) . ';' . "\n";
        echo '</script>' . "\n";
    }

    public function print_js()
    {
        $base = DARKSHIELD_PLUGIN_URL . 'assets/js/';
        $v    = '?ver=' . DARKSHIELD_VERSION;

        echo '<script type="text/javascript" src="' . esc_url($base . 'admin-script.js' . $v) . '"></script>' . "\n";

        if ('darkshield_page_darkshield-scanner' === $this->current_hook) {
            echo '<script type="text/javascript" src="' . esc_url($base . 'scanner.js' . $v) . '"></script>' . "\n";
        }

        if ('darkshield_page_darkshield-performance' === $this->current_hook) {
            echo '<script type="text/javascript" src="' . esc_url($base . 'performance.js' . $v) . '"></script>' . "\n";
        }
    }

    // ========================================
    // Admin Bar
    // ========================================

    public function admin_bar_status($wp_admin_bar)
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $mode  = DarkShield_Utils::get_mode();
        $icons = array('normal' => '🟢', 'national' => '🟡', 'offline' => '🔴');
        $icon  = isset($icons[$mode]) ? $icons[$mode] : '⚪';

        $wp_admin_bar->add_node(array(
            'id'    => 'darkshield-status',
            'title' => $icon . ' DarkShield: ' . DarkShield_Utils::get_mode_label(),
            'href'  => admin_url('admin.php?page=darkshield'),
        ));

        $subs = array(
            'darkshield'             => __('Dashboard', 'darkshield'),
            'darkshield-settings'    => __('Settings', 'darkshield'),
            'darkshield-log'         => __('Log', 'darkshield'),
            'darkshield-performance' => __('Performance', 'darkshield'),
        );
        foreach ($subs as $slug => $label) {
            $wp_admin_bar->add_node(array(
                'parent' => 'darkshield-status',
                'id'     => 'ds-bar-' . $slug,
                'title'  => $label,
                'href'   => admin_url('admin.php?page=' . $slug),
            ));
        }

        // Frontend only: Analyze This Page
        if (! is_admin()) {
            $wp_admin_bar->add_node(array(
                'parent' => 'darkshield-status',
                'id'     => 'darkshield-analyze-page',
                'title'  => '🔍 ' . __('Analyze This Page', 'darkshield'),
                'href'   => '#darkshield-analyze',
                'meta'   => array(
                    'class' => 'darkshield-analyze-btn',
                    'title' => __('Collect Resource Timing data for this page', 'darkshield'),
                ),
            ));
        }
    }


    // ========================================
    // Export Handlers
    // ========================================

    public function handle_exports()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['darkshield_export_log']) && $this->verify_nonce('darkshield_log_actions')) {
            $this->export_log_csv();
        }

        if (isset($_POST['darkshield_export_whitelist']) && $this->verify_nonce('darkshield_whitelist_action')) {
            $this->export_whitelist_txt();
        }
    }

    private function export_log_csv()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'darkshield_log';
        if (! DarkShield_Utils::table_exists($table)) {
            return;
        }

        $where = array('1=1');
        $args  = array();

        $filters = array(
            'log_domain' => 'domain LIKE %s',
            'log_type'   => 'type = %s',
            'log_source' => 'source = %s',
            'log_mode'   => 'mode = %s',
        );
        foreach ($filters as $param => $sql) {
            $val = $this->input($param);
            if ('' !== $val) {
                $where[] = $sql;
                $args[]  = ('log_domain' === $param) ? '%' . $wpdb->esc_like($val) . '%' : $val;
            }
        }

        $status = $this->input('log_status');
        if ('' !== $status) {
            $where[] = 'blocked = %d';
            $args[]  = (int) $status;
        }

        foreach (array('log_date_from' => '>=', 'log_date_to' => '<=') as $param => $op) {
            $val = $this->input($param);
            if ('' !== $val) {
                $where[] = "DATE(created_at) {$op} %s";
                $args[]  = $val;
            }
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
        $rows = ! empty($args) ? $wpdb->get_results($wpdb->prepare($sql, $args)) : $wpdb->get_results($sql);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=darkshield-log-' . gmdate('Y-m-d-His') . '.csv');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, array('ID', 'URL', 'Domain', 'Type', 'Source', 'Mode', 'Blocked', 'Date'));

        foreach ((array) $rows as $r) {
            fputcsv($out, array($r->id, $r->url, $r->domain, $r->type, $r->source, $r->mode, $r->blocked ? 'Yes' : 'No', $r->created_at));
        }

        fclose($out);
        exit;
    }

    private function export_whitelist_txt()
    {
        $wl = new DarkShield_Whitelist();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename=darkshield-whitelist-' . gmdate('Y-m-d') . '.txt');

        echo implode("\n", $wl->export());
        exit;
    }

    // ========================================
    // Helpers
    // ========================================

    private function input($key)
    {
        if (isset($_POST[$key])) {
            return sanitize_text_field(wp_unslash($_POST[$key]));
        }
        if (isset($_GET[$key])) {
            return sanitize_text_field(wp_unslash($_GET[$key]));
        }
        return '';
    }

    private function verify_nonce($action)
    {
        return isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $action);
    }

    // ========================================
    // Page Renderers
    // ========================================

    public function render_dashboard()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-dashboard.php';
    }
    public function render_scanner()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-scanner.php';
    }
    public function render_performance()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-performance.php';
    }
    public function render_settings()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-settings.php';
    }
    public function render_log()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-log.php';
    }
    public function render_whitelist()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-whitelist.php';
    }
    public function render_about()
    {
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/page-about.php';
    }

    // ========================================
    // Frontend Performance Script
    // ========================================

    /**
     * Enqueue frontend perf script — only for admins on frontend.
     */
    public function enqueue_frontend_perf()
    {
        if (is_admin() || ! current_user_can('manage_options') || ! is_admin_bar_showing()) {
            return;
        }
        add_action('wp_footer', array($this, 'print_frontend_perf_script'), 999);
    }

    /**
     * Print frontend perf script directly in footer.
     */
    public function print_frontend_perf_script()
    {
        $config = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('darkshield_nonce'),
        );

        echo '<script type="text/javascript" id="darkshield-front-config">' . "\n";
        echo 'var darkshield_front_perf = ' . wp_json_encode($config) . ';' . "\n";
        echo '</script>' . "\n";

        $url = DARKSHIELD_PLUGIN_URL . 'assets/js/frontend-perf.js?ver=' . DARKSHIELD_VERSION;
        echo '<script type="text/javascript" src="' . esc_url($url) . '"></script>' . "\n";
    }
}
