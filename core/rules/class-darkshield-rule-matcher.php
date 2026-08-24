<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Rule_Matcher {

	public static function matches_domain( $domain, $pattern, $match_type ) {
		$domain  = strtolower( trim( $domain ) );
		$pattern = trim( $pattern );

		if ( '' === $pattern ) {
			return false;
		}

		switch ( $match_type ) {
			case 'exact':
				return $domain === strtolower( $pattern );

			case 'wildcard':
				$regex = '#^' . str_replace( '\*', '.*', preg_quote( strtolower( $pattern ), '#' ) ) . '$#';
				return 1 === @preg_match( $regex, $domain );

			case 'regex':
				return 1 === @preg_match( $pattern, $domain );

			case 'suffix':
			default:
				$pattern = strtolower( $pattern );
				if ( $domain === $pattern ) {
					return true;
				}
				return substr( $domain, - ( strlen( $pattern ) + 1 ) ) === '.' . $pattern;
		}
	}

	public static function matches_path( $url, $pattern ) {
		$pattern = trim( $pattern );
		if ( '' === $pattern ) {
			return true;
		}
		$parsed = wp_parse_url( $url );
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '';

		if ( false !== strpos( $pattern, '*' ) ) {
			$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '#';
			return 1 === @preg_match( $regex, $path );
		}

		return 0 === strpos( $path, $pattern );
	}

	public static function matches_resource_type( $resource_type, $csv ) {
		$csv = trim( (string) $csv );
		if ( '' === $csv ) {
			return true;
		}
		if ( empty( $resource_type ) ) {
			return true;
		}
		$types = array_map( 'trim', explode( ',', $csv ) );
		return in_array( $resource_type, $types, true );
	}

	public static function matches_role( $condition ) {
		$condition = trim( (string) $condition );
		if ( '' === $condition ) {
			return true;
		}
		if ( 'logged_in' === $condition ) {
			return is_user_logged_in();
		}
		if ( 'logged_out' === $condition ) {
			return ! is_user_logged_in();
		}
		return is_user_logged_in() && current_user_can( $condition );
	}

	public static function matches_page( $condition ) {
		$condition = trim( (string) $condition );
		if ( '' === $condition ) {
			return true;
		}
		if ( 'front_page' === $condition ) {
			return is_front_page();
		}
		if ( 0 === strpos( $condition, 'is_page:' ) ) {
			$id = (int) substr( $condition, 8 );
			return is_page( $id );
		}
		if ( 0 === strpos( $condition, 'is_singular:' ) ) {
			$post_type = substr( $condition, 12 );
			return is_singular( $post_type );
		}
		if ( 'woocommerce_shop' === $condition ) {
			return function_exists( 'is_shop' ) && is_shop();
		}
		if ( 'woocommerce_checkout' === $condition ) {
			return function_exists( 'is_checkout' ) && is_checkout();
		}
		if ( 'woocommerce_cart' === $condition ) {
			return function_exists( 'is_cart' ) && is_cart();
		}
		return true;
	}

	public static function matches_schedule( $start, $end ) {
		if ( empty( $start ) || empty( $end ) ) {
			return true;
		}
		$now = current_time( 'H:i:s' );
		if ( $start <= $end ) {
			return $now >= $start && $now <= $end;
		}
		// Overnight window, e.g. 22:00 - 06:00.
		return $now >= $start || $now <= $end;
	}
}
