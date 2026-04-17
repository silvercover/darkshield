<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Block_CDN {

	private $cdn_domains = array(
		'cdn.jsdelivr.net',
		'cdnjs.cloudflare.com',
		'unpkg.com',
		'ajax.googleapis.com',
		'ajax.aspnetcdn.com',
		'stackpath.bootstrapcdn.com',
		'maxcdn.bootstrapcdn.com',
		'cdn.bootcss.com',
		'cdn.staticfile.org',
		'code.jquery.com',
		'cdn.rawgit.com',
		'rawcdn.githack.com',
		'gitcdn.github.io',
		'cdn.polyfill.io',
		'polyfill.io',
		'cdn.cloudflare.com',
		'cdn.statically.io',
	);

	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'dequeue' ), 999 );
		add_filter( 'script_loader_src', array( $this, 'filter_src' ), 999, 2 );
		add_filter( 'style_loader_src', array( $this, 'filter_src' ), 999, 2 );
	}

	public function dequeue() {
		global $wp_scripts, $wp_styles;
		$logger = new DarkShield_Logger();

		if ( $wp_scripts instanceof WP_Scripts ) {
			$this->dequeue_from( $wp_scripts, 'script', $logger );
		}
		if ( $wp_styles instanceof WP_Styles ) {
			$this->dequeue_from( $wp_styles, 'style', $logger );
		}
	}

	private function dequeue_from( $deps, $type, $logger ) {
		foreach ( $deps->registered as $handle => $dep ) {
			if ( DarkShield_Utils::is_protected_handle( $handle ) ) {
				continue;
			}
			if ( empty( $dep->src ) || strpos( $dep->src, '//' ) === false ) {
				continue;
			}
			if ( DarkShield_Utils::is_internal_url( $dep->src ) ) {
				continue;
			}
			$domain = DarkShield_Utils::extract_domain( $dep->src );
			if ( DarkShield_Utils::is_whitelisted( $domain ) ) {
				continue;
			}
			if ( DarkShield_Utils::is_allowed_service( $domain ) ) {
				continue;
			}
			if ( $this->is_cdn( $dep->src ) ) {
				if ( 'script' === $type ) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
				} else {
					wp_dequeue_style( $handle );
					wp_deregister_style( $handle );
				}
				$logger->log( $dep->src, $domain, 'cdn', 'blocker_cdn', DarkShield_Utils::get_mode(), true );
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
		if ( $this->is_cdn( $src ) ) {
			return false;
		}
		return $src;
	}

	private function is_cdn( $url ) {
		$check = $url;
		if ( strpos( $check, '//' ) === 0 ) {
			$check = 'https:' . $check;
		}
		return in_array( DarkShield_Utils::extract_domain( $check ), $this->cdn_domains, true );
	}
}
