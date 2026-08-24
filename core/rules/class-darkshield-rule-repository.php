<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Rule_Repository {

	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'darkshield_rules';
	}

	public function get_all( $enabled_only = false ) {
		if ( ! $this->ready() ) {
			return array();
		}
		global $wpdb;
		if ( $enabled_only ) {
			$sql = "SELECT * FROM {$this->table} WHERE enabled = 1 ORDER BY priority ASC, id ASC";
		} else {
			$sql = "SELECT * FROM {$this->table} ORDER BY priority ASC, id ASC";
		}
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function get( $id ) {
		if ( ! $this->ready() ) {
			return null;
		}
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	public function save( $data ) {
		if ( ! $this->ready() ) {
			return false;
		}
		global $wpdb;

		$fields = array(
			'name'            => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'enabled'         => empty( $data['enabled'] ) ? 0 : 1,
			'action'          => in_array( $data['action'] ?? 'allow', array( 'allow', 'deny' ), true ) ? $data['action'] : 'allow',
			'priority'        => isset( $data['priority'] ) ? (int) $data['priority'] : 10,
			'match_type'      => in_array( $data['match_type'] ?? 'suffix', array( 'exact', 'suffix', 'wildcard', 'regex' ), true ) ? $data['match_type'] : 'suffix',
			'pattern'         => isset( $data['pattern'] ) ? sanitize_text_field( $data['pattern'] ) : '',
			'path_pattern'    => isset( $data['path_pattern'] ) ? sanitize_text_field( $data['path_pattern'] ) : '',
			'resource_types'  => isset( $data['resource_types'] ) ? sanitize_text_field( $data['resource_types'] ) : '',
			'role_condition'  => isset( $data['role_condition'] ) ? sanitize_key( $data['role_condition'] ) : '',
			'page_condition'  => isset( $data['page_condition'] ) ? sanitize_text_field( $data['page_condition'] ) : '',
			'schedule_start'  => ! empty( $data['schedule_start'] ) ? $data['schedule_start'] : null,
			'schedule_end'    => ! empty( $data['schedule_end'] ) ? $data['schedule_end'] : null,
			'updated_at'      => current_time( 'mysql' ),
		);

		if ( 'regex' === $fields['match_type'] && '' !== $fields['pattern'] ) {
			if ( false === @preg_match( $fields['pattern'], '' ) ) {
				return new WP_Error( 'darkshield_invalid_regex', __( 'Invalid regular expression pattern.', 'darkshield' ) );
			}
		}

		$id = isset( $data['id'] ) ? (int) $data['id'] : 0;

		if ( $id > 0 ) {
			$wpdb->update( $this->table, $fields, array( 'id' => $id ) );
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $this->table, $fields );
			$id = $wpdb->insert_id;
		}

		do_action( 'darkshield_rules_changed' );
		return $id;
	}

	public function delete( $id ) {
		if ( ! $this->ready() ) {
			return false;
		}
		global $wpdb;
		$wpdb->delete( $this->table, array( 'id' => (int) $id ) );
		do_action( 'darkshield_rules_changed' );
		return true;
	}

	public function toggle( $id ) {
		if ( ! $this->ready() ) {
			return false;
		}
		global $wpdb;
		$rule = $this->get( $id );
		if ( ! $rule ) {
			return false;
		}
		$wpdb->update(
			$this->table,
			array( 'enabled' => $rule['enabled'] ? 0 : 1 ),
			array( 'id' => (int) $id )
		);
		do_action( 'darkshield_rules_changed' );
		return true;
	}

	public function record_hit( $id ) {
		if ( ! $this->ready() ) {
			return;
		}
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET hit_count = hit_count + 1, last_matched_at = %s WHERE id = %d",
				current_time( 'mysql' ),
				(int) $id
			)
		);
	}

	private function ready() {
		$ok = DarkShield_Utils::table_exists( $this->table );
		if ( ! $ok ) {
			DarkShield_Utils::ensure_tables();
			$ok = DarkShield_Utils::table_exists( $this->table );
		}
		return $ok;
	}
}
