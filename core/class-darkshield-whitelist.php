<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DarkShield_Whitelist {

    private $option_key = 'darkshield_whitelist';

    public function get_all() {
        $wl = get_option( $this->option_key, array() );
        return is_array( $wl ) ? $wl : array();
    }

    public function add( $domain ) {
        $domain = $this->sanitize( $domain );
        if ( empty( $domain ) ) {
            return false;
        }
        $wl = $this->get_all();
        if ( in_array( $domain, $wl, true ) ) {
            return false;
        }
        $wl[] = $domain;
        sort( $wl );
        update_option( $this->option_key, $wl );
        return true;
    }

    public function remove( $domain ) {
        $domain = strtolower( trim( $domain ) );
        $wl     = $this->get_all();
        $wl     = array_values( array_diff( $wl, array( $domain ) ) );
        update_option( $this->option_key, $wl );
    }

    public function import( $domains ) {
        $wl = $this->get_all();
        foreach ( $domains as $d ) {
            $d = $this->sanitize( $d );
            if ( ! empty( $d ) && ! in_array( $d, $wl, true ) ) {
                $wl[] = $d;
            }
        }
        sort( $wl );
        update_option( $this->option_key, $wl );
        return true;
    }

    public function export() {
        return $this->get_all();
    }

    public function clear() {
        update_option( $this->option_key, array() );
    }

    private function sanitize( $domain ) {
        $domain = strtolower( trim( $domain ) );
        $domain = preg_replace( '#^https?://#', '', $domain );
        $domain = preg_replace( '#^www\.#', '', $domain );
        $domain = preg_replace( '#/.*$#', '', $domain );
        $domain = preg_replace( '#:\d+$#', '', $domain );
        $domain = sanitize_text_field( $domain );
        if ( strpos( $domain, '.' ) === false ) {
            return '';
        }
        return $domain;
    }
}
