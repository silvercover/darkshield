<?php
/**
 * Plugin Name:       DarkShield
 * Plugin URI:        https://github.com/silvercover/darkshield
 * Description:       Block external requests, protect privacy, and improve performance in National/Offline modes.
 * Version:           1.0.1
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Hamed Takmil (aka silvercover)
 * Author URI:        https://silvercover.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       darkshield
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DARKSHIELD_VERSION', '1.0.0' );
define( 'DARKSHIELD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DARKSHIELD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DARKSHIELD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DARKSHIELD_PLUGIN_FILE', __FILE__ );

// Protected handles — blockers must NEVER touch these
global $darkshield_protected_handles;
$darkshield_protected_handles = array(
	'darkshield-admin-css',
	'darkshield-admin-js',
	'darkshield-scanner-js',
	'darkshield-performance-js',
	'jquery',
	'jquery-core',
	'jquery-migrate',
	'wp-i18n',
	'wp-hooks',
	'common',
	'admin-bar',
	'utils',
	'wp-auth-check',
);

// ========================================
// Load Classes
// ========================================
require_once DARKSHIELD_PLUGIN_DIR . 'includes/class-darkshield-loader.php';
require_once DARKSHIELD_PLUGIN_DIR . 'includes/class-darkshield-activator.php';
require_once DARKSHIELD_PLUGIN_DIR . 'includes/class-darkshield-deactivator.php';
require_once DARKSHIELD_PLUGIN_DIR . 'includes/class-darkshield-i18n.php';
require_once DARKSHIELD_PLUGIN_DIR . 'includes/class-darkshield-utils.php';

require_once DARKSHIELD_PLUGIN_DIR . 'admin/class-darkshield-admin.php';
require_once DARKSHIELD_PLUGIN_DIR . 'admin/class-darkshield-settings.php';

require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-blocker.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-logger.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-whitelist.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-scanner.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-scanner-ajax.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-performance.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-performance-ajax.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-output-buffer.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/class-darkshield-compatibility.php';

require_once DARKSHIELD_PLUGIN_DIR . 'core/modes/class-darkshield-mode-normal.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/modes/class-darkshield-mode-national.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/modes/class-darkshield-mode-offline.php';

require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-fonts.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-cdn.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-analytics.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-updates.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-gravatar.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-embeds.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-recaptcha.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-heartbeat.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-email.php';
require_once DARKSHIELD_PLUGIN_DIR . 'core/blockers/class-darkshield-block-emoji.php';

// ========================================
// Activation / Deactivation
// ========================================
register_activation_hook( __FILE__, array( 'DarkShield_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DarkShield_Deactivator', 'deactivate' ) );

// ========================================
// Plugin Action Links
// ========================================
add_filter(
	'plugin_action_links_' . DARKSHIELD_PLUGIN_BASENAME,
	function ( $links ) {
		$url = esc_url( admin_url( 'admin.php?page=darkshield' ) );
		array_unshift( $links, '<a href="' . $url . '">' . esc_html__( 'Settings', 'darkshield' ) . '</a>' );
		return $links;
	}
);

// ========================================
// Activation Notice
// ========================================
add_action(
	'admin_notices',
	function () {
		if ( get_transient( 'darkshield_activation_notice' ) ) {
			delete_transient( 'darkshield_activation_notice' );
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo '<strong>🛡️ ' . esc_html__( 'DarkShield is activated!', 'darkshield' ) . '</strong> ';
			echo esc_html__( 'Configure your shield mode.', 'darkshield' ) . ' ';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=darkshield' ) ) . '" class="button button-primary">';
			echo esc_html__( 'Go to Dashboard', 'darkshield' );
			echo '</a></p></div>';
		}
	}
);

// ========================================
// Main Plugin Class
// ========================================
final class DarkShield_Plugin {

	private static $instance = null;
	private $loader;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->loader = new DarkShield_Loader();

		$this->load_textdomain();
		$this->register_ajax_hooks();
		$this->register_admin_hooks();
		$this->register_core_hooks();
		$this->register_cron();

		DarkShield_Utils::ensure_tables();

		$this->loader->run();
	}

	private function load_textdomain() {
		$i18n = new DarkShield_I18n();
		$this->loader->add_action( 'plugins_loaded', $i18n, 'load_textdomain' );
	}

	private function register_ajax_hooks() {
		$scanner_ajax = DarkShield_Scanner_Ajax::instance();
		$scanner_ajax->register();

		$perf_ajax = DarkShield_Performance_Ajax::instance();
		$perf_ajax->register();
	}

	private function register_admin_hooks() {
		$admin = new DarkShield_Admin();

		if ( ! is_admin() ) {
			// Frontend: admin bar + perf script for admins
			$this->loader->add_action( 'admin_bar_menu', $admin, 'admin_bar_status', 100 );
			$this->loader->add_action( 'wp_enqueue_scripts', $admin, 'enqueue_frontend_perf' );
			return;
		}

		// Admin pages
		$this->loader->add_action( 'admin_menu', $admin, 'add_menu_pages' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->loader->add_action( 'admin_bar_menu', $admin, 'admin_bar_status', 100 );

		$settings = new DarkShield_Settings();
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );
	}

	private function register_core_hooks() {
		$blocker = new DarkShield_Blocker();
		$this->loader->add_action( 'init', $blocker, 'init' );

		$output = new DarkShield_Output_Buffer();
		$output->register();

		if ( is_admin() ) {
			$compat = new DarkShield_Compatibility();
			$compat->register();
		}
	}

	private function register_cron() {
		if ( ! wp_next_scheduled( 'darkshield_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'darkshield_daily_cleanup' );
		}
		add_action(
			'darkshield_daily_cleanup',
			function () {
				$logger = new DarkShield_Logger();
				$logger->cleanup();
			}
		);
	}
}

function darkshield() {
	return DarkShield_Plugin::instance();
}
darkshield();
