<?php
/**
 * CSV Importer
 *
 * @package SyncPointCRM
 * @since 1.0.0
 */

namespace SCRM\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Class CSV_Importer
 *
 * Handles CSV file parsing and data import.
 *
 * @since 1.0.0
 */
class CSV_Importer {

	/**
	 * File path.
	 *
	 * @var string
	 */
	private $file_path;

	/**
	 * Import type.
	 *
	 * @var string
	 */
	private $import_type = 'contacts';

	/**
	 * Field mapping.
	 *
	 * @var array
	 */
	private $mapping = array();

	/**
	 * CSV headers.
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * Constructor.
	 *
	 * @param string $file_path Path to CSV file.
	 */
	public function __construct( $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * Set import type.
	 *
	 * @param string $type Import type.
	 */
	public function set_import_type( $type ) {
		$this->import_type = $type;
	}

	/**
	 * Set field mapping.
	 *
	 * @param array $mapping Column to field mapping.
	 */
	public function set_mapping( $mapping ) {
		$this->mapping = $mapping;
	}

	/**
	 * Get CSV headers.
	 *
	 * @return array|\WP_Error
	 */
	public function get_headers() {
		if ( ! file_exists( $this->file_path ) ) {
			return new \WP_Error( 'file_not_found', __( 'CSV file not found.', 'syncpoint-crm' ) );
		}

		$handle = fopen( $this->file_path, 'r' );
		if ( ! $handle ) {
			return new \WP_Error( 'file_open_error', __( 'Could not open CSV file.', 'syncpoint-crm' ) );
		}

		$headers = fgetcsv( $handle );
		fclose( $handle );

		if ( ! $headers ) {
			return new \WP_Error( 'empty_file', __( 'CSV file is empty.', 'syncpoint-crm' ) );
		}

		$this->headers = $headers;
		return $headers;
	}

	/**
	 * Get preview rows.
	 *
	 * @param int $count Number of rows.
	 * @return array
	 */
	public function get_preview( $count = 5 ) {
		if ( ! file_exists( $this->file_path ) ) {
			return array();
		}

		$handle = fopen( $this->file_path, 'r' );
		if ( ! $handle ) {
			return array();
		}

		$rows = array();
		$i    = 0;

		// Skip header.
		fgetcsv( $handle );

		while ( ( $row = fgetcsv( $handle ) ) !== false && $i < $count ) {
			$rows[] = $row;
			++$i;
		}

		fclose( $handle );
		return $rows;
	}

	/**
	 * Run the import.
	 *
	 * @param array $options Import options.
	 * @return array
	 */
	public function run( $options = array() ) {
		$defaults = array(
			'skip_duplicates' => true,
			'update_existing' => false,
		);

		$options = wp_parse_args( $options, $defaults );

		if ( ! file_exists( $this->file_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'CSV file not found.', 'syncpoint-crm' ),
			);
		}

		$handle = fopen( $this->file_path, 'r' );
		if ( ! $handle ) {
			return array(
				'success' => false,
				'message' => __( 'Could not open CSV file.', 'syncpoint-crm' ),
			);
		}

		// Skip header row.
		$this->headers = fgetcsv( $handle );

		$stats = array(
			'total'   => 0,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			++$stats['total'];

			$result = $this->process_row( $row, $options );

			if ( is_wp_error( $result ) ) {
				++$stats['errors'];
			} elseif ( 'created' === $result ) {
				++$stats['created'];
			} elseif ( 'updated' === $result ) {
				++$stats['updated'];
			} else {
				++$stats['skipped'];
			}
		}

		fclose( $handle );

		return array(
			'success' => true,
			'stats'   => $stats,
		);
	}

	/**
	 * Process a single row.
	 *
	 * @param array $row     CSV row data.
	 * @param array $options Import options.
	 * @return string|\WP_Error
	 */
	private function process_row( $row, $options ) {
		$data = $this->map_row_to_data( $row );

		if ( empty( $data ) ) {
			return 'skipped';
		}

		switch ( $this->import_type ) {
			case 'contacts':
				return $this->import_contact( $data, $options );
			case 'companies':
				return $this->import_company( $data, $options );
			case 'transactions':
				return $this->import_transaction( $data, $options );
			default:
				return 'skipped';
		}
	}

