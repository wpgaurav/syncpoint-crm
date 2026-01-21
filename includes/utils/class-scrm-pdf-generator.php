<?php
/**
 * Invoice PDF Generator
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class PDF_Generator
 *
 * Generates PDF invoices using DOMPDF or simple HTML.
 *
 * @since 1.0.0
 */
class PDF_Generator {

	/**
	 * Invoice object.
	 *
	 * @var \SCRM\Core\Invoice
	 */
	private $invoice;

	/**
	 * Constructor.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 */
	public function __construct( $invoice ) {
		$this->invoice = $invoice;
	}

	/**
	 * Generate PDF.
	 *
	 * @return string|false PDF file path or false on failure.
	 */
	public function generate() {
		$html = $this->get_html();

		// Try DOMPDF if available.
		if ( class_exists( '\Dompdf\Dompdf' ) ) {
			return $this->generate_with_dompdf( $html );
		}

		// Try mPDF if available.
		if ( class_exists( '\Mpdf\Mpdf' ) ) {
			return $this->generate_with_mpdf( $html );
		}

		// Fallback: Save HTML and let the user print to PDF.
		return $this->save_html( $html );
	}

	/**
	 * Generate PDF using DOMPDF.
	 *
	 * @param string $html HTML content.
	 * @return string|false PDF path or false.
	 */
	private function generate_with_dompdf( $html ) {
		try {
			$dompdf = new \Dompdf\Dompdf( array(
				'isRemoteEnabled' => true,
			) );

			$dompdf->loadHtml( $html );
			$dompdf->setPaper( 'A4', 'portrait' );
			$dompdf->render();

			$output = $dompdf->output();
			$path = $this->get_pdf_path();

			file_put_contents( $path, $output );

			return $path;
		} catch ( \Exception $e ) {
			error_log( 'SCRM PDF Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Generate PDF using mPDF.
	 *
	 * @param string $html HTML content.
	 * @return string|false PDF path or false.
	 */
	private function generate_with_mpdf( $html ) {
		try {
			$mpdf = new \Mpdf\Mpdf( array(
				'mode'   => 'utf-8',
				'format' => 'A4',
			) );

			$mpdf->WriteHTML( $html );

			$path = $this->get_pdf_path();
			$mpdf->Output( $path, \Mpdf\Output\Destination::FILE );

			return $path;
		} catch ( \Exception $e ) {
			error_log( 'SCRM PDF Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Save HTML file (fallback).
	 *
	 * @param string $html HTML content.
	 * @return string|false HTML path or false.
	 */
	private function save_html( $html ) {
		$path = $this->get_pdf_path( 'html' );
		$result = file_put_contents( $path, $html );

		return $result ? $path : false;
	}

	/**
	 * Get PDF file path.
	 *
	 * @param string $extension File extension.
	 * @return string File path.
	 */
	private function get_pdf_path( $extension = 'pdf' ) {
		$upload_dir = wp_upload_dir();
		$scrm_dir = $upload_dir['basedir'] . '/starter-crm/invoices';

		// Create directory if needed.
		if ( ! file_exists( $scrm_dir ) ) {
			wp_mkdir_p( $scrm_dir );

			// Add index.php to prevent directory listing.
			file_put_contents( $scrm_dir . '/index.php', '<?php // Silence is golden.' );
		}

		$filename = sanitize_file_name( $this->invoice->invoice_number . '.' . $extension );

		return $scrm_dir . '/' . $filename;
	}

	/**
	 * Get HTML content for PDF.
	 *
	 * @return string HTML content.
	 */
	private function get_html() {
		// Get invoice settings.
		$settings = scrm_get_settings( 'invoices' );

		$company_name    = $settings['company_name'] ?? get_bloginfo( 'name' );
		$company_address = $settings['company_address'] ?? '';
		$company_tax_id  = $settings['company_tax_id'] ?? '';
		$company_logo    = $settings['company_logo'] ?? '';

		// Get contact.
		$contact = new \SCRM\Core\Contact( $this->invoice->contact_id );

		// Get items.
		$items = $this->invoice->get_items();

		// Build HTML.
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php printf( 'Invoice %s', esc_html( $this->invoice->invoice_number ) ); ?></title>
			<style>
				<?php echo $this->get_pdf_styles(); ?>
			</style>
		</head>
		<body>
			<div class="invoice">
				<div class="header">
					<div class="company">
						<?php if ( $company_logo ) : ?>
							<img src="<?php echo esc_url( $company_logo ); ?>" class="logo">
						<?php endif; ?>
						<div class="company-name"><?php echo esc_html( $company_name ); ?></div>
						<div class="company-address"><?php echo nl2br( esc_html( $company_address ) ); ?></div>
						<?php if ( $company_tax_id ) : ?>
							<div class="company-tax">Tax ID: <?php echo esc_html( $company_tax_id ); ?></div>
						<?php endif; ?>
					</div>
					<div class="invoice-info">
						<h1>INVOICE</h1>
						<div class="invoice-number"><?php echo esc_html( $this->invoice->invoice_number ); ?></div>
						<table class="meta-table">
							<tr>
								<td>Issue Date:</td>
								<td><?php echo esc_html( scrm_format_date( $this->invoice->issue_date ) ); ?></td>
							</tr>
							<tr>
								<td>Due Date:</td>
								<td><?php echo esc_html( scrm_format_date( $this->invoice->due_date ) ); ?></td>
							</tr>
							<tr>
								<td>Status:</td>
								<td class="status-<?php echo esc_attr( $this->invoice->status ); ?>">
									<?php echo esc_html( strtoupper( $this->invoice->status ) ); ?>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="bill-to">
					<h3>Bill To:</h3>
					<div class="client-name"><?php echo esc_html( $contact->get_full_name() ); ?></div>
					<?php if ( $company = $contact->get_company() ) : ?>
						<div><?php echo esc_html( $company->name ); ?></div>
					<?php endif; ?>
					<div><?php echo esc_html( $contact->email ); ?></div>
					<?php if ( $address = $contact->get_formatted_address() ) : ?>
						<div><?php echo esc_html( $address ); ?></div>
					<?php endif; ?>
				</div>

				<table class="items">
					<thead>
						<tr>
							<th class="description">Description</th>
							<th class="qty">Qty</th>
							<th class="price">Unit Price</th>
							<th class="amount">Amount</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item->description ); ?></td>
								<td class="qty"><?php echo esc_html( number_format( $item->quantity, 2 ) ); ?></td>
								<td class="price"><?php echo esc_html( scrm_format_currency( $item->unit_price, $this->invoice->currency ) ); ?></td>
								<td class="amount"><?php echo esc_html( scrm_format_currency( $item->total, $this->invoice->currency ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="totals">
					<table class="totals-table">
						<tr>
							<td>Subtotal:</td>
							<td><?php echo esc_html( scrm_format_currency( $this->invoice->subtotal, $this->invoice->currency ) ); ?></td>
						</tr>
						<?php if ( $this->invoice->discount_value > 0 ) : ?>
							<tr>
								<td>Discount:</td>
								<td>
									-<?php
									$discount = 'percentage' === $this->invoice->discount_type
										? $this->invoice->subtotal * ( $this->invoice->discount_value / 100 )
										: $this->invoice->discount_value;
									echo esc_html( scrm_format_currency( $discount, $this->invoice->currency ) );
									?>
								</td>
							</tr>
						<?php endif; ?>
						<?php if ( $this->invoice->tax_rate > 0 ) : ?>
							<tr>
								<td>Tax (<?php echo esc_html( $this->invoice->tax_rate ); ?>%):</td>
								<td><?php echo esc_html( scrm_format_currency( $this->invoice->tax_amount, $this->invoice->currency ) ); ?></td>
							</tr>
						<?php endif; ?>
						<tr class="total-row">
							<td>Total:</td>
							<td><?php echo esc_html( scrm_format_currency( $this->invoice->total, $this->invoice->currency ) ); ?></td>
						</tr>
					</table>
				</div>

				<?php if ( ! empty( $this->invoice->notes ) ) : ?>
					<div class="notes">
						<h4>Notes:</h4>
						<p><?php echo nl2br( esc_html( $this->invoice->notes ) ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $this->invoice->terms ) ) : ?>
					<div class="terms">
						<h4>Terms & Conditions:</h4>
						<p><?php echo nl2br( esc_html( $this->invoice->terms ) ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get PDF styles.
	 *
	 * @return string CSS styles.
	 */
	private function get_pdf_styles() {
		return '
			* { margin: 0; padding: 0; box-sizing: border-box; }
			body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
			.invoice { padding: 40px; }
			.header { display: table; width: 100%; margin-bottom: 40px; }
			.company, .invoice-info { display: table-cell; vertical-align: top; }
			.company { width: 60%; }
			.invoice-info { width: 40%; text-align: right; }
			.logo { max-height: 60px; margin-bottom: 10px; }
			.company-name { font-size: 18pt; font-weight: bold; margin-bottom: 5px; }
			.company-address, .company-tax { font-size: 9pt; color: #666; }
			h1 { font-size: 28pt; color: #333; margin: 0 0 5px 0; }
			.invoice-number { font-size: 12pt; color: #666; margin-bottom: 15px; }
			.meta-table { margin-left: auto; }
			.meta-table td { padding: 3px 0; font-size: 9pt; }
			.meta-table td:first-child { text-align: right; padding-right: 10px; color: #666; }
			.status-paid { color: #059669; font-weight: bold; }
			.status-sent, .status-viewed { color: #2563eb; }
			.status-overdue { color: #dc2626; font-weight: bold; }
			.bill-to { margin-bottom: 30px; }
			.bill-to h3 { font-size: 10pt; color: #666; margin-bottom: 8px; text-transform: uppercase; }
			.client-name { font-size: 12pt; font-weight: bold; }
			.items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
			.items th { background: #f3f4f6; padding: 10px; text-align: left; font-size: 9pt; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; }
			.items td { padding: 12px 10px; border-bottom: 1px solid #e5e7eb; }
			.items .description { width: 50%; }
			.items .qty, .items .price, .items .amount { text-align: right; }
			.totals { text-align: right; }
			.totals-table { margin-left: auto; width: 250px; }
			.totals-table td { padding: 5px 0; }
			.totals-table td:first-child { text-align: right; padding-right: 20px; color: #666; }
			.totals-table .total-row { font-size: 14pt; font-weight: bold; border-top: 2px solid #333; }
			.totals-table .total-row td { padding-top: 10px; }
			.notes, .terms { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
			.notes h4, .terms h4 { font-size: 10pt; color: #666; margin-bottom: 8px; }
			.notes p, .terms p { font-size: 9pt; color: #666; }
		';
	}

	/**
	 * Stream PDF for download.
	 *
	 * @return void
	 */
	public function stream() {
		$path = $this->invoice->pdf_path;

		if ( empty( $path ) || ! file_exists( $path ) ) {
			$path = $this->generate();
		}

		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Could not generate PDF.', 'syncpoint-crm' ) );
		}

		$filename = sanitize_file_name( $this->invoice->invoice_number . '.pdf' );

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );

		readfile( $path );
		exit;
	}
}
