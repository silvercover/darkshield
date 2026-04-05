<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Output_Buffer {

    private $logger;

    public function register() {
        if ( ! DarkShield_Utils::is_blocking_active() ) {
            return;
        }
        if ( is_admin() || DarkShield_Utils::is_ajax_request() || DarkShield_Utils::is_rest_request() || DarkShield_Utils::is_cron_request() ) {
            return;
        }

        $this->logger = new DarkShield_Logger();
        add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );
    }

    public function start_buffer() {
        ob_start( array( $this, 'filter_output' ) );
    }

    public function filter_output( $html ) {
        if ( empty( $html ) || strlen( $html ) < 100 ) {
            return $html;
        }
        if ( strpos( $html, '<html' ) === false && strpos( $html, '<!DOCTYPE' ) === false ) {
            return $html;
        }

        $html = $this->filter_tags( $html, 'script', 'src' );
        $html = $this->filter_tags( $html, 'link', 'href' );
        $html = $this->filter_tags( $html, 'img', 'src' );
        $html = $this->filter_tags( $html, 'iframe', 'src' );

        return $html;
    }

    private function filter_tags( $html, $tag, $attr ) {
        $pattern = '#<' . $tag . '\b([^>]*)\b' . $attr . '\s*=\s*["\']([^"\']+)["\']([^>]*)/?>#i';

        return preg_replace_callback( $pattern, function( $match ) use ( $tag, $attr ) {
            $url = $match[2];

            if ( DarkShield_Utils::is_internal_url( $url ) ) {
                return $match[0];
            }

            if ( ! DarkShield_Utils::should_block( $url ) ) {
                return $match[0];
            }

            $domain = DarkShield_Utils::extract_domain( $url );
            $this->logger->log( $url, $domain, $tag, 'output_buffer', DarkShield_Utils::get_mode(), true );

            return '<!-- DarkShield: blocked ' . esc_html( $domain ) . ' -->';
        }, $html );
    }
}
