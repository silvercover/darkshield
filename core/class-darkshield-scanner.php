<?php
if (! defined('ABSPATH')) {
    exit;
}

class DarkShield_Scanner
{

    private $batch_size = 30;
    private $scan_extensions = array('php', 'js', 'css', 'html', 'htm', 'json', 'twig');
    private $known_domains;

    private $url_patterns = array(
        'absolute'   => '#(https?:)?//[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}[^\s\'"<>\)\}]*#i',
        'css_import'  => '#@import\s+[\'"]?\s*(https?:)?//[^\s\'"<>;]+#i',
        'wp_remote'  => '#wp_remote_(get|post|head|request)\s*\(\s*[\'"]([^\'"]+)[\'"]#i',
    );

    public function __construct()
    {
        $this->known_domains = DarkShield_Utils::get_known_domains();
    }

    public function get_scannable_files()
    {
        $files = array();
        $dirs  = array(
            'themes'      => get_theme_root(),
            'plugins'     => WP_PLUGIN_DIR,
            'mu-plugins'  => WPMU_PLUGIN_DIR,
            'wp-includes' => ABSPATH . WPINC,
        );

        foreach ($dirs as $context => $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, $this->scan_extensions, true)) {
                    continue;
                }
                if ($file->getSize() > 512 * 1024) {
                    continue;
                }
                if ($file->getSize() > 100 * 1024 && preg_match('#\.min\.(js|css)$#i', $file->getFilename())) {
                    continue;
                }
                $files[] = array('path' => $file->getRealPath(), 'context' => $context);
            }
        }
        return $files;
    }

    public function create_batches($type = 'files')
    {
        $batch_id = 'ds_scan_' . wp_generate_password(12, false);

        if ('database' === $type) {
            $batches = $this->create_db_batches();
        } else {
            $files   = $this->get_scannable_files();
            $batches = array_chunk($files, $this->batch_size);
        }

        $total = count($batches);
        foreach ($batches as $i => $batch) {
            set_transient($batch_id . '_b_' . $i, $batch, HOUR_IN_SECONDS);
        }
        set_transient($batch_id . '_type', $type, HOUR_IN_SECONDS);
        set_transient($batch_id . '_total', $total, HOUR_IN_SECONDS);

        return array('batch_id' => $batch_id, 'total_batches' => $total, 'type' => $type);
    }

    public function process_batch($batch_id, $index)
    {
        $batch = get_transient($batch_id . '_b_' . $index);
        $type  = get_transient($batch_id . '_type');
        $total = (int) get_transient($batch_id . '_total');

        if (false === $batch) {
            return false;
        }

        $results = array();
        if ('database' === $type) {
            $results = $this->scan_db_batch($batch);
        } else {
            foreach ($batch as $info) {
                $results = array_merge($results, $this->scan_file($info['path'], $info['context']));
            }
        }

        if (! empty($results)) {
            $this->store_results($results);
        }

        delete_transient($batch_id . '_b_' . $index);

        if ($index >= $total - 1) {
            $this->cleanup_batch($batch_id, $total);
            update_option('darkshield_last_scan', current_time('mysql'));
        }

        return true;
    }

    public function stop($batch_id)
    {
        $total = (int) get_transient($batch_id . '_total');
        $this->cleanup_batch($batch_id, $total);
        update_option('darkshield_last_scan', current_time('mysql'));
    }

    private function cleanup_batch($batch_id, $total)
    {
        for ($i = 0; $i < $total; $i++) {
            delete_transient($batch_id . '_b_' . $i);
        }
        delete_transient($batch_id . '_type');
        delete_transient($batch_id . '_total');
    }

    public function scan_file($path, $context = '')
    {
        $results = array();
        if (! is_readable($path)) {
            return $results;
        }

        $content = @file_get_contents($path);
        if (empty($content)) {
            return $results;
        }

        $site_domain = DarkShield_Utils::get_site_domain();
        $lines       = explode("\n", $content);
        $seen        = array();

        foreach ($lines as $num => $line) {
            if (strlen(trim($line)) < 10 || strpos($line, '//') === false) {
                continue;
            }
            foreach ($this->extract_urls($line) as $url) {
                $key = md5($url);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $domain = DarkShield_Utils::extract_domain($url);
                if (empty($domain) || DarkShield_Utils::is_local_domain($domain) || $domain === $site_domain) {
                    continue;
                }

                $results[] = array(
                    'url'         => substr($url, 0, 2048),
                    'domain'      => $domain,
                    'type'        => $this->classify_domain($domain),
                    'file_path'   => $this->relative_path($path),
                    'line_number' => $num + 1,
                    'context'     => $context,
                );
            }
        }

        unset($content, $lines, $seen);
        return $results;
    }

    private function extract_urls($line)
    {
        $urls = array();
        foreach ($this->url_patterns as $key => $pattern) {
            if (preg_match_all($pattern, $line, $m)) {
                if ('wp_remote' === $key && ! empty($m[2])) {
                    $urls = array_merge($urls, $m[2]);
                } else {
                    $urls = array_merge($urls, $m[0]);
                }
            }
        }

        $clean = array();
        foreach (array_unique(array_filter($urls)) as $u) {
            $u = trim($u, "'\"\t\n\r\0\x0B)(");
            $u = preg_replace('#[\'";,\s\}\)]+$#', '', $u);
            if (strlen($u) >= 8 && strpos($u, 'data:') !== 0) {
                $clean[] = $u;
            }
        }
        return array_unique($clean);
    }

    public function classify_domain($domain)
    {
        if (empty($this->known_domains)) {
            return 'unknown';
        }
        foreach ($this->known_domains as $type => $domains) {
            if (in_array($domain, $domains, true)) {
                return $type;
            }
        }
        return 'unknown';
    }

    private function relative_path($abs)
    {
        $base = wp_normalize_path(ABSPATH);
        $path = wp_normalize_path($abs);
        return (strpos($path, $base) === 0) ? substr($path, strlen($base)) : $path;
    }

    private function store_results($results)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'darkshield_scan_results';
        if (! DarkShield_Utils::table_exists($table)) {
            return;
        }

        foreach ($results as $r) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE domain = %s AND file_path = %s AND line_number = %d LIMIT 1",
                $r['domain'],
                $r['file_path'],
                $r['line_number']
            ));
            if ($exists) {
                continue;
            }
            $wpdb->insert($table, array(
                'url'         => $r['url'],
                'domain'      => $r['domain'],
                'type'        => $r['type'],
                'file_path'   => $r['file_path'],
                'line_number' => $r['line_number'],
                'context'     => $r['context'],
                'status'      => DarkShield_Utils::should_block($r['url']) ? 'blocked' : 'detected',
                'created_at'  => current_time('mysql'),
            ), array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'));
        }
    }

    public function clear_results()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'darkshield_scan_results';
        if (DarkShield_Utils::table_exists($table)) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
    }

    private function create_db_batches()
    {
        global $wpdb;
        return array(
            array(array('source' => 'posts', 'table' => $wpdb->posts, 'column' => 'post_content', 'id_col' => 'ID')),
            array(array('source' => 'postmeta', 'table' => $wpdb->postmeta, 'column' => 'meta_value', 'id_col' => 'meta_id')),
            array(array('source' => 'options', 'table' => $wpdb->options, 'column' => 'option_value', 'id_col' => 'option_id')),
            array(array('source' => 'comments', 'table' => $wpdb->comments, 'column' => 'comment_content', 'id_col' => 'comment_ID')),
        );
    }

    private function scan_db_batch($batch)
    {
        global $wpdb;
        $results     = array();
        $site_domain = DarkShield_Utils::get_site_domain();

        // Allowed table names (only scan known WP tables)
        $allowed_tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->options,
            $wpdb->comments,
            $wpdb->commentmeta,
            $wpdb->termmeta,
            $wpdb->usermeta,
        );

        foreach ($batch as $item) {
            // Sanitize identifiers — only allow alphanumeric and underscore
            $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $item['table']);
            $id_col = preg_replace('/[^a-zA-Z0-9_]/', '', $item['id_col']);
            $column = preg_replace('/[^a-zA-Z0-9_]/', '', $item['column']);

            // Validate table is in allowed list
            if (! in_array($table, $allowed_tables, true)) {
                continue;
            }

            // Validate identifiers are not empty
            if (empty($table) || empty($id_col) || empty($column)) {
                continue;
            }

            $offset = 0;
            while ($offset < 5000) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Identifiers are sanitized above with preg_replace and validated against allowed list
                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `{$id_col}` AS rid, `{$column}` AS content FROM `{$table}` WHERE `{$column}` LIKE %s OR `{$column}` LIKE %s LIMIT 200 OFFSET %d",
                        '%http://%',
                        '%https://%',
                        $offset
                    )
                );

                if (empty($rows)) {
                    break;
                }

                foreach ($rows as $row) {
                    $c = $row->content;
                    if (empty($c) || strlen($c) > 100000) {
                        continue;
                    }

                    $c = maybe_unserialize($c);
                    if (is_array($c) || is_object($c)) {
                        $c = wp_json_encode($c);
                    }
                    if (! is_string($c)) {
                        continue;
                    }

                    foreach ($this->extract_urls($c) as $url) {
                        $domain = DarkShield_Utils::extract_domain($url);
                        if (empty($domain) || DarkShield_Utils::is_local_domain($domain) || $domain === $site_domain) {
                            continue;
                        }
                        $results[] = array(
                            'url'         => substr($url, 0, 2048),
                            'domain'      => $domain,
                            'type'        => $this->classify_domain($domain),
                            'file_path'   => 'db:' . $item['source'] . ':' . $row->rid,
                            'line_number' => 0,
                            'context'     => 'database',
                        );
                    }
                }

                $offset += 200;
                unset($rows);
            }
        }

        return $results;
    }


    public function get_summary()
    {
        global $wpdb;
        $table   = $wpdb->prefix . 'darkshield_scan_results';
        $summary = array('total' => 0, 'by_type' => array(), 'by_context' => array(), 'by_status' => array(), 'domains' => array());

        if (! DarkShield_Utils::table_exists($table)) {
            return $summary;
        }

        $summary['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        foreach ($wpdb->get_results("SELECT type, COUNT(*) as c FROM {$table} GROUP BY type ORDER BY c DESC") as $r) {
            $summary['by_type'][$r->type] = (int) $r->c;
        }
        foreach ($wpdb->get_results("SELECT context, COUNT(*) as c FROM {$table} GROUP BY context ORDER BY c DESC") as $r) {
            $summary['by_context'][$r->context] = (int) $r->c;
        }
        foreach ($wpdb->get_results("SELECT status, COUNT(*) as c FROM {$table} GROUP BY status") as $r) {
            $summary['by_status'][$r->status] = (int) $r->c;
        }
        $summary['domains'] = $wpdb->get_results("SELECT domain, type, COUNT(*) as c FROM {$table} GROUP BY domain, type ORDER BY c DESC LIMIT 50");

        return $summary;
    }

    public function get_results_html()
    {
        ob_start();
        include DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-scanner-results.php';
        return ob_get_clean();
    }
}
