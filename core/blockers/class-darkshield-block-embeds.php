<?php
if (! defined('ABSPATH')) {
    exit;
}

class DarkShield_Block_Embeds
{

    public function register()
    {
        // Disable oEmbed discovery
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');

        // Disable oEmbed auto-discovery
        add_filter('embed_oembed_discover', '__return_false');

        // Block oEmbed results
        add_filter('pre_oembed_result', array($this, 'block_oembed'), 999, 3);

        // Remove embed rewrite rules
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('rest_pre_serve_request', '_oembed_rest_pre_serve_request');

        // Deregister embed scripts
        add_action('wp_enqueue_scripts', array($this, 'dequeue_embed_scripts'), 999);
    }

    public function block_oembed($result, $url, $args)
    {
        $domain = DarkShield_Utils::extract_domain($url);

        if (DarkShield_Utils::is_internal_url($url)) {
            return $result;
        }

        if (DarkShield_Utils::should_block($url)) {
            $logger = new DarkShield_Logger();
            $logger->log($url, $domain, 'embeds', 'blocker_embeds', DarkShield_Utils::get_mode(), true);
            /* translators: %s: domain name of the blocked embed */
            return '<p class="darkshield-blocked-embed">' . sprintf(__('[Embed blocked: %s]', 'darkshield'), esc_html($domain)) . '</p>';
        }

        return $result;
    }

    public function dequeue_embed_scripts()
    {
        wp_dequeue_script('wp-embed');
        wp_deregister_script('wp-embed');
    }
}
