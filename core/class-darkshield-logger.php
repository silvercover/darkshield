<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Logger {

	private $table;
	private $table_ok = null;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'darkshield_log';
	}

	public function log( $url, $domain, $type, $source, $mode, $blocked ) {
		if ( ! DarkShield_Utils::get_setting( 'log_enabled', 1 ) ) {
			return;
		}
		if ( ! $this->ready() ) {
			return;
		}

		global $wpdb;
		$wpdb->insert(
			$this->table,
			array(
				'url'        => substr( $url, 0, 2048 ),
				'domain'     => $domain,
				'type'       => $type,
				'source'     => $source,
				'mode'       => $mode,
				'blocked'    => $blocked ? 1 : 0,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
	}

	public function cleanup() {
		if ( ! $this->ready() ) {
			return;
		}
		global $wpdb;
		$days = max( 1, (int) DarkShield_Utils::get_setting( 'log_retention', 30 ) );
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}

	public function clear() {
		if ( ! $this->ready() ) {
			return;
		}
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$this->table}" );
	}

	private function ready() {
		if ( null !== $this->table_ok ) {
			return $this->table_ok;
		}
		$this->table_ok = DarkShield_Utils::table_exists( $this->table );
		if ( ! $this->table_ok ) {
			DarkShield_Utils::ensure_tables();
			$this->table_ok = DarkShield_Utils::table_exists( $this->table );
		}
		return $this->table_ok;
	}
}
