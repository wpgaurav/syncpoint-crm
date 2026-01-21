<?php
/**
 * CSV Exporter
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Class CSV_Exporter
 *
 * Handles CSV file exports.
 *
 * @since 1.0.0
 */
class CSV_Exporter {

	/**
	 * Export type.
	 *
	 * @var string
	 */
	private $export_type = 'contacts';

	/**
	 * Query args.
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * Columns to export.
	 *
	 * @var array
	 */
	private $columns = array();

	/**
	 * Delimiter.
	 *
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * Constructor.
	 *
	 * @param string $export_type Export type.
	 */
	public function __construct( $export_type = 'contacts' ) {
		$this->export_type = $export_type;
		$this->set_default_columns();
	}

	/**
	 * Set default columns based on export type.
	 */
	private function set_default_columns() {
		switch ( $this->export_type ) {
			case 'contacts':
				$this->columns = array(
					'contact_id'     => __( 'ID', 'syncpoint-crm' ),
					'first_name'     => __( 'First Name', 'syncpoint-crm' ),
					'last_name'      => __( 'Last Name', 'syncpoint-crm' ),
					'email'          => __( 'Email', 'syncpoint-crm' ),
					'phone'          => __( 'Phone', 'syncpoint-crm' ),
					'type'           => __( 'Type', 'syncpoint-crm' ),
					'status'         => __( 'Status', 'syncpoint-crm' ),
					'company_name'   => __( 'Company', 'syncpoint-crm' ),
					'currency'       => __( 'Currency', 'syncpoint-crm' ),
					'address_line_1' => __( 'Address', 'syncpoint-crm' ),
					'city'           => __( 'City', 'syncpoint-crm' ),
					'state'          => __( 'State', 'syncpoint-crm' ),
					'postal_code'    => __( 'Postal Code', 'syncpoint-crm' ),
					'country'        => __( 'Country', 'syncpoint-crm' ),
					'tags'           => __( 'Tags', 'syncpoint-crm' ),
					'source'         => __( 'Source', 'syncpoint-crm' ),
					'created_at'     => __( 'Created', 'syncpoint-crm' ),
				);
				break;

			case 'companies':
				$this->columns = array(
					'company_id'     => __( 'ID', 'syncpoint-crm' ),
					'name'           => __( 'Name', 'syncpoint-crm' ),
					'email'          => __( 'Email', 'syncpoint-crm' ),
					'phone'          => __( 'Phone', 'syncpoint-crm' ),
					'website'        => __( 'Website', 'syncpoint-crm' ),
					'industry'       => __( 'Industry', 'syncpoint-crm' ),
					'address_line_1' => __( 'Address', 'syncpoint-crm' ),
					'city'           => __( 'City', 'syncpoint-crm' ),
					'state'          => __( 'State', 'syncpoint-crm' ),
					'postal_code'    => __( 'Postal Code', 'syncpoint-crm' ),
					'country'        => __( 'Country', 'syncpoint-crm' ),
					'created_at'     => __( 'Created', 'syncpoint-crm' ),
				);
				break;

			case 'transactions':
				$this->columns = array(
					'transaction_id' => __( 'ID', 'syncpoint-crm' ),
					'contact_email'  => __( 'Contact Email', 'syncpoint-crm' ),
					'contact_name'   => __( 'Contact Name', 'syncpoint-crm' ),
					'type'           => __( 'Type', 'syncpoint-crm' ),
					'gateway'        => __( 'Gateway', 'syncpoint-crm' ),
					'amount'         => __( 'Amount', 'syncpoint-crm' ),
					'currency'       => __( 'Currency', 'syncpoint-crm' ),
					'status'         => __( 'Status', 'syncpoint-crm' ),
					'description'    => __( 'Description', 'syncpoint-crm' ),
					'created_at'     => __( 'Date', 'syncpoint-crm' ),
				);
				break;

			case 'invoices':
				$this->columns = array(
					'invoice_number' => __( 'Invoice #', 'syncpoint-crm' ),
					'contact_email'  => __( 'Contact Email', 'syncpoint-crm' ),
					'contact_name'   => __( 'Contact Name', 'syncpoint-crm' ),
					'status'         => __( 'Status', 'syncpoint-crm' ),
					'issue_date'     => __( 'Issue Date', 'syncpoint-crm' ),
					'due_date'       => __( 'Due Date', 'syncpoint-crm' ),
					'subtotal'       => __( 'Subtotal', 'syncpoint-crm' ),
					'tax_amount'     => __( 'Tax', 'syncpoint-crm' ),
					'total'          => __( 'Total', 'syncpoint-crm' ),
					'currency'       => __( 'Currency', 'syncpoint-crm' ),
					'paid_at'        => __( 'Paid Date', 'syncpoint-crm' ),
				);
				break;
		}
	}

	/**
	 * Set columns to export.
	 *
	 * @param array $columns Column keys.
	 */
	public function set_columns( $columns ) {
		$this->columns = $columns;
	}

