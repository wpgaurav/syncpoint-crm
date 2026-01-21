<?php
/**
 * AJAX Handler
 *
 * Handles all AJAX requests for the CRM.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_AJAX
 *
 * @since 1.0.0
 */
class SCRM_AJAX {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_handlers();
	}

	/**
	 * Register AJAX handlers.
	 */
	private function register_handlers() {
		$actions = array(
			'scrm_search_contacts',
			'scrm_search_companies',
			'scrm_get_contact',
			'scrm_quick_add_contact',
			'scrm_quick_add_company',
			'scrm_get_tags',
			'scrm_create_tag',
			'scrm_import_preview',
			'scrm_import_run',
			'scrm_sync_gateway',
			'scrm_dashboard_stats',
			'scrm_dashboard_chart_data',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, str_replace( 'scrm_', 'handle_', $action ) ) );
		}
	}

	/**
	 * Verify nonce and capability.
	 *
	 * @param string $action Nonce action.
	 * @return bool True if valid.
	 */
	private function verify_request( $action = 'scrm_ajax_nonce' ) {
		if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return false;
		}

		return true;
	}

	/**
	 * Search contacts.
	 */
	public function handle_search_contacts() {
		$this->verify_request();

		// Accept both 'search' and 'query' params for flexibility.
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		if ( empty( $search ) && isset( $_POST['query'] ) ) {
			$search = sanitize_text_field( wp_unslash( $_POST['query'] ) );
		}
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

		$contacts = scrm_get_contacts( array(
			'search' => $search,
			'type'   => $type,
			'status' => 'active',
			'limit'  => 10,
		) );

		$results = array();
		foreach ( $contacts as $contact ) {
			$results[] = array(
				'id'         => $contact->id,
				'contact_id' => $contact->contact_id,
				'name'       => trim( $contact->first_name . ' ' . $contact->last_name ) ?: '(No name)',
				'email'      => $contact->email,
				'type'       => $contact->type,
			);
		}

		wp_send_json_success( array( 'contacts' => $results ) );
	}

	/**
	 * Search companies.
	 */
	public function handle_search_companies() {
		$this->verify_request();

		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$companies = scrm_get_companies( array(
			'search' => $search,
			'limit'  => 10,
		) );

		$results = array();
		foreach ( $companies as $company ) {
			$results[] = array(
				'id'         => $company->id,
				'company_id' => $company->company_id,
				'name'       => $company->name,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * Get single contact.
	 */
	public function handle_get_contact() {
		$this->verify_request();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid contact ID.', 'syncpoint-crm' ) ) );
		}

		$contact = scrm_get_contact( $id );

		if ( ! $contact ) {
			wp_send_json_error( array( 'message' => __( 'Contact not found.', 'syncpoint-crm' ) ) );
		}

		wp_send_json_success( (array) $contact );
	}

	/**
	 * Quick add contact.
	 */
	public function handle_quick_add_contact() {
		$this->verify_request();

		$data = array(
			'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'email'      => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'type'       => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'customer',
			'company_id' => isset( $_POST['company_id'] ) ? absint( $_POST['company_id'] ) : null,
		);

		$result = scrm_create_contact( array_filter( $data ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$contact = scrm_get_contact( $result );

		wp_send_json_success( array(
			'id'         => $contact->id,
			'contact_id' => $contact->contact_id,
			'name'       => trim( $contact->first_name . ' ' . $contact->last_name ),
			'email'      => $contact->email,
		) );
	}

	/**
	 * Quick add company.
	 */
	public function handle_quick_add_company() {
		$this->verify_request();

		$data = array(
			'name'    => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'   => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'website' => isset( $_POST['website'] ) ? esc_url_raw( wp_unslash( $_POST['website'] ) ) : '',
		);

		$result = scrm_create_company( array_filter( $data ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$company = scrm_get_company( $result );

		wp_send_json_success( array(
			'id'         => $company->id,
			'company_id' => $company->company_id,
			'name'       => $company->name,
		) );
	}

	/**
	 * Get tags.
	 */
	public function handle_get_tags() {
		$this->verify_request();

		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';

		$tags = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );

		wp_send_json_success( $tags );
	}

	/**
	 * Create tag.
	 */
	public function handle_create_tag() {
		$this->verify_request();

		$data = array(
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'color'       => isset( $_POST['color'] ) ? scrm_sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '#6B7280',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
		);

		$result = scrm_create_tag( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$tag = scrm_get_tag( $result );

		wp_send_json_success( (array) $tag );
	}

	/**
	 * Import preview.
	 */
	public function handle_import_preview() {
		$this->verify_request();

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'syncpoint-crm' ) ) );
		}

		$file = $_FILES['file'];

		// Validate file type.
		$allowed_types = array( 'text/csv', 'application/vnd.ms-excel' );
		if ( ! in_array( $file['type'], $allowed_types, true ) && ! preg_match( '/\.csv$/i', $file['name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please upload a CSV file.', 'syncpoint-crm' ) ) );
		}

		// Move to temp location.
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/starter-crm/temp';

		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$temp_file = $temp_dir . '/' . wp_generate_uuid4() . '.csv';
		move_uploaded_file( $file['tmp_name'], $temp_file );

		// Parse preview.
		$importer = new SCRM\Import\CSV_Importer( $temp_file );
		$headers = $importer->get_headers();
		$preview = $importer->get_preview( 5 );

		if ( is_wp_error( $headers ) ) {
			unlink( $temp_file );
			wp_send_json_error( array( 'message' => $headers->get_error_message() ) );
		}

		wp_send_json_success( array(
			'file'    => basename( $temp_file ),
			'headers' => $headers,
			'preview' => $preview,
		) );
	}

	/**
	 * Run import.
	 */
	public function handle_import_run() {
		$this->verify_request();

		$file = isset( $_POST['file'] ) ? sanitize_file_name( $_POST['file'] ) : '';
		$mapping = isset( $_POST['mapping'] ) ? (array) $_POST['mapping'] : array();
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'contacts';
		$skip_duplicates = isset( $_POST['skip_duplicates'] ) && $_POST['skip_duplicates'];
		$update_existing = isset( $_POST['update_existing'] ) && $_POST['update_existing'];

		if ( empty( $file ) ) {
			wp_send_json_error( array( 'message' => __( 'No file specified.', 'syncpoint-crm' ) ) );
		}

		$upload_dir = wp_upload_dir();
		$file_path = $upload_dir['basedir'] . '/starter-crm/temp/' . $file;

		if ( ! file_exists( $file_path ) ) {
			wp_send_json_error( array( 'message' => __( 'File not found.', 'syncpoint-crm' ) ) );
		}

		// Sanitize mapping.
		$clean_mapping = array();
		foreach ( $mapping as $index => $field ) {
			$clean_mapping[ absint( $index ) ] = sanitize_key( $field );
		}

		$importer = new SCRM\Import\CSV_Importer( $file_path );
		$importer->set_import_type( $type );
		$importer->set_mapping( $clean_mapping );

		$results = $importer->run( array(
			'skip_duplicates' => $skip_duplicates,
			'update_existing' => $update_existing,
		) );

		// Clean up temp file.
		unlink( $file_path );

		wp_send_json_success( $results );
	}

	/**
	 * Sync gateway.
	 */
	public function handle_sync_gateway() {
		$this->verify_request();

		$gateway = isset( $_POST['gateway'] ) ? sanitize_text_field( wp_unslash( $_POST['gateway'] ) ) : '';

		if ( empty( $gateway ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid gateway.', 'syncpoint-crm' ) ) );
		}

		$gateway_instance = null;

		switch ( $gateway ) {
			case 'paypal':
				$gateway_instance = new SCRM\Gateways\PayPal();
				break;
			case 'stripe':
				$gateway_instance = new SCRM\Gateways\Stripe();
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Unknown gateway.', 'syncpoint-crm' ) ) );
		}

		if ( ! $gateway_instance->is_available() ) {
			wp_send_json_error( array( 'message' => __( 'Gateway is not enabled.', 'syncpoint-crm' ) ) );
		}

		$results = $gateway_instance->sync_transactions();

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
		}

		wp_send_json_success( $results );
	}

	/**
	 * Dashboard stats.
	 */
	public function handle_dashboard_stats() {
		$this->verify_request();

		$period = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : '30days';

		$stats = SCRM_Dashboard::get_stats( $period );

		wp_send_json_success( $stats );
	}

	/**
	 * Dashboard chart data.
	 */
	public function handle_dashboard_chart_data() {
		$this->verify_request();

		$chart = isset( $_POST['chart'] ) ? sanitize_text_field( wp_unslash( $_POST['chart'] ) ) : 'revenue';
		$period = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : '30days';

		$data = array();

		switch ( $chart ) {
			case 'revenue':
				$data = SCRM_Dashboard::get_revenue_chart_data( $period );
				break;
			case 'contacts':
				$data = SCRM_Dashboard::get_contacts_chart_data( $period );
				break;
		}

		wp_send_json_success( $data );
	}
}

// Initialize.
new SCRM_AJAX();
