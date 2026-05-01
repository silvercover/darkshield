<?php
/**
 * DarkShield - Block WordPress Emoji
 *
 * Suppresses WordPress core emoji loader: detection script, staticize filters,
 * dns-prefetch resource hint, and TinyMCE emoji plugin.
 *
 * @package DarkShield
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Block_Emoji {

	/**
	 * Register all emoji suppression hooks.
	 */
	public function register() {

		// Remove the emoji detection script from wp_head and admin_head.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_head', 'print_emoji_detection_script', 7 );

		// Remove inline emoji styles.
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );

		// Remove staticize emoji filters from content, feeds, and emails.
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        
        add_filter( 'emoji_svg_url', '__return_false' );

		// Remove the s.w.org dns-prefetch resource hint.
		add_filter( 'wp_resource_hints', array( $this, 'remove_emoji_dns_prefetch' ), 10, 2 );

		// Remove the TinyMCE emoji plugin from the classic editor.
		add_filter( 'tiny_mce_plugins', array( $this, 'remove_tinymce_emoji' ) );

		// Remove emoji canonical (added in WP 6.4+).
		remove_action( 'wp_head', 'wp_emoji_loader_script', 7 );

		// Log the block action.
		$logger = new DarkShield_Logger();
		if ( DarkShield_Utils::get_setting( 'log_enabled' ) ) {
			$logger->log(
				'https://s.w.org',
				's.w.org',
				'updates',
				'emoji-blocker',
				DarkShield_Utils::get_mode(),
				true
			);
		}
	}

	/**
	 * Remove dns-prefetch for s.w.org from resource hints.
	 *
	 * @param array  $urls          Array of URLs for the given relation type.
	 * @param string $relation_type The relation type (dns-prefetch, preconnect, etc.).
	 * @return array Filtered URLs.
	 */
	public function remove_emoji_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $urls;
		}

		$filtered = array();
		foreach ( $urls as $url ) {
			// Match both //s.w.org and https://s.w.org variations.
			if ( is_string( $url ) && false !== strpos( $url, 's.w.org' ) ) {
				continue;
			}
			if ( is_array( $url ) && isset( $url['href'] ) && false !== strpos( $url['href'], 's.w.org' ) ) {
				continue;
			}
			$filtered[] = $url;
		}

		return $filtered;
	}

	/**
	 * Remove the wpemoji TinyMCE plugin.
	 *
	 * @param array $plugins Array of TinyMCE plugin slugs.
	 * @return array Filtered plugins.
	 */
	public function remove_tinymce_emoji( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		}
		return $plugins;
	}
}
