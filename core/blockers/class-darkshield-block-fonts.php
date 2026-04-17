<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Block_Fonts {

	private $font_domains = array(
		'fonts.googleapis.com',
		'fonts.gstatic.com',
		'use.typekit.net',
		'p.typekit.net',
		'use.fontawesome.com',
		'ka-f.fontawesome.com',
		'kit.fontawesome.com',
		'pro.fontawesome.com',
		'fonts.bunny.net',
		'maxcdn.bootstrapcdn.com',
	);

	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'dequeue' ), 999 );
		add_filter( 'style_loader_src', array( $this, 'filter_src' ), 999, 2 );
	}

	public function dequeue() {
		global $wp_styles;
		if ( ! $wp_styles instanceof WP_Styles ) {
			return;
		}

		$logger = new DarkShield_Logger();

		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( DarkShield_Utils::is_protected_handle( $handle ) ) {
				continue;
			}
			if ( empty( $style->src ) || strpos( $style->src, '//' ) === false ) {
				continue;
			}
			if ( DarkShield_Utils::is_internal_url( $style->src ) ) {
				continue;
			}
			if ( DarkShield_Utils::is_whitelisted( DarkShield_Utils::extract_domain( $style->src ) ) ) {
				continue;
			}
			if ( $this->is_font( $style->src ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
				$logger->log(
					$style->src,
					DarkShield_Utils::extract_domain( $style->src ),
					'fonts',
					'blocker_fonts',
					DarkShield_Utils::get_mode(),
					true
				);
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
		if ( DarkShield_Utils::is_whitelisted( DarkShield_Utils::extract_domain( $src ) ) ) {
			return $src;
		}
		if ( $this->is_font( $src ) ) {
			return false;
		}
		return $src;
	}

	private function is_font( $url ) {
		$check = $url;
		if ( strpos( $check, '//' ) === 0 ) {
			$check = 'https:' . $check;
		}
		$domain = DarkShield_Utils::extract_domain( $check );
		return in_array( $domain, $this->font_domains, true );
	}
}
