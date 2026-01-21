<?php
/**
 * CSV Importer
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Class CSV_Importer
 *
 * Handles CSV file imports.
 *
 * @since 1.0.0
 */
class CSV_Importer {

	/**
	 * File path.
	 *
	 * @var string
	 */
	private $file_path = '';

	/**
	 * Field mapping.
	 *
	 * @var array
	 */
	private $mapping = array();

	/**
	 * Import type.
	 *
	 * @var string
	 */
	private $import_type = 'contacts';

	/**
	 * Delimiter.
	 *
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * Headers.
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * Results.
	 *
	 * @var array
	 */
	private $results = array(
		'imported' => 0,
		'updated'  => 0,
		'skipped'  => 0,
		'errors'   => array(),
	);

	/**
	 * Constructor.
	 *
	 * @param string $file_path Path to CSV file.
	 */
	public function __construct( $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * Set field mapping.
	 *
	 * @param array $mapping Column index => field name.
	 */
	public function set_mapping( $mapping ) {
		$this->mapping = $mapping;
	}

	/**
	 * Set import type.
	 *
	 * @param string $type Import type (contacts, companies, transactions).
	 */
	public function set_import_type( $type ) {
		$this->import_type = $type;
	}

	/**
	 * Set delimiter.
	 *
	 * @param string $delimiter CSV delimiter.
	 */
	public function set_delimiter( $delimiter ) {
		$this->delimiter = $delimiter;
	}

	/**
	 * Get file headers.
	 *
	 * @return array|WP_Error Headers or error.
	 */
	public function get_headers() {
		if ( ! file_exists( $this->file_path ) ) {
			return new \WP_Error( 'file_not_found', __( 'File not found.', 'syncpoint-crm' ) );
		}

		$handle = fopen( $this->file_path, 'r' );
		if ( ! $handle ) {
			return new \WP_Error( 'cannot_open', __( 'Cannot open file.', 'syncpoint-crm' ) );
		}

		$this->headers = fgetcsv( $handle, 0, $this->delimiter );
		fclose( $handle );

		return $this->headers;
	}

	/**
	 * Get preview rows.
	 *
	 * @param int $count Number of rows to preview.
	 * @return array Preview rows.
	 */
	public function get_preview( $count = 5 ) {
		if ( ! file_exists( $this->file_path ) ) {
			return array();
		}

		$handle = fopen( $this->file_path, 'r' );
		if ( ! $handle ) {
			return array();
		}

		// Skip header.
		fgetcsv( $handle, 0, $this->delimiter );

		$rows = array();
		$i = 0;

		while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== false && $i < $count ) {
			$rows[] = $row;
			$i++;
		}

		fclose( $handle );

		return $rows;
	}

	/**
	 * Run import.
	 *
	 * @param array $options Import options.
	 * @return array Import results.
	 */
	public function run( $options = array() ) {
		$defaults = array(
			'skip_duplicates' => true,
			'update_existing' => false,
			'batch_size'      => 100,
		);

		$options = wp_parse_args( $options, $defaults );

		if ( ! file_exists( $this->file_path ) ) {
			$this->results['errors'][] = __( 'File not found.', 'syncpoint-crm' );
			return $this->results;
		}

		$handle = fopen( $this->file_path, 'r' );
		if ( ! $handle ) {
			$this->results['errors'][] = __( 'Cannot open file.', 'syncpoint-crm' );
			return $this->results;
		}

		// Read and skip header.
		$this->headers = fgetcsv( $handle, 0, $this->delimiter );

		$row_number = 1;

		while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== false ) {
			$row_number++;

			$result = $this->import_row( $row, $options, $row_number );

			if ( 'imported' === $result ) {
				$this->results['imported']++;
			} elseif ( 'updated' === $result ) {
				$this->results['updated']++;
			} elseif ( 'skipped' === $result ) {
				$this->results['skipped']++;
			}
		}

