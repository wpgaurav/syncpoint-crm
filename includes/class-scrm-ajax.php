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
			'scrm_sync_paypal',
			'scrm_sync_stripe',
			'scrm_sync_paypal_nvp',
			'scrm_cancel_sync',
			'scrm_check_import_progress',
			'scrm_send_email',
			'scrm_dashboard_stats',
			'scrm_dashboard_chart_data',
			'scrm_recreate_tables',
			'scrm_optimize_tables',
			'scrm_export_all',
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
	 * Sync PayPal transactions with logging.
	 */
	public function handle_sync_paypal() {
		if ( ! check_ajax_referer( 'scrm_sync_paypal', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( scrm_is_sync_running( 'paypal' ) ) {
			wp_send_json_error( array( 'message' => __( 'A sync is already in progress.', 'syncpoint-crm' ) ) );
			return;
		}

		$log_id = scrm_start_sync_log( 'paypal', 'manual' );

		$gateway = new SCRM\Gateways\PayPal();

		if ( ! $gateway->is_available() ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, __( 'PayPal is not enabled.', 'syncpoint-crm' ) );
			wp_send_json_error( array( 'message' => __( 'PayPal is not enabled.', 'syncpoint-crm' ) ) );
			return;
		}

		$results = $gateway->sync_transactions();

		if ( is_wp_error( $results ) ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $results->get_error_message() );
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
			return;
		}

		scrm_complete_sync_log(
			$log_id,
			'completed',
			$results['synced'] ?? 0,
			$results['skipped'] ?? 0,
			$results['contacts_added'] ?? 0
		);

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: transactions synced, 2: contacts created */
				__( 'Synced %1$d transactions, created %2$d contacts.', 'syncpoint-crm' ),
				$results['synced'] ?? 0,
				$results['contacts_added'] ?? 0
			),
			'results' => $results,
		) );
	}

	/**
	 * Sync Stripe transactions with logging.
	 */
	public function handle_sync_stripe() {
		if ( ! check_ajax_referer( 'scrm_sync_stripe', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( scrm_is_sync_running( 'stripe' ) ) {
			wp_send_json_error( array( 'message' => __( 'A sync is already in progress.', 'syncpoint-crm' ) ) );
			return;
		}

		$log_id = scrm_start_sync_log( 'stripe', 'manual' );

		$gateway = new SCRM\Gateways\Stripe();

		if ( ! $gateway->is_available() ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, __( 'Stripe is not enabled.', 'syncpoint-crm' ) );
			wp_send_json_error( array( 'message' => __( 'Stripe is not enabled.', 'syncpoint-crm' ) ) );
			return;
		}

		$results = $gateway->sync_transactions();

		if ( is_wp_error( $results ) ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $results->get_error_message() );
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
			return;
		}

		scrm_complete_sync_log(
			$log_id,
			'completed',
			$results['synced'] ?? 0,
			$results['skipped'] ?? 0,
			$results['contacts_added'] ?? 0
		);

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: transactions synced, 2: contacts created */
				__( 'Synced %1$d transactions, created %2$d contacts.', 'syncpoint-crm' ),
				$results['synced'] ?? 0,
				$results['contacts_added'] ?? 0
			),
			'results' => $results,
		) );
	}

	/**
	 * Sync PayPal transactions using NVP API (historical import).
	 */
	public function handle_sync_paypal_nvp() {
		// Increase execution time for large imports.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		if ( ! check_ajax_referer( 'scrm_sync_paypal_nvp', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( scrm_is_sync_running( 'paypal' ) ) {
			wp_send_json_error( array( 'message' => __( 'A sync is already in progress. If stuck, wait 5 minutes and try again.', 'syncpoint-crm' ) ) );
			return;
		}

		$log_id = scrm_start_sync_log( 'paypal', 'historical' );

		try {
			$gateway = new SCRM\Gateways\PayPal();

			if ( ! $gateway->is_available() ) {
				scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, __( 'PayPal is not enabled.', 'syncpoint-crm' ) );
				wp_send_json_error( array( 'message' => __( 'PayPal is not enabled.', 'syncpoint-crm' ) ) );
				return;
			}

			$results = $gateway->sync_transactions_nvp();

			if ( is_wp_error( $results ) ) {
				scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $results->get_error_message() );
				wp_send_json_error( array( 'message' => $results->get_error_message() ) );
				return;
			}

			scrm_complete_sync_log(
				$log_id,
				'completed',
				$results['synced'] ?? 0,
				$results['skipped'] ?? 0,
				$results['contacts_added'] ?? 0
			);

			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: 1: transactions synced, 2: contacts created */
					__( 'Imported %1$d historical transactions, created %2$d contacts.', 'syncpoint-crm' ),
					$results['synced'] ?? 0,
					$results['contacts_added'] ?? 0
				),
				'results' => $results,
			) );

		} catch ( \Exception $e ) {
			set_transient( 'scrm_paypal_import_progress', array(
				'status'  => 'error',
				'message' => $e->getMessage(),
			), 600 );
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		} catch ( \Error $e ) {
			set_transient( 'scrm_paypal_import_progress', array(
				'status'  => 'error',
				'message' => $e->getMessage(),
			), 600 );
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $e->getMessage() );
			wp_send_json_error( array( 'message' => 'PHP Error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Cancel running sync.
	 */
	public function handle_cancel_sync() {
		$this->verify_request();

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;
		if ( ! $log_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid log ID.', 'syncpoint-crm' ) ) );
		}

		$result = scrm_complete_sync_log( $log_id, 'cancelled', 0, 0, 0, __( 'Cancelled by user.', 'syncpoint-crm' ) );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Sync cancelled.', 'syncpoint-crm' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to cancel sync.', 'syncpoint-crm' ) ) );
		}
	}

	/**
	 * Check PayPal import progress.
	 */
	public function handle_check_import_progress() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		$progress = get_transient( 'scrm_paypal_import_progress' );

		if ( ! $progress ) {
			wp_send_json_success( array(
				'status'  => 'idle',
				'message' => __( 'No import in progress.', 'syncpoint-crm' ),
			) );
			return;
		}

		wp_send_json_success( $progress );
	}

	/**
	 * Send email to contacts.
	 */
	public function handle_send_email() {
		if ( ! check_ajax_referer( 'scrm_send_email', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		$contact_ids = isset( $_POST['contact_ids'] ) ? array_map( 'absint', (array) $_POST['contact_ids'] ) : array();
		$subject     = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$message     = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $contact_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No contacts selected.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( empty( $subject ) || empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Subject and message are required.', 'syncpoint-crm' ) ) );
			return;
		}

		$sent     = 0;
		$failed   = 0;
		$settings = scrm_get_settings( 'invoices' );
		$from_name = $settings['company_name'] ?? get_bloginfo( 'name' );
		$from_email = get_option( 'admin_email' );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);

		foreach ( $contact_ids as $contact_id ) {
			$contact = scrm_get_contact( $contact_id );

			if ( ! $contact || empty( $contact->email ) ) {
				$failed++;
				continue;
			}

			$personalized_message = str_replace(
				array( '{first_name}', '{last_name}', '{email}', '{company}' ),
				array(
					$contact->first_name ?: '',
					$contact->last_name ?: '',
					$contact->email,
					$contact->company_id ? scrm_get_company( $contact->company_id )->name ?? '' : '',
				),
				$message
			);

			$personalized_subject = str_replace(
				array( '{first_name}', '{last_name}' ),
				array( $contact->first_name ?: '', $contact->last_name ?: '' ),
				$subject
			);

			$email_body = scrm_get_email_template( $personalized_message );

			$result = wp_mail( $contact->email, $personalized_subject, $email_body, $headers );

			if ( $result ) {
				$sent++;
				scrm_log_activity( 'contact', $contact_id, 'email_sent', sprintf(
					/* translators: %s: email subject */
					__( 'Email sent: %s', 'syncpoint-crm' ),
					$personalized_subject
				) );
				scrm_log_email( $contact_id, $personalized_subject, $personalized_message, 'sent' );
			} else {
				$failed++;
				scrm_log_email( $contact_id, $personalized_subject, $personalized_message, 'failed' );
			}
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: emails sent, 2: emails failed */
				__( 'Sent %1$d emails, %2$d failed.', 'syncpoint-crm' ),
				$sent,
				$failed
			),
			'sent'   => $sent,
			'failed' => $failed,
		) );
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

	/**
	 * Recreate missing database tables.
	 */
	public function handle_recreate_tables() {
		if ( ! check_ajax_referer( 'scrm_recreate_tables', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		// Re-run the activator to create any missing tables.
		require_once SCRM_PLUGIN_DIR . 'includes/class-scrm-activator.php';
		SCRM_Activator::activate();

		wp_send_json_success( array(
			'message' => __( 'Tables recreated successfully.', 'syncpoint-crm' ),
		) );
	}

	/**
	 * Optimize database tables.
	 */
	public function handle_optimize_tables() {
		if ( ! check_ajax_referer( 'scrm_optimize_tables', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'scrm_contacts',
			$wpdb->prefix . 'scrm_companies',
			$wpdb->prefix . 'scrm_transactions',
			$wpdb->prefix . 'scrm_invoices',
			$wpdb->prefix . 'scrm_invoice_items',
			$wpdb->prefix . 'scrm_tags',
			$wpdb->prefix . 'scrm_tag_relationships',
			$wpdb->prefix . 'scrm_activity_log',
			$wpdb->prefix . 'scrm_webhook_log',
			$wpdb->prefix . 'scrm_sync_log',
			$wpdb->prefix . 'scrm_email_log',
		);

		$optimized = 0;
		foreach ( $tables as $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			if ( $exists ) {
				$wpdb->query( "OPTIMIZE TABLE {$table}" );
				$optimized++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of tables optimized */
				__( 'Optimized %d tables.', 'syncpoint-crm' ),
				$optimized
			),
		) );
	}

	/**
	 * Export all CRM data.
	 */
	public function handle_export_all() {
		if ( ! check_ajax_referer( 'scrm_export_all', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'syncpoint-crm' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'syncpoint-crm' ) ) );
			return;
		}

		global $wpdb;

		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/scrm-exports';

		// Create export directory if it doesn't exist.
		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
			file_put_contents( $export_dir . '/.htaccess', 'deny from all' );
			file_put_contents( $export_dir . '/index.php', '<?php // Silence is golden.' );
		}

		$timestamp = date( 'Y-m-d-His' );
		$zip_filename = "scrm-export-{$timestamp}.zip";
		$zip_path = $export_dir . '/' . $zip_filename;

		// Create ZIP file.
		$zip = new ZipArchive();
		if ( $zip->open( $zip_path, ZipArchive::CREATE ) !== true ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create export file.', 'syncpoint-crm' ) ) );
			return;
		}

		// Export each table.
		$tables = array(
			'scrm_contacts'      => 'contacts.csv',
			'scrm_companies'     => 'companies.csv',
			'scrm_transactions'  => 'transactions.csv',
			'scrm_invoices'      => 'invoices.csv',
			'scrm_invoice_items' => 'invoice_items.csv',
			'scrm_tags'          => 'tags.csv',
			'scrm_activity_log'  => 'activity_log.csv',
			'scrm_email_log'     => 'email_log.csv',
		);

		foreach ( $tables as $table => $filename ) {
			$full_table = $wpdb->prefix . $table;
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table ) ) === $full_table;

			if ( ! $exists ) {
				continue;
			}

			$rows = $wpdb->get_results( "SELECT * FROM {$full_table}", ARRAY_A );

			if ( empty( $rows ) ) {
				continue;
			}

			// Generate CSV content.
			$csv = fopen( 'php://temp', 'r+' );
			fputcsv( $csv, array_keys( $rows[0] ) );

			foreach ( $rows as $row ) {
				fputcsv( $csv, $row );
			}

			rewind( $csv );
			$csv_content = stream_get_contents( $csv );
			fclose( $csv );

			$zip->addFromString( $filename, $csv_content );
		}

		$zip->close();

		$download_url = $upload_dir['baseurl'] . '/scrm-exports/' . $zip_filename;

		wp_send_json_success( array(
			'message'      => __( 'Export completed.', 'syncpoint-crm' ),
			'download_url' => $download_url,
		) );
	}
}

// Initialize.
new SCRM_AJAX();
