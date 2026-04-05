<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Blocker {

    private $logger;

    public function init() {
        if ( ! DarkShield_Utils::is_blocking_active() ) {
            return;
        }

        $this->logger = new DarkShield_Logger();

        $this->load_mode();
        $this->load_blockers();

        add_filter( 'pre_http_request', array( $this, 'intercept_http' ), 10, 3 );
    }

    private function load_mode() {
        $mode = DarkShield_Utils::get_mode();
        switch ( $mode ) {
            case 'national':
                $h = new DarkShield_Mode_National();
                $h->register();
                break;
            case 'offline':
                $h = new DarkShield_Mode_Offline();
                $h->register();
                break;
        }
    }

    private function load_blockers() {
        $map = array(
            'block_fonts'     => 'DarkShield_Block_Fonts',
            'block_cdn'       => 'DarkShield_Block_CDN',
            'block_analytics' => 'DarkShield_Block_Analytics',
            'block_updates'   => 'DarkShield_Block_Updates',
            'block_gravatar'  => 'DarkShield_Block_Gravatar',
            'block_embeds'    => 'DarkShield_Block_Embeds',
            'block_recaptcha' => 'DarkShield_Block_Recaptcha',
            'block_heartbeat' => 'DarkShield_Block_Heartbeat',
            'block_email'     => 'DarkShield_Block_Email',
        );

        foreach ( $map as $key => $class ) {
            if ( DarkShield_Utils::get_setting( $key, 0 ) && class_exists( $class ) ) {
                $b = new $class();
                $b->register();
            }
        }
    }

    public function intercept_http( $preempt, $parsed_args, $url ) {
        if ( false !== $preempt ) {
            return $preempt;
        }

        if ( DarkShield_Utils::is_internal_url( $url ) ) {
            return false;
        }

        if ( ! DarkShield_Utils::should_block( $url ) ) {
            return false;
        }

        $domain = DarkShield_Utils::extract_domain( $url );
        $type   = $this->classify( $domain );

        $this->logger->log( $url, $domain, $type, 'http_request', DarkShield_Utils::get_mode(), true );

        return new WP_Error(
            'darkshield_blocked',
            sprintf( __( 'DarkShield: Blocked %s', 'darkshield' ), $domain )
        );
    }

    private function classify( $domain ) {
        $known = DarkShield_Utils::get_known_domains();
        if ( is_array( $known ) ) {
            foreach ( $known as $type => $domains ) {
                if ( in_array( $domain, $domains, true ) ) {
                    return $type;
                }
            }
        }
        return 'unknown';
    }
}
