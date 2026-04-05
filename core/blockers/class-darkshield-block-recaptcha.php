<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Block_Recaptcha {

    private $captcha_domains = array(
        'www.google.com',
        'www.gstatic.com',
        'www.recaptcha.net',
        'recaptcha.net',
        'hcaptcha.com',
        'js.hcaptcha.com',
        'api.hcaptcha.com',
        'challenges.cloudflare.com',
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
            if ( $this->is_captcha( $script->src ) ) {
                wp_dequeue_script( $handle );
                wp_deregister_script( $handle );
                $logger->log( $script->src, DarkShield_Utils::extract_domain( $script->src ), 'recaptcha', 'blocker_recaptcha', DarkShield_Utils::get_mode(), true );
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
        if ( $this->is_captcha( $src ) ) {
            return false;
        }
        return $src;
    }

    private function is_captcha( $url ) {
        $check = $url;
        if ( strpos( $check, '//' ) === 0 ) {
            $check = 'https:' . $check;
        }
        $domain = DarkShield_Utils::extract_domain( $check );

        if ( ! in_array( $domain, $this->captcha_domains, true ) ) {
            return false;
        }

        // Only block if URL contains captcha-related paths
        $captcha_patterns = array( 'recaptcha', 'hcaptcha', 'turnstile', 'captcha', 'challenge' );
        foreach ( $captcha_patterns as $p ) {
            if ( stripos( $url, $p ) !== false ) {
                return true;
            }
        }

        // www.google.com without captcha path should not be blocked here
        if ( $domain === 'www.google.com' || $domain === 'www.gstatic.com' ) {
            return false;
        }

        return true;
    }
}
