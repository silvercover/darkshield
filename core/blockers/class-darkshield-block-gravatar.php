<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Block_Gravatar {

	public function register() {
		add_filter( 'get_avatar_url', array( $this, 'replace_avatar' ), 999, 3 );
		add_filter( 'get_avatar', array( $this, 'filter_avatar_html' ), 999, 6 );
		add_filter( 'option_show_avatars', '__return_false' );
	}

	public function replace_avatar( $url, $id_or_email, $args ) {
		$domain           = DarkShield_Utils::extract_domain( $url );
		$gravatar_domains = array(
			'gravatar.com',
			'www.gravatar.com',
			'secure.gravatar.com',
			'0.gravatar.com',
			'1.gravatar.com',
			'2.gravatar.com',
			'i0.wp.com',
			'i1.wp.com',
			'i2.wp.com',
		);

		if ( in_array( $domain, $gravatar_domains, true ) ) {
			return $this->local_avatar( $args );
		}

		return $url;
	}

	public function filter_avatar_html( $avatar, $id_or_email, $size, $default, $alt, $args ) {
		if ( strpos( $avatar, 'gravatar.com' ) !== false || strpos( $avatar, 'wp.com' ) !== false ) {
			$s   = isset( $args['size'] ) ? (int) $args['size'] : 48;
			$a   = isset( $args['alt'] ) ? esc_attr( $args['alt'] ) : '';
			$url = $this->local_avatar( array( 'size' => $s ) );
			return '<img src="' . esc_url( $url ) . '" alt="' . $a . '" width="' . $s . '" height="' . $s . '" class="avatar avatar-' . $s . ' photo darkshield-avatar" />';
		}
		return $avatar;
	}

	private function local_avatar( $args ) {
		$size  = isset( $args['size'] ) ? (int) $args['size'] : 48;
		$color = substr( md5( 'darkshield' ), 0, 6 );

		return 'data:image/svg+xml,' . rawurlencode(
			'<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100">'
			. '<rect width="100" height="100" fill="#' . $color . '"/>'
			. '<text x="50" y="55" font-size="40" text-anchor="middle" dominant-baseline="middle" fill="#fff" font-family="sans-serif">?</text>'
			. '</svg>'
		);
	}
}