	/**
	 * Set query arguments.
	 *
	 * @param array $args Query arguments.
	 */
	public function set_args( $args ) {
		$this->args = $args;
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
	 * Generate CSV content.
	 *
	 * @return string CSV content.
	 */
	public function generate() {
		$data = $this->get_data();

		$output = fopen( 'php://temp', 'r+' );

		// Write headers.
		fputcsv( $output, array_values( $this->columns ), $this->delimiter );

		// Write data rows.
		foreach ( $data as $row ) {
			$csv_row = array();

			foreach ( array_keys( $this->columns ) as $column ) {
				$csv_row[] = $row[ $column ] ?? '';
			}

			fputcsv( $output, $csv_row, $this->delimiter );
		}

		rewind( $output );
		$content = stream_get_contents( $output );
		fclose( $output );

		return $content;
	}

	/**
	 * Stream CSV download.
	 *
	 * @param string $filename Filename.
	 */
	public function download( $filename = '' ) {
		if ( empty( $filename ) ) {
			$filename = $this->export_type . '-' . date( 'Y-m-d' ) . '.csv';
		}

		$content = $this->generate();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Save to file.
	 *
	 * @param string $file_path File path.
	 * @return bool True on success.
	 */
	public function save( $file_path ) {
		$content = $this->generate();
		return (bool) file_put_contents( $file_path, $content );
	}

	/**
	 * Get data based on export type.
	 *
	 * @return array Export data.
	 */
	private function get_data() {
		switch ( $this->export_type ) {
			case 'contacts':
				return $this->get_contacts_data();

			case 'companies':
				return $this->get_companies_data();

			case 'transactions':
				return $this->get_transactions_data();

			case 'invoices':
				return $this->get_invoices_data();

			default:
				return array();
		}
	}

	/**
	 * Get contacts data.
	 *
	 * @return array Contacts data.
	 */
	private function get_contacts_data() {
		$args = wp_parse_args( $this->args, array(
			'limit' => -1,
		) );

		$contacts = scrm_get_contacts( $args );
		$data = array();

		foreach ( $contacts as $contact ) {
			$company = $contact->company_id ? scrm_get_company( $contact->company_id ) : null;
			$tags = scrm_get_object_tags( $contact->id, 'contact' );
			$tag_names = wp_list_pluck( $tags, 'name' );

			$data[] = array(
				'contact_id'     => $contact->contact_id,
				'first_name'     => $contact->first_name,
				'last_name'      => $contact->last_name,
				'email'          => $contact->email,
				'phone'          => $contact->phone,
				'type'           => $contact->type,
				'status'         => $contact->status,
				'company_name'   => $company ? $company->name : '',
				'currency'       => $contact->currency,
				'address_line_1' => $contact->address_line_1,
				'city'           => $contact->city,
				'state'          => $contact->state,
				'postal_code'    => $contact->postal_code,
				'country'        => $contact->country,
				'tags'           => implode( ', ', $tag_names ),
				'source'         => $contact->source,
				'created_at'     => $contact->created_at,
			);
		}

		return $data;
	}

	/**
	 * Get companies data.
	 *
	 * @return array Companies data.
	 */
	private function get_companies_data() {
		$args = wp_parse_args( $this->args, array(
			'limit' => -1,
		) );

		$companies = scrm_get_companies( $args );
		$data = array();

		foreach ( $companies as $company ) {
			$data[] = array(
				'company_id'     => $company->company_id,
				'name'           => $company->name,
				'email'          => $company->email,
				'phone'          => $company->phone,
				'website'        => $company->website,
				'industry'       => $company->industry,
				'address_line_1' => $company->address_line_1,
				'city'           => $company->city,
				'state'          => $company->state,
				'postal_code'    => $company->postal_code,
				'country'        => $company->country,
				'created_at'     => $company->created_at,
			);
		}

		return $data;
	}

	/**
	 * Get transactions data.
	 *
	 * @return array Transactions data.
	 */
	private function get_transactions_data() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';

		$sql = "SELECT * FROM {$table} ORDER BY created_at DESC";

		if ( ! empty( $this->args['limit'] ) && $this->args['limit'] > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $this->args['limit'] );
		}

		$transactions = $wpdb->get_results( $sql );
		$data = array();

		foreach ( $transactions as $txn ) {
			$contact = scrm_get_contact( $txn->contact_id );

			$data[] = array(
				'transaction_id' => $txn->transaction_id,
				'contact_email'  => $contact ? $contact->email : '',
				'contact_name'   => $contact ? trim( $contact->first_name . ' ' . $contact->last_name ) : '',
				'type'           => $txn->type,
				'gateway'        => $txn->gateway,
				'amount'         => $txn->amount,
				'currency'       => $txn->currency,
				'status'         => $txn->status,
				'description'    => $txn->description,
				'created_at'     => $txn->created_at,
			);
		}

		return $data;
	}

	/**
	 * Get invoices data.
	 *
	 * @return array Invoices data.
	 */
	private function get_invoices_data() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		$sql = "SELECT * FROM {$table} ORDER BY created_at DESC";

		if ( ! empty( $this->args['limit'] ) && $this->args['limit'] > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $this->args['limit'] );
		}

		$invoices = $wpdb->get_results( $sql );
		$data = array();

		foreach ( $invoices as $invoice ) {
			$contact = scrm_get_contact( $invoice->contact_id );

			$data[] = array(
				'invoice_number' => $invoice->invoice_number,
				'contact_email'  => $contact ? $contact->email : '',
				'contact_name'   => $contact ? trim( $contact->first_name . ' ' . $contact->last_name ) : '',
				'status'         => $invoice->status,
				'issue_date'     => $invoice->issue_date,
				'due_date'       => $invoice->due_date,
				'subtotal'       => $invoice->subtotal,
				'tax_amount'     => $invoice->tax_amount,
				'total'          => $invoice->total,
				'currency'       => $invoice->currency,
				'paid_at'        => $invoice->paid_at,
			);
		}

		return $data;
	}
}
