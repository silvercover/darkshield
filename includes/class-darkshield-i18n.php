<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_I18n {

	public function load_textdomain() {
		load_plugin_textdomain(
			'darkshield',
			false,
			dirname( DARKSHIELD_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
