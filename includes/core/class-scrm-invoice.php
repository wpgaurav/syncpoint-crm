<?php
/**
 * Invoice Model
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Invoice
 *
 * @since 1.0.0
 */
class Invoice {

	/**
	 * Invoice ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Invoice number.
	 *
	 * @var string
	 */
	public $invoice_number = '';

	/**
	 * Contact ID.
	 *
	 * @var int
	 */
	public $contact_id = 0;

	/**
	 * Company ID.
	 *
	 * @var int|null
	 */
	public $company_id = null;

	/**
	 * Status.
	 *
	 * @var string
	 */
	public $status = 'draft';

	/**
	 * Issue date.
	 *
	 * @var string
	 */
	public $issue_date = '';

	/**
	 * Due date.
	 *
	 * @var string
	 */
	public $due_date = '';

	/**
	 * Subtotal.
	 *
	 * @var float
	 */
	public $subtotal = 0.00;

	/**
	 * Tax rate.
	 *
	 * @var float
	 */
	public $tax_rate = 0.00;

	/**
	 * Tax amount.
	 *
	 * @var float
	 */
	public $tax_amount = 0.00;

	/**
	 * Discount type.
	 *
	 * @var string
	 */
	public $discount_type = 'fixed';

	/**
	 * Discount value.
	 *
	 * @var float
	 */
	public $discount_value = 0.00;

	/**
	 * Total.
	 *
	 * @var float
	 */
	public $total = 0.00;

	/**
	 * Currency.
	 *
	 * @var string
	 */
	public $currency = 'USD';

	/**
	 * Notes.
	 *
	 * @var string
	 */
	public $notes = '';

	/**
	 * Terms.
	 *
	 * @var string
	 */
	public $terms = '';

	/**
	 * Payment methods.
	 *
	 * @var array
	 */
	public $payment_methods = array();

	/**
	 * PayPal payment link.
	 *
	 * @var string
	 */
	public $paypal_payment_link = '';

	/**
	 * Stripe payment link.
	 *
	 * @var string
	 */
	public $stripe_payment_link = '';

	/**
	 * PDF path.
	 *
	 * @var string
	 */
	public $pdf_path = '';

	/**
	 * Viewed at.
	 *
	 * @var string|null
	 */
	public $viewed_at = null;

	/**
	 * Paid at.
	 *
	 * @var string|null
	 */
	public $paid_at = null;

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
	 * Created by.
	 *
	 * @var int
	 */
	public $created_by = 0;

	/**
	 * Line items.
	 *
	 * @var array
	 */
	protected $items = null;

	/**
	 * Constructor.
	 *
	 * @param int|object $invoice Invoice ID or object.
	 */
	public function __construct( $invoice = 0 ) {
		if ( is_numeric( $invoice ) && $invoice > 0 ) {
			$this->read( $invoice );
		} elseif ( is_object( $invoice ) ) {
			$this->set_props( $invoice );
		}
	}