		fclose( $handle );

		return $this->results;
	}

	/**
	 * Import a single row.
	 *
	 * @param array $row        Row data.
	 * @param array $options    Import options.
	 * @param int   $row_number Row number for error reporting.
	 * @return string Result (imported, updated, skipped, error).
	 */
	private function import_row( $row, $options, $row_number ) {
		$data = $this->map_row( $row );

		if ( empty( $data ) ) {
			return 'skipped';
		}

		switch ( $this->import_type ) {
			case 'contacts':
				return $this->import_contact( $data, $options, $row_number );

			case 'companies':
				return $this->import_company( $data, $options, $row_number );

			case 'transactions':
				return $this->import_transaction( $data, $options, $row_number );

			default:
				return 'skipped';
		}
	}

	/**
	 * Map row data using field mapping.
	 *
	 * @param array $row Raw row data.
	 * @return array Mapped data.
	 */
	private function map_row( $row ) {
		$data = array();

		foreach ( $this->mapping as $column_index => $field_name ) {
			if ( empty( $field_name ) ) {
				continue;
			}

			$value = isset( $row[ $column_index ] ) ? trim( $row[ $column_index ] ) : '';

			// Handle special fields.
			if ( 'tags' === $field_name && ! empty( $value ) ) {
				$data['tags'] = array_map( 'trim', explode( ',', $value ) );
			} else {
				$data[ $field_name ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Import a contact.
	 *
	 * @param array $data       Contact data.
	 * @param array $options    Import options.
	 * @param int   $row_number Row number.
	 * @return string Result.
	 */
	private function import_contact( $data, $options, $row_number ) {
		// Email is required.
		if ( empty( $data['email'] ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %d: row number */
				__( 'Row %d: Missing email address.', 'syncpoint-crm' ),
				$row_number
			);
			return 'error';
		}

		// Validate email.
		if ( ! is_email( $data['email'] ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %1$d: row number, %2$s: email */
				__( 'Row %1$d: Invalid email "%2$s".', 'syncpoint-crm' ),
				$row_number,
				$data['email']
			);
			return 'error';
		}

		// Check for existing contact.
		$existing = scrm_get_contact_by_email( $data['email'] );

		if ( $existing ) {
			if ( $options['update_existing'] ) {
				$result = scrm_update_contact( $existing->id, $data );

				if ( is_wp_error( $result ) ) {
					$this->results['errors'][] = sprintf(
						/* translators: %1$d: row number, %2$s: error */
						__( 'Row %1$d: %2$s', 'syncpoint-crm' ),
						$row_number,
						$result->get_error_message()
					);
					return 'error';
				}

				return 'updated';
			}

			if ( $options['skip_duplicates'] ) {
				return 'skipped';
			}
		}

		// Set defaults.
		if ( empty( $data['type'] ) ) {
			$data['type'] = 'customer';
		}

		if ( empty( $data['status'] ) ) {
			$data['status'] = 'active';
		}

		$data['source'] = 'import';

		// Handle company creation.
		if ( ! empty( $data['company_name'] ) ) {
			$company_id = $this->find_or_create_company( $data['company_name'] );
			if ( $company_id ) {
				$data['company_id'] = $company_id;
			}
			unset( $data['company_name'] );
		}

		$result = scrm_create_contact( $data );

		if ( is_wp_error( $result ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %1$d: row number, %2$s: error */
				__( 'Row %1$d: %2$s', 'syncpoint-crm' ),
				$row_number,
				$result->get_error_message()
			);
			return 'error';
		}

		return 'imported';
	}

	/**
	 * Import a company.
	 *
	 * @param array $data       Company data.
	 * @param array $options    Import options.
	 * @param int   $row_number Row number.
	 * @return string Result.
	 */
	private function import_company( $data, $options, $row_number ) {
		if ( empty( $data['name'] ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %d: row number */
				__( 'Row %d: Missing company name.', 'syncpoint-crm' ),
				$row_number
			);
			return 'error';
		}

		// Check for existing by name.
		global $wpdb;
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}scrm_companies WHERE name = %s",
			$data['name']
		) );

		if ( $existing ) {
			if ( $options['update_existing'] ) {
				$result = scrm_update_company( $existing->id, $data );

				if ( is_wp_error( $result ) ) {
					$this->results['errors'][] = sprintf(
						/* translators: %1$d: row number, %2$s: error */
						__( 'Row %1$d: %2$s', 'syncpoint-crm' ),
						$row_number,
						$result->get_error_message()
					);
					return 'error';
				}

				return 'updated';
			}

			if ( $options['skip_duplicates'] ) {
				return 'skipped';
			}
		}

		$result = scrm_create_company( $data );

		if ( is_wp_error( $result ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %1$d: row number, %2$s: error */
				__( 'Row %1$d: %2$s', 'syncpoint-crm' ),
				$row_number,
				$result->get_error_message()
			);
			return 'error';
		}

		return 'imported';
	}

	/**
	 * Import a transaction.
	 *
	 * @param array $data       Transaction data.
	 * @param array $options    Import options.
	 * @param int   $row_number Row number.
	 * @return string Result.
	 */
	private function import_transaction( $data, $options, $row_number ) {
		// Contact identifier required.
		if ( empty( $data['contact_email'] ) && empty( $data['contact_id'] ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %d: row number */
				__( 'Row %d: Missing contact identifier.', 'syncpoint-crm' ),
				$row_number
			);
			return 'error';
		}

		// Find contact.
		$contact = null;
		if ( ! empty( $data['contact_email'] ) ) {
			$contact = scrm_get_contact_by_email( $data['contact_email'] );
		} elseif ( ! empty( $data['contact_id'] ) ) {
			$contact = scrm_get_contact( $data['contact_id'] );
		}

		if ( ! $contact ) {
			$this->results['errors'][] = sprintf(
				/* translators: %d: row number */
				__( 'Row %d: Contact not found.', 'syncpoint-crm' ),
				$row_number
			);
			return 'error';
		}

		// Amount required.
		if ( empty( $data['amount'] ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %d: row number */
				__( 'Row %d: Missing amount.', 'syncpoint-crm' ),
				$row_number
			);
			return 'error';
		}

		$txn_data = array(
			'contact_id'  => $contact->id,
			'amount'      => floatval( $data['amount'] ),
			'type'        => $data['type'] ?? 'payment',
			'gateway'     => $data['gateway'] ?? 'import',
			'currency'    => $data['currency'] ?? $contact->currency,
			'status'      => $data['status'] ?? 'completed',
			'description' => $data['description'] ?? '',
		);

		if ( ! empty( $data['gateway_transaction_id'] ) ) {
			$txn_data['gateway_transaction_id'] = $data['gateway_transaction_id'];
		}

		$result = scrm_create_transaction( $txn_data );

		if ( is_wp_error( $result ) ) {
			$this->results['errors'][] = sprintf(
				/* translators: %1$d: row number, %2$s: error */
				__( 'Row %1$d: %2$s', 'syncpoint-crm' ),
				$row_number,
				$result->get_error_message()
			);
			return 'error';
		}

		return 'imported';
	}

	/**
	 * Find or create a company by name.
	 *
	 * @param string $name Company name.
	 * @return int|null Company ID or null.
	 */
	private function find_or_create_company( $name ) {
		global $wpdb;

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}scrm_companies WHERE name = %s",
			$name
		) );

		if ( $existing ) {
			return (int) $existing;
		}

		$result = scrm_create_company( array( 'name' => $name ) );

		if ( is_wp_error( $result ) ) {
			return null;
		}

		return $result;
	}

	/**
	 * Get results.
	 *
	 * @return array Import results.
	 */
	public function get_results() {
		return $this->results;
	}
}
