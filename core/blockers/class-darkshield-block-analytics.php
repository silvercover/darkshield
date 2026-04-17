<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Block_Analytics {

	private $analytics_domains = array(
		'www.google-analytics.com',
		'google-analytics.com',
		'ssl.google-analytics.com',
		'www.googletagmanager.com',
		'googletagmanager.com',
		'tagmanager.google.com',
		'analytics.google.com',
		'stats.g.doubleclick.net',
		'connect.facebook.net',
		'www.facebook.com',
		'pixel.facebook.com',
		'snap.licdn.com',
		'bat.bing.com',
		'analytics.twitter.com',
		'static.hotjar.com',
		'script.hotjar.com',
		'vars.hotjar.com',
		'cdn.mouseflow.com',
		'cdn.mxpnl.com',
		'cdn.segment.com',
		'clarity.ms',
		'www.clarity.ms',
		'plausible.io',
	);

	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 999 );
		add_filter( 'script_loader_src', array( $this, 'filter_src' ), 999, 2 );
	}

	public function dequeue() {
		global $wp_scripts;
		if ( ! $wp_scripts instanceof WP_Scripts ) {
			return;
		}
		$logger = new DarkShield_Logger();
		foreach ( $wp_scripts->registered as $handle => $script ) {
			if ( DarkShield_Utils::is_protected_handle( $handle ) ) {
				continue;
			}
			if ( empty( $script->src ) || strpos( $script->src, '//' ) === false ) {
				continue;
			}
			if ( DarkShield_Utils::is_internal_url( $script->src ) ) {
				continue;
			}
			$domain = DarkShield_Utils::extract_domain( $script->src );
			if ( DarkShield_Utils::is_whitelisted( $domain ) || DarkShield_Utils::is_allowed_service( $domain ) ) {
				continue;
			}
			if ( $this->is_analytics( $script->src ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
				$logger->log( $script->src, $domain, 'analytics', 'blocker_analytics', DarkShield_Utils::get_mode(), true );
			}
		}
	}

	public function filter_src( $src, $handle ) {
		if ( empty( $src ) || DarkShield_Utils::is_protected_handle( $handle ) ) {
			return $src;
		}
		if ( strpos( $src, '//' ) === false || DarkShield_Utils::is_internal_url( $src ) ) {
			return $src;
		}
		$domain = DarkShield_Utils::extract_domain( $src );
		if ( DarkShield_Utils::is_whitelisted( $domain ) || DarkShield_Utils::is_allowed_service( $domain ) ) {
			return $src;
		}
		if ( $this->is_analytics( $src ) ) {
			return false;
		}
		return $src;
	}

	private function is_analytics( $url ) {
		$check = $url;
		if ( strpos( $check, '//' ) === 0 ) {
			$check = 'https:' . $check;
		}
		return in_array( DarkShield_Utils::extract_domain( $check ), $this->analytics_domains, true );
	}
}