	/**
	 * Read invoice from database.
	 *
	 * @param int $id Invoice ID.
	 */
	public function read( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

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
	 * Read invoice by number.
	 *
	 * @param string $number Invoice number.
	 */
	public function read_by_number( $number ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE invoice_number = %s",
				$number
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
		$this->id                  = (int) $data->id;
		$this->invoice_number      = $data->invoice_number;
		$this->contact_id          = (int) $data->contact_id;
		$this->company_id          = $data->company_id ? (int) $data->company_id : null;
		$this->status              = $data->status;
		$this->issue_date          = $data->issue_date;
		$this->due_date            = $data->due_date;
		$this->subtotal            = floatval( $data->subtotal );
		$this->tax_rate            = floatval( $data->tax_rate );
		$this->tax_amount          = floatval( $data->tax_amount );
		$this->discount_type       = $data->discount_type;
		$this->discount_value      = floatval( $data->discount_value );
		$this->total               = floatval( $data->total );
		$this->currency            = $data->currency;
		$this->notes               = $data->notes;
		$this->terms               = $data->terms;
		$this->payment_methods     = is_string( $data->payment_methods ) ? json_decode( $data->payment_methods, true ) : $data->payment_methods;
		$this->paypal_payment_link = $data->paypal_payment_link;
		$this->stripe_payment_link = $data->stripe_payment_link;
		$this->pdf_path            = $data->pdf_path;
		$this->viewed_at           = $data->viewed_at;
		$this->paid_at             = $data->paid_at;
		$this->created_at          = $data->created_at;
		$this->updated_at          = $data->updated_at;
		$this->created_by          = (int) $data->created_by;

		if ( ! is_array( $this->payment_methods ) ) {
			$this->payment_methods = array();
		}
	}

	/**
	 * Save invoice.
	 *
	 * @return int|\WP_Error Invoice ID or error.
	 */
	public function save() {
		if ( $this->id ) {
			return $this->upgmdate();
		}
		return $this->create();
	}

	/**
	 * Create new invoice.
	 *
	 * @return int|\WP_Error Invoice ID or error.
	 */
	public function create() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		if ( empty( $this->contact_id ) ) {
			return new \WP_Error( 'missing_contact', __( 'Contact ID is required.', 'syncpoint-crm' ) );
		}

		if ( empty( $this->invoice_number ) ) {
			$this->invoice_number = scrm_generate_id( 'invoice' );
		}

		if ( empty( $this->issue_date ) ) {
			$this->issue_date = current_time( 'Y-m-d' );
		}

		if ( empty( $this->due_date ) ) {
			$this->due_date = gmdate( 'Y-m-d', strtotime( '+30 days', strtotime( $this->issue_date ) ) );
		}

		if ( empty( $this->currency ) ) {
			$this->currency = scrm_get_default_currency();
		}

		$data = array(
			'invoice_number'      => $this->invoice_number,
			'contact_id'          => $this->contact_id,
			'company_id'          => $this->company_id,
			'status'              => $this->status,
			'issue_date'          => $this->issue_date,
			'due_date'            => $this->due_date,
			'subtotal'            => $this->subtotal,
			'tax_rate'            => $this->tax_rate,
			'tax_amount'          => $this->tax_amount,
			'discount_type'       => $this->discount_type,
			'discount_value'      => $this->discount_value,
			'total'               => $this->total,
			'currency'            => $this->currency,
			'notes'               => $this->notes,
			'terms'               => $this->terms,
			'payment_methods'     => wp_json_encode( $this->payment_methods ),
			'paypal_payment_link' => $this->paypal_payment_link,
			'stripe_payment_link' => $this->stripe_payment_link,
			'pdf_path'            => $this->pdf_path,
			'viewed_at'           => $this->viewed_at,
			'paid_at'             => $this->paid_at,
			'created_at'          => current_time( 'mysql' ),
			'updated_at'          => current_time( 'mysql' ),
			'created_by'          => get_current_user_id(),
		);

		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		$this->id = $wpdb->insert_id;

		do_action( 'scrm_invoice_created', $this->id, $this->to_array() );

		return $this->id;
	}

