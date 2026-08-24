<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Rule_Engine {

	private static $rules = null;

	public static function reset_cache() {
		self::$rules = null;
	}

	private static function get_rules() {
		if ( null !== self::$rules ) {
			return self::$rules;
		}
		$repo        = new DarkShield_Rule_Repository();
		self::$rules = $repo->get_all( true );
		return self::$rules;
	}

	/**
	 * Evaluate all enabled rules against a URL/domain/context.
	 *
	 * @param string $url     Full URL being considered.
	 * @param string $domain  Extracted domain.
	 * @param array  $context Optional: 'resource_type' => script|style|image|font|iframe|http|fetch|xhr.
	 * @return string|null 'allow'|'deny' on first match, null if no rule matched.
	 */
	public static function evaluate( $url, $domain, $context = array() ) {
		$rules = self::get_rules();
		if ( empty( $rules ) ) {
			return null;
		}

		$resource_type = isset( $context['resource_type'] ) ? $context['resource_type'] : '';

		foreach ( $rules as $rule ) {
			if ( ! DarkShield_Rule_Matcher::matches_domain( $domain, $rule['pattern'], $rule['match_type'] ) ) {
				continue;
			}
			if ( ! DarkShield_Rule_Matcher::matches_path( $url, $rule['path_pattern'] ) ) {
				continue;
			}
			if ( ! DarkShield_Rule_Matcher::matches_resource_type( $resource_type, $rule['resource_types'] ) ) {
				continue;
			}
			if ( ! DarkShield_Rule_Matcher::matches_role( $rule['role_condition'] ) ) {
				continue;
			}
			if ( ! DarkShield_Rule_Matcher::matches_page( $rule['page_condition'] ) ) {
				continue;
			}
			if ( ! DarkShield_Rule_Matcher::matches_schedule( $rule['schedule_start'], $rule['schedule_end'] ) ) {
				continue;
			}

			$repo = new DarkShield_Rule_Repository();
			$repo->record_hit( $rule['id'] );

			return $rule['action'];
		}

		return null;
	}
}
