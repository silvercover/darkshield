<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Scanner_Ajax {

	private static $instance = null;
	private $scanner;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->scanner = new DarkShield_Scanner();
	}

	public function register() {
		add_action( 'wp_ajax_darkshield_start_scan', array( $this, 'handle_start' ) );
		add_action( 'wp_ajax_darkshield_process_batch', array( $this, 'handle_batch' ) );
		add_action( 'wp_ajax_darkshield_get_scan_results', array( $this, 'handle_results' ) );
		add_action( 'wp_ajax_darkshield_clear_scan', array( $this, 'handle_clear' ) );
		add_action( 'wp_ajax_darkshield_get_scan_summary', array( $this, 'handle_summary' ) );
		add_action( 'wp_ajax_darkshield_stop_scan', array( $this, 'handle_stop' ) );
	}

	public function handle_start() {
		$this->verify();
		$type = isset( $_POST['scan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['scan_type'] ) ) : 'files';
		if ( ! in_array( $type, array( 'files', 'database' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid type.' ) );
		}
		$this->scanner->clear_results();
		wp_send_json_success( $this->scanner->create_batches( $type ) );
	}

	public function handle_batch() {
		$this->verify();
		$bid = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
		$idx = isset( $_POST['batch_index'] ) ? absint( $_POST['batch_index'] ) : 0;
		if ( empty( $bid ) ) {
			wp_send_json_error( array( 'message' => 'Invalid batch.' ) );
		}
		$ok = $this->scanner->process_batch( $bid, $idx );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => 'Batch failed.' ) );
		}
		wp_send_json_success(
			array(
				'batch_index' => $idx,
				'completed'   => true,
			)
		);
	}

	public function handle_results() {
		$this->verify();
		wp_send_json_success( array( 'html' => $this->scanner->get_results_html() ) );
	}

	public function handle_clear() {
		$this->verify();
		$this->scanner->clear_results();
		wp_send_json_success( array( 'message' => __( 'Scan results cleared.', 'darkshield' ) ) );
	}

	public function handle_summary() {
		$this->verify();
		wp_send_json_success( $this->scanner->get_summary() );
	}

	public function handle_stop() {
		$this->verify();
		$bid = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
		if ( ! empty( $bid ) ) {
			$this->scanner->stop( $bid );
		}
		wp_send_json_success( array( 'message' => __( 'Scan stopped.', 'darkshield' ) ) );
	}

	private function verify() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
		}
		if ( ! check_ajax_referer( 'darkshield_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
		}
	}
}
