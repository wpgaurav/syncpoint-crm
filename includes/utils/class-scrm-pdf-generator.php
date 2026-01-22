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
			$dompdf = new \Dompdf\Dompdf(
				array(
					'isRemoteEnabled' => true,
				)
			);

			$dompdf->loadHtml( $html );
			$dompdf->setPaper( 'A4', 'portrait' );
			$dompdf->render();

			$output = $dompdf->output();
			$path   = $this->get_pdf_path();

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
			$mpdf = new \Mpdf\Mpdf(
				array(
					'mode'   => 'utf-8',
					'format' => 'A4',
				)
			);

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
		$path   = $this->get_pdf_path( 'html' );
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
		$scrm_dir   = $upload_dir['basedir'] . '/starter-crm/invoices';

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
				<?php echo $this->get_pdf_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
					</div>
					<div class="invoice-info">
						<h1><?php printf( 'Invoice %s', esc_html( $this->invoice->invoice_number ) ); ?></h1>
					</div>
				</div>
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
		return file_get_contents( SCRM_PLUGIN_DIR . 'assets/css/invoice-pdf.css' );
	}
}