	/**
	 * Map row data using field mapping.
	 *
	 * @param array $row CSV row.
	 * @return array
	 */
	private function map_row_to_data( $row ) {
		$data = array();

		foreach ( $this->mapping as $column_index => $field_name ) {
			if ( empty( $field_name ) || ! isset( $row[ $column_index ] ) ) {
				continue;
			}

			$value = trim( $row[ $column_index ] );

			if ( '' !== $value ) {
				$data[ $field_name ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Import a contact.
	 *
	 * @param array $data    Contact data.
	 * @param array $options Import options.
	 * @return string|\WP_Error
	 */
	private function import_contact( $data, $options ) {
		if ( empty( $data['email'] ) ) {
			return new \WP_Error( 'missing_email', __( 'Email is required.', 'syncpoint-crm' ) );
		}

		$email    = sanitize_email( $data['email'] );
		$existing = scrm_get_contact_by_email( $email );

		if ( $existing ) {
			if ( $options['skip_duplicates'] && ! $options['update_existing'] ) {
				return 'skipped';
			}

			if ( $options['update_existing'] ) {
				$update_data = $this->sanitize_contact_data( $data );
				unset( $update_data['email'] );

				$result = scrm_update_contact( $existing->id, $update_data );
				return is_wp_error( $result ) ? $result : 'updated';
			}

			return 'skipped';
		}

		$contact_data = $this->sanitize_contact_data( $data );
		$contact_id   = scrm_create_contact( $contact_data );

		return is_wp_error( $contact_id ) ? $contact_id : 'created';
	}

	/**
	 * Sanitize contact data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private function sanitize_contact_data( $data ) {
		$sanitized = array();

		$text_fields = array( 'first_name', 'last_name', 'phone', 'type', 'status', 'source', 'city', 'state', 'postal_code', 'country' );

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		if ( isset( $data['email'] ) ) {
			$sanitized['email'] = sanitize_email( $data['email'] );
		}

		if ( empty( $sanitized['type'] ) ) {
			$sanitized['type'] = 'customer';
		}

		if ( empty( $sanitized['status'] ) ) {
			$sanitized['status'] = 'active';
		}

		if ( empty( $sanitized['source'] ) ) {
			$sanitized['source'] = 'csv_import';
		}

		return $sanitized;
	}

	/**
	 * Import a company.
	 *
	 * @param array $data    Company data.
	 * @param array $options Import options.
	 * @return string|\WP_Error
	 */
	private function import_company( $data, $options ) {
		if ( empty( $data['name'] ) ) {
			return new \WP_Error( 'missing_name', __( 'Company name is required.', 'syncpoint-crm' ) );
		}

		$name = sanitize_text_field( $data['name'] );

		global $wpdb;
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}scrm_companies WHERE name = %s",
				$name
			)
		);

		if ( $existing ) {
			if ( $options['skip_duplicates'] && ! $options['update_existing'] ) {
				return 'skipped';
			}

			if ( $options['update_existing'] ) {
				$update_data = $this->sanitize_company_data( $data );
				unset( $update_data['name'] );

				$wpdb->upgmdate(
					$wpdb->prefix . 'scrm_companies',
					$update_data,
					array( 'id' => $existing->id )
				);

				return 'updated';
			}

			return 'skipped';
		}

		$company_data = $this->sanitize_company_data( $data );
		$result       = scrm_create_company( $company_data );

		return is_wp_error( $result ) ? $result : 'created';
	}

	/**
	 * Sanitize company data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private function sanitize_company_data( $data ) {
		$sanitized = array();

		$text_fields = array( 'name', 'phone', 'city', 'state', 'postal_code', 'country', 'industry' );

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		if ( isset( $data['email'] ) ) {
			$sanitized['email'] = sanitize_email( $data['email'] );
		}

		if ( isset( $data['website'] ) ) {
			$sanitized['website'] = esc_url_raw( $data['website'] );
		}

		return $sanitized;
	}

	/**
	 * Import a transaction.
	 *
	 * @param array $data    Transaction data.
	 * @param array $options Import options.
	 * @return string|\WP_Error
	 */
	private function import_transaction( $data, $options ) {
		if ( empty( $data['contact_email'] ) && empty( $data['contact_id'] ) ) {
			return new \WP_Error( 'missing_contact', __( 'Contact email or ID is required.', 'syncpoint-crm' ) );
		}

		$contact = null;

		if ( ! empty( $data['contact_email'] ) ) {
			$contact = scrm_get_contact_by_email( sanitize_email( $data['contact_email'] ) );
		} elseif ( ! empty( $data['contact_id'] ) ) {
			$contact = scrm_get_contact( absint( $data['contact_id'] ) );
		}

		if ( ! $contact ) {
			return new \WP_Error( 'contact_not_found', __( 'Contact not found.', 'syncpoint-crm' ) );
		}

		$txn_data = array(
			'contact_id'  => $contact->id,
			'type'        => sanitize_text_field( $data['type'] ?? 'payment' ),
			'gateway'     => sanitize_text_field( $data['gateway'] ?? 'import' ),
			'amount'      => floatval( $data['amount'] ?? 0 ),
			'currency'    => sanitize_text_field( $data['currency'] ?? scrm_get_default_currency() ),
			'status'      => sanitize_text_field( $data['status'] ?? 'completed' ),
			'description' => sanitize_text_field( $data['description'] ?? '' ),
		);

		$result = scrm_create_transaction( $txn_data );

		return is_wp_error( $result ) ? $result : 'created';
	}
}
