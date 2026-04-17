<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Block_Email {

	public function register() {
		// Only block emails in offline mode
		if ( 'offline' !== DarkShield_Utils::get_mode() ) {
			return;
		}

		add_filter( 'pre_wp_mail', array( $this, 'intercept_mail' ), 999, 2 );
	}

	/**
	 * Intercept and log emails instead of sending.
	 *
	 * @param null|bool $return Short-circuit return value.
	 * @param array     $atts   Email attributes.
	 * @return bool
	 */
	public function intercept_mail( $return, $atts ) {
		$to      = isset( $atts['to'] ) ? $atts['to'] : '';
		$subject = isset( $atts['subject'] ) ? $atts['subject'] : '';

		if ( is_array( $to ) ) {
			$to = implode( ', ', $to );
		}

		$logger = new DarkShield_Logger();
		$logger->log(
			'mailto:' . $to . ' | ' . $subject,
			'email',
			'email',
			'blocker_email',
			'offline',
			true
		);

		// Return true to short-circuit wp_mail — email not sent
		return true;
	}
}