	/**
	 * Update invoice.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function upgmdate() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		$data = array(
			'contact_id'          => $this->contact_id,
			'company_id'          => $this->company_id,
			'status'              => $this->status,
			'issue_date'          => $this->issue_date,
			'due_date'            => $this->due_date,
			'subtotal'            => $this->subtotal,
			'tax_rate'            => $this->tax_rate,
			'tax_amount'          => $this->tax_amount,
			'discount_type'       => $this->discount_type,
			'discount_value'      => $this->discount_value,
			'total'               => $this->total,
			'currency'            => $this->currency,
			'notes'               => $this->notes,
			'terms'               => $this->terms,
			'payment_methods'     => wp_json_encode( $this->payment_methods ),
			'paypal_payment_link' => $this->paypal_payment_link,
			'stripe_payment_link' => $this->stripe_payment_link,
			'pdf_path'            => $this->pdf_path,
			'viewed_at'           => $this->viewed_at,
			'paid_at'             => $this->paid_at,
			'updated_at'          => current_time( 'mysql' ),
		);

		$result = $wpdb->upgmdate( $table, $data, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		return true;
	}

	/**
	 * Delete invoice.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function delete() {
		if ( 'paid' === $this->status ) {
			return new \WP_Error( 'cannot_delete', __( 'Cannot delete a paid invoice.', 'syncpoint-crm' ) );
		}

		global $wpdb;

		// Delete line items.
		$wpdb->delete(
			$wpdb->prefix . 'scrm_invoice_items',
			array( 'invoice_id' => $this->id )
		);

		// Delete invoice.
		$result = $wpdb->delete(
			$wpdb->prefix . 'scrm_invoices',
			array( 'id' => $this->id )
		);

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		// Delete PDF file if exists.
		if ( $this->pdf_path && file_exists( $this->pdf_path ) ) {
			unlink( $this->pdf_path );
		}

		return true;
	}

	/**
	 * Get line items.
	 *
	 * @return array Array of line items.
	 */
	public function get_items() {
		if ( null === $this->items ) {
			global $wpdb;
			$table = $wpdb->prefix . 'scrm_invoice_items';

			$this->items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE invoice_id = %d ORDER BY sort_order ASC",
					$this->id
				)
			);
		}

		return $this->items ?: array();
	}

	/**
	 * Add line item.
	 *
	 * @param array $item Item data.
	 * @return int|false Item ID or false on failure.
	 */
	public function add_item( $item ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoice_items';

		$quantity   = floatval( $item['quantity'] ?? 1 );
		$unit_price = floatval( $item['unit_price'] ?? 0 );
		$total      = $quantity * $unit_price;

		$data = array(
			'invoice_id'  => $this->id,
			'description' => sanitize_textarea_field( $item['description'] ?? '' ),
			'quantity'    => $quantity,
			'unit_price'  => $unit_price,
			'total'       => $total,
			'sort_order'  => absint( $item['sort_order'] ?? 0 ),
			'created_at'  => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $table, $data );

		if ( $result ) {
			$this->items = null; // Clear cache.
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Remove line item.
	 *
	 * @param int $item_id Item ID.
	 * @return bool True on success.
	 */
	public function remove_item( $item_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoice_items';

		$result = $wpdb->delete(
			$table,
			array(
				'id'         => $item_id,
				'invoice_id' => $this->id,
			)
		);

		if ( $result ) {
			$this->items = null;
			return true;
		}

		return false;
	}

	/**
	 * Clear all line items.
	 *
	 * @return bool True on success.
	 */
	public function clear_items() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoice_items';

		$wpdb->delete( $table, array( 'invoice_id' => $this->id ) );
		$this->items = null;

		return true;
	}

	/**
	 * Calculate totals.
	 */
	public function calculate_totals() {
		$items = $this->get_items();

		$this->subtotal = 0;
		foreach ( $items as $item ) {
			$this->subtotal += floatval( $item->total );
		}

		// Calculate discount.
		$discount = 0;
		if ( $this->discount_value > 0 ) {
			if ( 'percentage' === $this->discount_type ) {
				$discount = $this->subtotal * ( $this->discount_value / 100 );
			} else {
				$discount = $this->discount_value;
			}
		}

		$subtotal_after_discount = $this->subtotal - $discount;

		// Calculate tax.
		$this->tax_amount = 0;
		if ( $this->tax_rate > 0 ) {
			$this->tax_amount = $subtotal_after_discount * ( $this->tax_rate / 100 );
		}

		// Calculate total.
		$this->total = $subtotal_after_discount + $this->tax_amount;

		// Round to 2 decimal places.
		$this->subtotal   = round( $this->subtotal, 2 );
		$this->tax_amount = round( $this->tax_amount, 2 );
		$this->total      = round( $this->total, 2 );
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
	 * Get company.
	 *
	 * @return Company|null Company object or null.
	 */
	public function get_company() {
		if ( ! $this->company_id ) {
			return null;
		}
		return new Company( $this->company_id );
	}

	/**
	 * Get formatted total.
	 *
	 * @return string Formatted total.
	 */
	public function get_formatted_total() {
		return scrm_format_currency( $this->total, $this->currency );
	}

	/**
	 * Get status label.
	 *
	 * @return string Status label.
	 */
	public function get_status_label() {
		$statuses = scrm_get_invoice_statuses();
		return isset( $statuses[ $this->status ] ) ? $statuses[ $this->status ] : $this->status;
	}

	/**
	 * Check if invoice is overdue.
	 *
	 * @return bool True if overdue.
	 */
	public function is_overdue() {
		if ( in_array( $this->status, array( 'paid', 'cancelled', 'draft' ), true ) ) {
			return false;
		}

		return strtotime( $this->due_date ) < strtotime( 'today' );
	}

	/**
	 * Mark as sent.
	 *
	 * @return bool True on success.
	 */
	public function mark_sent() {
		if ( 'draft' === $this->status ) {
			$this->status = 'sent';
			$result       = $this->upgmdate();

			if ( ! is_wp_error( $result ) ) {
				do_action( 'scrm_invoice_sent', $this->id, $this->contact_id );
				return true;
			}
		}

		return false;
	}

	/**
	 * Mark as viewed.
	 *
	 * @return bool True on success.
	 */
	public function mark_viewed() {
		if ( empty( $this->viewed_at ) ) {
			$this->viewed_at = current_time( 'mysql' );

			if ( 'sent' === $this->status ) {
				$this->status = 'viewed';
			}

			$result = $this->upgmdate();

			if ( ! is_wp_error( $result ) ) {
				do_action( 'scrm_invoice_viewed', $this->id );
				return true;
			}
		}

		return false;
	}

	/**
	 * Mark as paid.
	 *
	 * @param int $transaction_id Transaction ID.
	 * @return bool True on success.
	 */
	public function mark_paid( $transaction_id = 0 ) {
		$this->status  = 'paid';
		$this->paid_at = current_time( 'mysql' );

		$result = $this->upgmdate();

		if ( ! is_wp_error( $result ) ) {
			do_action( 'scrm_invoice_paid', $this->id, $transaction_id );
			return true;
		}

		return false;
	}

	/**
	 * Get public URL.
	 *
	 * @return string Public invoice URL.
	 */
	public function get_public_url() {
		return add_query_arg(
			array(
				'scrm_invoice' => $this->invoice_number,
				'key'          => $this->get_access_key(),
			),
			home_url( '/invoice/' )
		);
	}

	/**
	 * Get access key.
	 *
	 * @return string Access key.
	 */
	public function get_access_key() {
		return substr( md5( $this->invoice_number . $this->created_at . wp_salt() ), 0, 16 );
	}

	/**
	 * Get PDF URL.
	 *
	 * @return string PDF download URL.
	 */
	public function get_pdf_url() {
		return add_query_arg(
			array(
				'scrm_invoice' => $this->invoice_number,
				'key'          => $this->get_access_key(),
				'download'     => 'pdf',
			),
			home_url( '/invoice/' )
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array Invoice data.
	 */
	public function to_array() {
		return array(
			'id'                  => $this->id,
			'invoice_number'      => $this->invoice_number,
			'contact_id'          => $this->contact_id,
			'company_id'          => $this->company_id,
			'status'              => $this->status,
			'issue_date'          => $this->issue_date,
			'due_date'            => $this->due_date,
			'subtotal'            => $this->subtotal,
			'tax_rate'            => $this->tax_rate,
			'tax_amount'          => $this->tax_amount,
			'discount_type'       => $this->discount_type,
			'discount_value'      => $this->discount_value,
			'total'               => $this->total,
			'currency'            => $this->currency,
			'notes'               => $this->notes,
			'terms'               => $this->terms,
			'payment_methods'     => $this->payment_methods,
			'paypal_payment_link' => $this->paypal_payment_link,
			'stripe_payment_link' => $this->stripe_payment_link,
			'pdf_path'            => $this->pdf_path,
			'viewed_at'           => $this->viewed_at,
			'paid_at'             => $this->paid_at,
			'created_at'          => $this->created_at,
			'updated_at'          => $this->updated_at,
			'created_by'          => $this->created_by,
			'items'               => $this->get_items(),
		);
	}

	/**
	 * Check if invoice exists.
	 *
	 * @return bool True if exists.
	 */
	public function exists() {
		return $this->id > 0;
	}
}
