<?php
/**
 * Transaction Model
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Transaction
 *
 * @since 1.0.0
 */
class Transaction {

	/**
	 * Transaction ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Custom transaction ID.
	 *
	 * @var string
	 */
	public $transaction_id = '';

	/**
	 * Contact ID.
	 *
	 * @var int
	 */
	public $contact_id = 0;

	/**
	 * Invoice ID.
	 *
	 * @var int|null
	 */
	public $invoice_id = null;

	/**
	 * Transaction type.
	 *
	 * @var string
	 */
	public $type = 'payment';

	/**
	 * Payment gateway.
	 *
	 * @var string
	 */
	public $gateway = 'manual';

	/**
	 * Gateway transaction ID.
	 *
	 * @var string
	 */
	public $gateway_transaction_id = '';

	/**
	 * Amount.
	 *
	 * @var float
	 */
	public $amount = 0.00;

	/**
	 * Currency.
	 *
	 * @var string
	 */
	public $currency = 'USD';

	/**
	 * Status.
	 *
	 * @var string
	 */
	public $status = 'pending';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Metadata.
	 *
	 * @var array
	 */
	public $metadata = array();

	/**
	 * Created at.
	 *
	 * @var string
	 */
	public $created_at = '';

	/**
	 * Updated at.
	 *
	 * @var string
	 */
	public $updated_at = '';

	/**
	 * Constructor.
	 *
	 * @param int|object $transaction Transaction ID or object.
	 */
	public function __construct( $transaction = 0 ) {
		if ( is_numeric( $transaction ) && $transaction > 0 ) {
			$this->read( $transaction );
		} elseif ( is_object( $transaction ) ) {
			$this->set_props( $transaction );
		}
	}

	/**
	 * Read transaction from database.
	 *
	 * @param int $id Transaction ID.
	 */
	public function read( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);

		if ( $row ) {
			$this->set_props( $row );
		}
	}

	/**
	 * Set properties from object.
	 *
	 * @param object $data Data object.
	 */
	public function set_props( $data ) {
		$this->id                     = (int) $data->id;
		$this->transaction_id         = $data->transaction_id;
		$this->contact_id             = (int) $data->contact_id;
		$this->invoice_id             = $data->invoice_id ? (int) $data->invoice_id : null;
		$this->type                   = $data->type;
		$this->gateway                = $data->gateway;
		$this->gateway_transaction_id = $data->gateway_transaction_id;
		$this->amount                 = floatval( $data->amount );
		$this->currency               = $data->currency;
		$this->status                 = $data->status;
		$this->description            = $data->description;
		$this->metadata               = is_string( $data->metadata ) ? json_decode( $data->metadata, true ) : $data->metadata;
		$this->created_at             = $data->created_at;
		$this->updated_at             = $data->updated_at;

		if ( ! is_array( $this->metadata ) ) {
			$this->metadata = array();
		}
	}

	/**
	 * Save transaction.
	 *
	 * @return int|\WP_Error Transaction ID or error.
	 */
	public function save() {
		if ( $this->id ) {
			return $this->upgmdate();
		}
		return $this->create();
	}

	/**
	 * Create new transaction.
	 *
	 * @return int|\WP_Error Transaction ID or error.
	 */
	public function create() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';

		if ( empty( $this->contact_id ) ) {
			return new \WP_Error( 'missing_contact', __( 'Contact ID is required.', 'syncpoint-crm' ) );
		}

		if ( empty( $this->amount ) ) {
			return new \WP_Error( 'missing_amount', __( 'Amount is required.', 'syncpoint-crm' ) );
		}

		if ( empty( $this->transaction_id ) ) {
			$this->transaction_id = scrm_generate_id( 'transaction' );
		}

		if ( empty( $this->currency ) ) {
			$this->currency = scrm_get_default_currency();
		}

		$data = array(
			'transaction_id'         => $this->transaction_id,
			'contact_id'             => $this->contact_id,
			'invoice_id'             => $this->invoice_id,
			'type'                   => $this->type,
			'gateway'                => $this->gateway,
			'gateway_transaction_id' => $this->gateway_transaction_id,
			'amount'                 => $this->amount,
			'currency'               => $this->currency,
			'status'                 => $this->status,
			'description'            => $this->description,
			'metadata'               => wp_json_encode( $this->metadata ),
			'created_at'             => current_time( 'mysql' ),
			'updated_at'             => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		$this->id = $wpdb->insert_id;

		do_action( 'scrm_transaction_created', $this->id, $this->to_array() );

		// Fire payment received for completed payments.
		if ( 'payment' === $this->type && 'completed' === $this->status ) {
			do_action( 'scrm_payment_received', $this->id, $this->contact_id, $this->amount, $this->currency );
		}

		return $this->id;
	}

	/**
	 * Update transaction.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function upgmdate() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';

		$data = array(
			'status'      => $this->status,
			'description' => $this->description,
			'metadata'    => wp_json_encode( $this->metadata ),
			'updated_at'  => current_time( 'mysql' ),
		);

		$result = $wpdb->upgmdate( $table, $data, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		do_action( 'scrm_transaction_updated', $this->id, $this->to_array() );

		return true;
	}

	/**
	 * Get contact.
	 *
	 * @return Contact Contact object.
	 */
	public function get_contact() {
		return new Contact( $this->contact_id );
	}

	/**
	 * Get invoice.
	 *
	 * @return Invoice|null Invoice object or null.
	 */
	public function get_invoice() {
		if ( ! $this->invoice_id ) {
			return null;
		}
		return new Invoice( $this->invoice_id );
	}

	/**
	 * Get formatted amount.
	 *
	 * @return string Formatted amount.
	 */
	public function get_formatted_amount() {
		return scrm_format_currency( $this->amount, $this->currency );
	}

	/**
	 * Get type label.
	 *
	 * @return string Type label.
	 */
	public function get_type_label() {
		$types = scrm_get_transaction_types();
		return isset( $types[ $this->type ] ) ? $types[ $this->type ] : $this->type;
	}

	/**
	 * Get status label.
	 *
	 * @return string Status label.
	 */
	public function get_status_label() {
		$statuses = array(
			'pending'   => __( 'Pending', 'syncpoint-crm' ),
			'completed' => __( 'Completed', 'syncpoint-crm' ),
			'failed'    => __( 'Failed', 'syncpoint-crm' ),
			'refunded'  => __( 'Refunded', 'syncpoint-crm' ),
		);
		return isset( $statuses[ $this->status ] ) ? $statuses[ $this->status ] : $this->status;
	}

	/**
	 * Convert to array.
	 *
	 * @return array Transaction data.
	 */
	public function to_array() {
		return array(
			'id'                     => $this->id,
			'transaction_id'         => $this->transaction_id,
			'contact_id'             => $this->contact_id,
			'invoice_id'             => $this->invoice_id,
			'type'                   => $this->type,
			'gateway'                => $this->gateway,
			'gateway_transaction_id' => $this->gateway_transaction_id,
			'amount'                 => $this->amount,
			'currency'               => $this->currency,
			'status'                 => $this->status,
			'description'            => $this->description,
			'metadata'               => $this->metadata,
			'created_at'             => $this->created_at,
			'updated_at'             => $this->updated_at,
		);
	}

	/**
	 * Check if transaction exists.
	 *
	 * @return bool True if exists.
	 */
	public function exists() {
		return $this->id > 0;
	}
}
