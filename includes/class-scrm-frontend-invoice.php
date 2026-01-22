<?php
/**
 * Frontend Invoice Handler
 *
 * Handles public invoice viewing and payment processing.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Frontend_Invoice
 *
 * @since 1.0.0
 */
class SCRM_Frontend_Invoice {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'handle_invoice_requests' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Add rewrite rules.
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^invoice/([^/]+)/?$',
			'index.php?scrm_invoice=$matches[1]',
			'top'
		);
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'scrm_invoice';
		$vars[] = 'key';
		$vars[] = 'download';
		$vars[] = 'payment';
		return $vars;
	}

	/**
	 * Handle invoice requests.
	 */
	public function handle_invoice_requests() {
		$invoice_number = get_query_var( 'scrm_invoice' );

		if ( empty( $invoice_number ) ) {
			return;
		}

		// Find invoice.
		$invoice = new SCRM\Core\Invoice();
		$invoice->read_by_number( sanitize_text_field( $invoice_number ) );

		if ( ! $invoice->exists() ) {
			wp_die(
				esc_html__( 'Invoice not found.', 'syncpoint-crm' ),
				esc_html__( 'Invoice Not Found', 'syncpoint-crm' ),
				array( 'response' => 404 )
			);
		}

		// Verify access key.
		$provided_key = get_query_var( 'key' );
		if ( empty( $provided_key ) ) {
			$provided_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		}

		if ( $provided_key !== $invoice->get_access_key() ) {
			// Check if user is logged in and has permission.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die(
					esc_html__( 'You do not have permission to view this invoice.', 'syncpoint-crm' ),
					esc_html__( 'Access Denied', 'syncpoint-crm' ),
					array( 'response' => 403 )
				);
			}
		}

		// Mark as viewed.
		$invoice->mark_viewed();

		// Handle download.
		$download = get_query_var( 'download' );
		if ( 'pdf' === $download ) {
			$this->handle_pdf_download( $invoice );
			exit;
		}

		// Handle payment return.
		$payment_status = get_query_var( 'payment' );
		if ( ! empty( $payment_status ) ) {
			$this->handle_payment_return( $invoice, $payment_status );
		}

		// Display invoice.
		$this->display_invoice( $invoice );
		exit;
	}

	/**
	 * Display invoice.
	 *
	 * @param SCRM\Core\Invoice $invoice Invoice object.
	 */
	private function display_invoice( $invoice ) {
		// Get contact.
		$contact = new SCRM\Core\Contact( $invoice->contact_id );

		// Get items.
		$items = $invoice->get_items();

		// Get company settings.
		$settings        = scrm_get_settings( 'invoices' );
		$company_name    = $settings['company_name'] ?? get_bloginfo( 'name' );
		$company_address = $settings['company_address'] ?? '';
		$company_tax_id  = $settings['company_tax_id'] ?? '';
		$company_logo    = $settings['company_logo'] ?? '';

		// Load template.
		$template = scrm_locate_template( 'invoices/invoice-public.php' );

		if ( $template ) {
			include $template;
		} else {
			include SCRM_PLUGIN_DIR . 'templates/invoices/invoice-public.php';
		}
	}

	/**
	 * Handle PDF download.
	 *
	 * @param SCRM\Core\Invoice $invoice Invoice object.
	 */
	private function handle_pdf_download( $invoice ) {
		$generator = new SCRM\Utils\PDF_Generator( $invoice );
		$generator->stream();
	}

	/**
	 * Handle payment return.
	 *
	 * @param SCRM\Core\Invoice $invoice        Invoice object.
	 * @param string            $payment_status Payment status.
	 */
	private function handle_payment_return( $invoice, $payment_status ) {
		if ( 'success' === $payment_status ) {
			// Payment success, but actual status update happens via webhook.
			add_action(
				'scrm_before_invoice_display',
				function () {
					echo '<div class="scrm-notice scrm-notice--success">' .
					esc_html__( 'Thank you! Your payment is being processed.', 'syncpoint-crm' ) .
					'</div>';
				}
			);
		} elseif ( 'cancelled' === $payment_status ) {
			add_action(
				'scrm_before_invoice_display',
				function () {
					echo '<div class="scrm-notice scrm-notice--warning">' .
					esc_html__( 'Payment was cancelled. You can try again using the buttons below.', 'syncpoint-crm' ) .
					'</div>';
				}
			);
		}
	}
}

// Initialize.
new SCRM_Frontend_Invoice();
