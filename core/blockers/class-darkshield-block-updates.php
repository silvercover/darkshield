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

		// Suppress WordPress core emoji loader to stop frontend references to s.w.org.
		// Runs inline because register() already fires during init:10, which is
		// before wp_head, admin_print_scripts, and the content/email filters execute.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'tiny_mce_plugins', array( $this, 'strip_tinymce_emoji' ) );
		add_filter( 'wp_resource_hints', array( $this, 'strip_swo_dns_prefetch' ), 10, 2 );
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

		$domain     = DarkShield_Utils::extract_domain( $url );
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
			/* translators: %s: domain name that was blocked */
			return new WP_Error(
				'darkshield_updates_blocked',
				sprintf( __( 'DarkShield: Updates blocked for %s', 'darkshield' ), $domain )
			);
		}

		return $preempt;
	}

	/**
	 * Remove the wpemoji TinyMCE plugin so the editor stops loading emoji UI assets.
	 *
	 * @param array $plugins Registered TinyMCE plugins.
	 * @return array
	 */
	public function strip_tinymce_emoji( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}

	/**
	 * Strip s.w.org from the dns-prefetch resource hints emitted by wp_resource_hints().
	 *
	 * @param array  $urls          Resource URLs for the current relation.
	 * @param string $relation_type Relation type (preconnect, dns-prefetch, etc.).
	 * @return array
	 */
	public function strip_swo_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$urls = array_filter(
				(array) $urls,
				function ( $url ) {
					return false === strpos( (string) $url, 's.w.org' );
				}
			);
		}
		return $urls;
	}
}
