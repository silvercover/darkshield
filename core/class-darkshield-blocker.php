<?php
if (! defined('ABSPATH')) {
    exit;
}

class DarkShield_Blocker
{

    private $logger;

    public function init()
    {
        $this->logger = new DarkShield_Logger();

        // Specialized blockers — work in ALL modes (including Normal)
        // They only activate if their setting checkbox is enabled
        $this->load_blockers();

        // Mode-based blocking — only in National/Offline
        if (DarkShield_Utils::is_blocking_active()) {
            $this->load_mode();
            add_filter('pre_http_request', array($this, 'intercept_http'), 10, 3);
        }
    }

    private function load_mode()
    {
        $mode = DarkShield_Utils::get_mode();
        switch ($mode) {
            case 'national':
                $h = new DarkShield_Mode_National();
                $h->register();
                break;
            case 'offline':
                $h = new DarkShield_Mode_Offline();
                $h->register();
                break;
        }
    }

    private function load_blockers()
    {
        $map = array(
            'block_fonts'     => 'DarkShield_Block_Fonts',
            'block_cdn'       => 'DarkShield_Block_CDN',
            'block_analytics' => 'DarkShield_Block_Analytics',
            'block_updates'   => 'DarkShield_Block_Updates',
            'block_gravatar'  => 'DarkShield_Block_Gravatar',
            'block_embeds'    => 'DarkShield_Block_Embeds',
            'block_recaptcha' => 'DarkShield_Block_Recaptcha',
            'block_heartbeat' => 'DarkShield_Block_Heartbeat',
            'block_email'     => 'DarkShield_Block_Email',
        );

        foreach ($map as $key => $class) {
            if (DarkShield_Utils::get_setting($key, 0) && class_exists($class)) {
                $b = new $class();
                $b->register();
            }
        }
    }

    public function intercept_http($preempt, $parsed_args, $url)
    {
        if (false !== $preempt) {
            return $preempt;
        }

        // Never block internal URLs
        if (DarkShield_Utils::is_internal_url($url)) {
            return false;
        }

        // Never block admin-ajax
        if (strpos($url, admin_url('admin-ajax.php')) !== false) {
            return false;
        }

        if (! DarkShield_Utils::should_block($url)) {
            return false;
        }

        $domain = DarkShield_Utils::extract_domain($url);
        $type   = $this->classify($domain);

        $this->logger->log($url, $domain, $type, 'http_request', DarkShield_Utils::get_mode(), true);
        
        /* translators: %s: domain name that was blocked */
        return new WP_Error(
            'darkshield_blocked',
            sprintf(__('DarkShield: Blocked %s', 'darkshield'), $domain)
        );
    }

    private function classify($domain)
    {
        $known = DarkShield_Utils::get_known_domains();
        if (is_array($known)) {
            foreach ($known as $type => $domains) {
                if (in_array($domain, $domains, true)) {
                    return $type;
                }
            }
        }
        return 'unknown';
    }
}
