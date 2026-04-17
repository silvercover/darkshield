<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Performance {

	public function extract_resources( $page_url = '' ) {
		if ( empty( $page_url ) ) {
			$page_url = home_url( '/' );
		}

		$response = wp_remote_get(
			$page_url,
			array(
				'timeout'    => 30,
				'sslverify'  => false,
				'user-agent' => 'DarkShield Performance/1.0',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error'     => $response->get_error_message(),
				'resources' => array(),
			);
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return array(
				'error'     => 'Empty response.',
				'resources' => array(),
			);
		}

		$resources = array();

		// <link> stylesheets and preloads
		if ( preg_match_all( '#<link[^>]+href=["\']([^"\']+)["\'][^>]*>#i', $html, $m ) ) {
			foreach ( $m[0] as $i => $tag ) {
				$rel = '';
				if ( preg_match( '#rel=["\']([^"\']+)["\']#i', $tag, $rm ) ) {
					$rel = strtolower( $rm[1] );
				}
				if ( in_array( $rel, array( 'stylesheet', 'preload', 'preconnect', 'dns-prefetch' ), true ) ) {
					$resources[] = array(
						'url'  => $this->abs_url( $m[1][ $i ], $page_url ),
						'type' => 'stylesheet' === $rel ? 'css' : $rel,
						'tag'  => 'link',
					);
				}
			}
		}

		// <script src>
		if ( preg_match_all( '#<script[^>]+src=["\']([^"\']+)["\']#i', $html, $m ) ) {
			foreach ( $m[1] as $src ) {
				$resources[] = array(
					'url'  => $this->abs_url( $src, $page_url ),
					'type' => 'js',
					'tag'  => 'script',
				);
			}
		}

		// <img src>
		if ( preg_match_all( '#<img[^>]+src=["\']([^"\']+)["\']#i', $html, $m ) ) {
			foreach ( $m[1] as $src ) {
				if ( strpos( $src, 'data:' ) !== 0 ) {
					$resources[] = array(
						'url'  => $this->abs_url( $src, $page_url ),
						'type' => 'image',
						'tag'  => 'img',
					);
				}
			}
		}

		// <iframe src>
		if ( preg_match_all( '#<iframe[^>]+src=["\']([^"\']+)["\']#i', $html, $m ) ) {
			foreach ( $m[1] as $src ) {
				$resources[] = array(
					'url'  => $this->abs_url( $src, $page_url ),
					'type' => 'iframe',
					'tag'  => 'iframe',
				);
			}
		}

		// @import and url() inside <style> blocks
		if ( preg_match_all( '#<style[^>]*>(.*?)</style>#is', $html, $sm ) ) {
			foreach ( $sm[1] as $css ) {
				if ( preg_match_all( '#(?:@import\s+(?:url\s*\()?\s*[\'"]?\s*|url\s*\(\s*[\'"]?\s*)((?:https?:)?//[^\s\'"<>\)]+)#i', $css, $um ) ) {
					foreach ( $um[1] as $u ) {
						$resources[] = array(
							'url'  => $this->abs_url( $u, $page_url ),
							'type' => 'css_inline',
							'tag'  => 'style',
						);
					}
				}
			}
		}

		// Deduplicate
		$seen   = array();
		$unique = array();
		foreach ( $resources as $r ) {
			$key = md5( $r['url'] );
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ] = true;
				$unique[]     = $r;
			}
		}

		return array(
			'error'     => '',
			'resources' => $unique,
			'page_url'  => $page_url,
			'html_size' => strlen( $html ),
			'total'     => count( $unique ),
		);
	}

	/**
	 * Ping a single URL and measure response time.
	 */
	public function check_url( $url ) {
		$start = microtime( true );

		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => 10,
				'sslverify'   => false,
				'redirection' => 3,
				'user-agent'  => 'DarkShield Performance/1.0',
			)
		);

		$elapsed = round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $response ) ) {
			$domain     = DarkShield_Utils::extract_domain( $url );
			$is_blocked = DarkShield_Utils::should_block( $url );

			return array(
				'url'            => $url,
				'domain'         => $domain,
				'status'         => 'error',
				'status_code'    => 0,
				'response_time'  => $elapsed,
				'rating'         => 'slow',
				'error'          => $response->get_error_message(),
				'content_type'   => '',
				'content_length' => 0,
				'cache_control'  => '',
				'server'         => '',
				'is_external'    => ( $domain !== DarkShield_Utils::get_site_domain() ),
				'is_blocked'     => $is_blocked,
			);
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$headers = wp_remote_retrieve_headers( $response );
		$domain  = DarkShield_Utils::extract_domain( $url );

		$ct  = isset( $headers['content-type'] ) ? $headers['content-type'] : '';
		$cl  = isset( $headers['content-length'] ) ? (int) $headers['content-length'] : 0;
		$cc  = isset( $headers['cache-control'] ) ? $headers['cache-control'] : '';
		$srv = isset( $headers['server'] ) ? $headers['server'] : '';

		$rating = 'fast';
		if ( $elapsed > 1000 ) {
			$rating = 'slow';
		} elseif ( $elapsed > 500 ) {
			$rating = 'medium';
		}

		return array(
			'url'            => $url,
			'domain'         => $domain,
			'status'         => 'ok',
			'status_code'    => $code,
			'response_time'  => $elapsed,
			'rating'         => $rating,
			'error'          => '',
			'content_type'   => $ct,
			'content_length' => $cl,
			'cache_control'  => $cc,
			'server'         => $srv,
			'is_external'    => ( $domain !== DarkShield_Utils::get_site_domain() ),
			'is_blocked'     => DarkShield_Utils::should_block( $url ),
		);
	}

	/**
	 * Convert relative/protocol-relative URL to absolute.
	 */
	private function abs_url( $url, $base ) {
		$url = trim( $url );

		if ( strpos( $url, '//' ) === 0 ) {
			return 'https:' . $url;
		}
		if ( strpos( $url, 'http' ) === 0 ) {
			return $url;
		}
		if ( strpos( $url, '/' ) === 0 ) {
			$p      = wp_parse_url( $base );
			$scheme = isset( $p['scheme'] ) ? $p['scheme'] : 'https';
			$host   = isset( $p['host'] ) ? $p['host'] : '';
			return $scheme . '://' . $host . $url;
		}

		return rtrim( $base, '/' ) . '/' . $url;
	}
}
