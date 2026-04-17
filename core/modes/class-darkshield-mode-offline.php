<?php
if (! defined('ABSPATH')) {
    exit;
}

class DarkShield_Mode_Offline
{

    private $logger;

    public function register()
    {
        $this->logger = new DarkShield_Logger();

        // Block external HTTP requests at lowest priority (runs first)
        add_filter('pre_http_request', array($this, 'block_external_http'), 5, 3);

        // Filter enqueued assets
        add_action('wp_enqueue_scripts', array($this, 'filter_enqueued'), 999);
        add_action('admin_enqueue_scripts', array($this, 'filter_enqueued'), 999);

        // Filter individual asset sources
        add_filter('script_loader_src', array($this, 'filter_src'), 999, 2);
        add_filter('style_loader_src', array($this, 'filter_src'), 999, 2);

        // Remove resource hints (dns-prefetch, preconnect)
        remove_action('wp_head', 'wp_resource_hints', 2);

        // Disable WP Cron external pings
        if (! defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }
    }

    /**
     * Block all external HTTP requests.
     * Respects whitelist, allowed services, messenger APIs.
     */
    public function block_external_http($preempt, $parsed_args, $url)
    {
        if (false !== $preempt) {
            return $preempt;
        }

        // Internal URLs always pass
        if (DarkShield_Utils::is_internal_url($url)) {
            return false;
        }

        // should_block checks whitelist, services, messenger
        if (! DarkShield_Utils::should_block($url)) {
            return false;
        }

        $domain = DarkShield_Utils::extract_domain($url);

        $this->logger->log($url, $domain, 'http', 'offline_http', 'offline', true);

        /* translators: %s: domain name that was blocked */
        return new WP_Error(
            'darkshield_offline',
            sprintf(__('DarkShield Offline: Blocked %s', 'darkshield'), $domain)
        );
    }

    /**
     * Dequeue external scripts and styles.
     */
    public function filter_enqueued()
    {
        global $wp_scripts, $wp_styles;

        if ($wp_scripts instanceof WP_Scripts) {
            $this->dequeue_blocked($wp_scripts, 'script');
        }
        if ($wp_styles instanceof WP_Styles) {
            $this->dequeue_blocked($wp_styles, 'style');
        }
    }

    private function dequeue_blocked($deps, $type)
    {
        foreach ($deps->registered as $handle => $dep) {
            // Never touch protected handles (jQuery, WP core, DarkShield own)
            if (DarkShield_Utils::is_protected_handle($handle)) {
                continue;
            }

            if (empty($dep->src)) {
                continue;
            }

            $src = $dep->src;

            // Relative URL = local file, always safe
            if (strpos($src, '//') === false) {
                continue;
            }

            // Same site / localhost = safe
            if (DarkShield_Utils::is_internal_url($src)) {
                continue;
            }

            // Whitelisted / allowed service / messenger = safe
            if (! DarkShield_Utils::should_block($src)) {
                continue;
            }

            $domain = DarkShield_Utils::extract_domain($src);

            if ('script' === $type) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            } else {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }

            $this->logger->log($src, $domain, $type, 'enqueue_offline', 'offline', true);
        }
    }

    /**
     * Filter individual script/style src.
     * Last line of defense — if dequeue missed it.
     */
    public function filter_src($src, $handle)
    {
        if (empty($src)) {
            return $src;
        }

        // Protected handles pass through
        if (DarkShield_Utils::is_protected_handle($handle)) {
            return $src;
        }

        // Relative = local
        if (strpos($src, '//') === false) {
            return $src;
        }

        // Internal = safe
        if (DarkShield_Utils::is_internal_url($src)) {
            return $src;
        }

        // Check block decision
        if (DarkShield_Utils::should_block($src)) {
            return false;
        }

        return $src;
    }
}
