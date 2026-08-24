<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Activator {

	public static function activate() {
		self::create_tables();
		self::set_defaults();
		set_transient( 'darkshield_activation_notice', true, 60 );
	}

	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$log_table = $wpdb->prefix . 'darkshield_log';
		$sql_log   = "CREATE TABLE {$log_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            domain varchar(255) NOT NULL,
            type varchar(50) DEFAULT '' NOT NULL,
            source varchar(255) DEFAULT '' NOT NULL,
            mode varchar(20) NOT NULL,
            blocked tinyint(1) NOT NULL DEFAULT 1,
            matched_rule_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY domain (domain),
            KEY mode (mode),
            KEY blocked (blocked),
            KEY created_at (created_at)
        ) {$charset};";
		dbDelta( $sql_log );

		$rules_table = $wpdb->prefix . 'darkshield_rules';
		$sql_rules   = "CREATE TABLE {$rules_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL DEFAULT '',
            enabled tinyint(1) NOT NULL DEFAULT 1,
            action varchar(10) NOT NULL DEFAULT 'allow',
            priority int(11) NOT NULL DEFAULT 10,
            match_type varchar(20) NOT NULL DEFAULT 'suffix',
            pattern varchar(500) NOT NULL DEFAULT '',
            path_pattern varchar(500) NOT NULL DEFAULT '',
            resource_types varchar(255) NOT NULL DEFAULT '',
            role_condition varchar(20) NOT NULL DEFAULT '',
            page_condition varchar(30) NOT NULL DEFAULT '',
            schedule_start time DEFAULT NULL,
            schedule_end time DEFAULT NULL,
            hit_count bigint(20) unsigned NOT NULL DEFAULT 0,
            last_matched_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY enabled (enabled),
            KEY priority (priority),
            KEY action (action)
        ) {$charset};";
		dbDelta( $sql_rules );

		$scan_table = $wpdb->prefix . 'darkshield_scan_results';
		$sql_scan   = "CREATE TABLE {$scan_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            domain varchar(255) NOT NULL,
            type varchar(50) DEFAULT '' NOT NULL,
            file_path varchar(1024) DEFAULT '' NOT NULL,
            line_number int(11) DEFAULT 0 NOT NULL,
            context varchar(50) DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'detected' NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY domain (domain),
            KEY status (status)
        ) {$charset};";
		dbDelta( $sql_scan );
	}

	private static function set_defaults() {
		if ( false === get_option( 'darkshield_mode' ) ) {
			update_option( 'darkshield_mode', 'normal' );
		}

		if ( false === get_option( 'darkshield_settings' ) ) {
			update_option(
				'darkshield_settings',
				array(
					'block_fonts'     => 1,
					'block_cdn'       => 1,
					'block_analytics' => 1,
					'block_updates'   => 1,
					'block_gravatar'  => 1,
					'block_embeds'    => 1,
					'block_recaptcha' => 1,
					'block_heartbeat' => 0,
					'block_email'     => 0,
					'allow_messenger' => 1,
					'log_enabled'     => 1,
					'block_emoji'      => 1,					
					'log_retention'   => 30,
				)
			);
		}

		if ( false === get_option( 'darkshield_whitelist' ) ) {
			update_option( 'darkshield_whitelist', array() );
		}

		update_option( 'darkshield_version', DARKSHIELD_VERSION );
	}
}
