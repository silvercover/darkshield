<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Mode_National {

	private $logger;

	public function register() {
		$this->logger = new DarkShield_Logger();

		add_action( 'wp_enqueue_scripts', array( $this, 'filter_enqueued' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'filter_enqueued' ), 999 );

		add_filter( 'script_loader_src', array( $this, 'filter_src' ), 999, 2 );
		add_filter( 'style_loader_src', array( $this, 'filter_src' ), 999, 2 );

		remove_action( 'wp_head', 'wp_resource_hints', 2 );
	}

	public function filter_enqueued() {
		global $wp_scripts, $wp_styles;

		if ( $wp_scripts instanceof WP_Scripts ) {
			$this->dequeue_blocked( $wp_scripts, 'script' );
		}
		if ( $wp_styles instanceof WP_Styles ) {
			$this->dequeue_blocked( $wp_styles, 'style' );
		}
	}

	private function dequeue_blocked( $deps, $type ) {
		foreach ( $deps->registered as $handle => $dep ) {
			if ( DarkShield_Utils::is_protected_handle( $handle ) ) {
				continue;
			}
			if ( empty( $dep->src ) ) {
				continue;
			}

			$src = $dep->src;

			// Relative URL = local file, skip
			if ( strpos( $src, '//' ) === false ) {
				continue;
			}

			if ( DarkShield_Utils::is_internal_url( $src ) ) {
				continue;
			}

			if ( ! DarkShield_Utils::should_block( $src ) ) {
				continue;
			}

			$domain = DarkShield_Utils::extract_domain( $src );

			if ( 'script' === $type ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			} else {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}

			$this->logger->log( $src, $domain, $type, 'enqueue_national', 'national', true );
		}
	}

	public function filter_src( $src, $handle ) {
		if ( empty( $src ) ) {
			return $src;
		}

		if ( DarkShield_Utils::is_protected_handle( $handle ) ) {
			return $src;
		}

		// Relative = local
		if ( strpos( $src, '//' ) === false ) {
			return $src;
		}

		if ( DarkShield_Utils::is_internal_url( $src ) ) {
			return $src;
		}

		if ( DarkShield_Utils::should_block( $src ) ) {
			return false;
		}

		return $src;
	}
}
