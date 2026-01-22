<?php
/**
 * Email Handler
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class Emails
 *
 * Handles all email sending.
 *
 * @since 1.0.0
 */
class Emails {

	/**
	 * Send invoice email.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @param array              $args    Email arguments.
	 * @return bool True on success.
	 */
	public static function send_invoice( $invoice, $args = array() ) {
		$contact = new \SCRM\Core\Contact( $invoice->contact_id );

		if ( ! $contact->exists() || empty( $contact->email ) ) {
			return false;
		}

		$settings = scrm_get_settings( 'invoices' );

		$defaults = array(
			'to'       => $contact->email,
			'subject'  => sprintf(
				/* translators: %1$s: company name, %2$s: invoice number */
				__( 'Invoice %2$s from %1$s', 'syncpoint-crm' ),
				$settings['company_name'] ?? get_bloginfo( 'name' ),
				$invoice->invoice_number
			),
			'message'  => '',
			'reply_to' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build message.
		if ( empty( $args['message'] ) ) {
			$args['message'] = self::get_invoice_email_content( $invoice, $contact );
		}

		// Prepare headers.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . ( $settings['company_name'] ?? get_bloginfo( 'name' ) ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		if ( ! empty( $args['reply_to'] ) ) {
			$headers[] = 'Reply-To: ' . $args['reply_to'];
		}

		/**
		 * Filter invoice email content.
		 *
		 * @since 1.0.0
		 * @param array              $args    Email arguments.
		 * @param \SCRM\Core\Invoice $invoice Invoice object.
		 */
		$args = apply_filters( 'scrm_invoice_email_args', $args, $invoice );

		$result = wp_mail( $args['to'], $args['subject'], $args['message'], $headers );

		if ( $result ) {
			// Mark invoice as sent.
			$invoice->mark_sent();

			// Log activity.
			scrm_log_activity(
				'invoice',
				$invoice->id,
				'email_sent',
				sprintf(
				/* translators: %s: email address */
					__( 'Invoice emailed to %s', 'syncpoint-crm' ),
					$contact->email
				)
			);

			do_action( 'scrm_invoice_email_sent', $invoice, $contact );
		}

		return $result;
	}

	/**
	 * Get invoice email content.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @param \SCRM\Core\Contact $contact Contact object.
	 * @return string Email HTML content.
	 */
	private static function get_invoice_email_content( $invoice, $contact ) {
		$settings     = scrm_get_settings( 'invoices' );
		$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );

		$view_url = $invoice->get_public_url();
		$pdf_url  = $invoice->get_pdf_url();

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
			<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f4f5;">
				<tr>
					<td align="center" style="padding: 40px 20px;">
						<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
							<!-- Header -->
							<tr>
								<td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 40px 40px 30px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">
										<?php echo esc_html( $company_name ); ?>
									</h1>
								</td>
							</tr>

							<!-- Content -->
							<tr>
								<td style="padding: 40px;">
									<p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.5;">
										<?php
										printf(
											/* translators: %s: contact name */
											esc_html__( 'Hi %s,', 'syncpoint-crm' ),
											esc_html( $contact->first_name ?: $contact->get_full_name() )
										);
										?>
									</p>

									<p style="margin: 0 0 30px; color: #374151; font-size: 16px; line-height: 1.5;">
										<?php
										printf(
											/* translators: %s: invoice number */
											esc_html__( 'We have generated a new invoice for you. Here are the details:', 'syncpoint-crm' ),
											esc_html( $invoice->invoice_number )
										);
										?>
									</p>

									<!-- Invoice Details Box -->
									<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f9fafb; border-radius: 8px; margin-bottom: 30px;">
										<tr>
											<td style="padding: 25px;">
												<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
													<tr>
														<td style="padding: 5px 0;">
															<span style="color: #6b7280; font-size: 14px;"><?php esc_html_e( 'Invoice Number:', 'syncpoint-crm' ); ?></span>
															<span style="color: #1f2937; font-size: 14px; font-weight: 600; float: right;">
																<?php echo esc_html( $invoice->invoice_number ); ?>
															</span>
														</td>
													</tr>
													<tr>
														<td style="padding: 5px 0;">
															<span style="color: #6b7280; font-size: 14px;"><?php esc_html_e( 'Issue Date:', 'syncpoint-crm' ); ?></span>
															<span style="color: #1f2937; font-size: 14px; float: right;">
																<?php echo esc_html( scrm_format_gmdate( $invoice->issue_date ) ); ?>
															</span>
														</td>
													</tr>
													<tr>
														<td style="padding: 5px 0;">
															<span style="color: #6b7280; font-size: 14px;"><?php esc_html_e( 'Due Date:', 'syncpoint-crm' ); ?></span>
															<span style="color: #1f2937; font-size: 14px; float: right;">
																<?php echo esc_html( scrm_format_gmdate( $invoice->due_date ) ); ?>
															</span>
														</td>
													</tr>
													<tr>
														<td style="padding: 15px 0 5px; border-top: 2px solid #e5e7eb; margin-top: 10px;">
															<span style="color: #1f2937; font-size: 18px; font-weight: 700;"><?php esc_html_e( 'Amount Due:', 'syncpoint-crm' ); ?></span>
															<span style="color: #3b82f6; font-size: 24px; font-weight: 700; float: right;">
																<?php echo esc_html( scrm_format_currency( $invoice->total, $invoice->currency ) ); ?>
															</span>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>

									<!-- CTA Buttons -->
									<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
										<tr>
											<td align="center" style="padding-bottom: 20px;">
												<a href="<?php echo esc_url( $view_url ); ?>"
													style="display: inline-block; padding: 14px 32px; background-color: #3b82f6; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 6px;">
													<?php esc_html_e( 'View & Pay Invoice', 'syncpoint-crm' ); ?>
												</a>
											</td>
										</tr>
										<tr>
											<td align="center">
												<a href="<?php echo esc_url( $pdf_url ); ?>"
													style="color: #6b7280; text-decoration: underline; font-size: 14px;">
													<?php esc_html_e( 'Download PDF', 'syncpoint-crm' ); ?>
												</a>
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<!-- Footer -->
							<tr>
								<td style="padding: 30px 40px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
									<p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center;">
										<?php
										printf(
											/* translators: %s: company name */
											esc_html__( 'Thank you for your business! - %s', 'syncpoint-crm' ),
											esc_html( $company_name )
										);
										?>
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send payment confirmation email.
	 *
	 * @param \SCRM\Core\Invoice     $invoice     Invoice object.
	 * @param \SCRM\Core\Transaction $transaction Transaction object.
	 * @return bool True on success.
	 */
	public static function send_payment_confirmation( $invoice, $transaction ) {
		$contact = new \SCRM\Core\Contact( $invoice->contact_id );

		if ( ! $contact->exists() || empty( $contact->email ) ) {
			return false;
		}

		$settings     = scrm_get_settings( 'invoices' );
		$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: %s: invoice number */
			__( 'Payment Received - Invoice %s', 'syncpoint-crm' ),
			$invoice->invoice_number
		);

		$message = self::get_payment_confirmation_content( $invoice, $transaction, $contact );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $company_name . ' <' . get_option( 'admin_email' ) . '>',
		);

		$result = wp_mail( $contact->email, $subject, $message, $headers );

		if ( $result ) {
			scrm_log_activity(
				'invoice',
				$invoice->id,
				'payment_email_sent',
				sprintf(
				/* translators: %s: email address */
					__( 'Payment confirmation emailed to %s', 'syncpoint-crm' ),
					$contact->email
				)
			);
		}

		return $result;
	}

	/**
	 * Get payment confirmation email content.
	 *
	 * @param \SCRM\Core\Invoice     $invoice     Invoice object.
	 * @param \SCRM\Core\Transaction $transaction Transaction object.
	 * @param \SCRM\Core\Contact     $contact     Contact object.
	 * @return string Email HTML content.
	 */
	private static function get_payment_confirmation_content( $invoice, $transaction, $contact ) {
		$settings     = scrm_get_settings( 'invoices' );
		$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
		</head>
		<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
			<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f4f5;">
				<tr>
					<td align="center" style="padding: 40px 20px;">
						<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
							<tr>
								<td style="background-color: #10b981; padding: 40px; text-align: center;">
									<div style="font-size: 48px; margin-bottom: 10px;">✓</div>
									<h1 style="margin: 0; color: #ffffff; font-size: 24px;">
										<?php esc_html_e( 'Payment Received', 'syncpoint-crm' ); ?>
									</h1>
								</td>
							</tr>
							<tr>
								<td style="padding: 40px;">
									<p style="margin: 0 0 20px; color: #374151; font-size: 16px;">
										<?php
										printf(
											/* translators: %s: contact name */
											esc_html__( 'Hi %s,', 'syncpoint-crm' ),
											esc_html( $contact->first_name ?: $contact->get_full_name() )
										);
										?>
									</p>
									<p style="margin: 0 0 30px; color: #374151; font-size: 16px;">
										<?php esc_html_e( 'We have received your payment. Thank you!', 'syncpoint-crm' ); ?>
									</p>

									<table role="presentation" width="100%" style="background: #f9fafb; padding: 20px; border-radius: 8px;">
										<tr>
											<td style="padding: 8px 0; color: #6b7280;"><?php esc_html_e( 'Invoice:', 'syncpoint-crm' ); ?></td>
											<td style="padding: 8px 0; text-align: right; font-weight: 600;"><?php echo esc_html( $invoice->invoice_number ); ?></td>
										</tr>
										<tr>
											<td style="padding: 8px 0; color: #6b7280;"><?php esc_html_e( 'Amount:', 'syncpoint-crm' ); ?></td>
											<td style="padding: 8px 0; text-align: right; font-weight: 600; color: #10b981;">
												<?php echo esc_html( scrm_format_currency( $transaction->amount, $transaction->currency ) ); ?>
											</td>
										</tr>
										<tr>
											<td style="padding: 8px 0; color: #6b7280;"><?php esc_html_e( 'Date:', 'syncpoint-crm' ); ?></td>
											<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( scrm_format_gmdate( $transaction->created_at ) ); ?></td>
										</tr>
										<tr>
											<td style="padding: 8px 0; color: #6b7280;"><?php esc_html_e( 'Transaction ID:', 'syncpoint-crm' ); ?></td>
											<td style="padding: 8px 0; text-align: right; font-size: 12px;"><?php echo esc_html( $transaction->transaction_id ); ?></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td style="padding: 30px 40px; background-color: #f9fafb; text-align: center; color: #6b7280; font-size: 14px;">
									<?php echo esc_html( $company_name ); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send reminder email.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @return bool True on success.
	 */
	public static function send_reminder( $invoice ) {
		$contact = new \SCRM\Core\Contact( $invoice->contact_id );

		if ( ! $contact->exists() || empty( $contact->email ) ) {
			return false;
		}

		$settings     = scrm_get_settings( 'invoices' );
		$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );

		$is_overdue = $invoice->is_overdue();

		$subject = $is_overdue
			? sprintf( __( 'Overdue: Invoice %s', 'syncpoint-crm' ), $invoice->invoice_number )
			: sprintf( __( 'Reminder: Invoice %s Due Soon', 'syncpoint-crm' ), $invoice->invoice_number );

		$message = self::get_reminder_email_content( $invoice, $contact, $is_overdue );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $company_name . ' <' . get_option( 'admin_email' ) . '>',
		);

		$result = wp_mail( $contact->email, $subject, $message, $headers );

		if ( $result ) {
			scrm_log_activity(
				'invoice',
				$invoice->id,
				'reminder_sent',
				sprintf(
				/* translators: %s: email address */
					__( 'Reminder emailed to %s', 'syncpoint-crm' ),
					$contact->email
				)
			);
		}

		return $result;
	}

	/**
	 * Get reminder email content.
	 *
	 * @param \SCRM\Core\Invoice $invoice    Invoice object.
	 * @param \SCRM\Core\Contact $contact    Contact object.
	 * @param bool               $is_overdue Whether invoice is overdue.
	 * @return string Email HTML content.
	 */
	private static function get_reminder_email_content( $invoice, $contact, $is_overdue ) {
		$settings     = scrm_get_settings( 'invoices' );
		$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );
		$view_url     = $invoice->get_public_url();

		$header_color = $is_overdue ? '#dc2626' : '#f59e0b';

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
		</head>
		<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
			<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td align="center" style="padding: 40px 20px;">
						<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background: #fff; border-radius: 8px;">
							<tr>
								<td style="background: <?php echo esc_attr( $header_color ); ?>; padding: 40px; text-align: center;">
									<h1 style="margin: 0; color: #fff; font-size: 24px;">
										<?php
										echo $is_overdue
											? esc_html__( 'Payment Overdue', 'syncpoint-crm' )
											: esc_html__( 'Payment Reminder', 'syncpoint-crm' );
										?>
									</h1>
								</td>
							</tr>
							<tr>
								<td style="padding: 40px;">
									<p style="margin: 0 0 20px; font-size: 16px; color: #374151;">
										<?php
										printf(
											/* translators: %s: contact name */
											esc_html__( 'Hi %s,', 'syncpoint-crm' ),
											esc_html( $contact->first_name ?: $contact->get_full_name() )
										);
										?>
									</p>
									<p style="margin: 0 0 30px; font-size: 16px; color: #374151;">
										<?php
										if ( $is_overdue ) {
											printf(
												/* translators: %1$s: invoice number, %2$s: due date */
												esc_html__( 'Invoice %1$s was due on %2$s. Please make payment as soon as possible.', 'syncpoint-crm' ),
												esc_html( $invoice->invoice_number ),
												esc_html( scrm_format_gmdate( $invoice->due_date ) )
											);
										} else {
											printf(
												/* translators: %1$s: invoice number, %2$s: due date */
												esc_html__( 'This is a friendly reminder that invoice %1$s is due on %2$s.', 'syncpoint-crm' ),
												esc_html( $invoice->invoice_number ),
												esc_html( scrm_format_gmdate( $invoice->due_date ) )
											);
										}
										?>
									</p>
									<p style="margin: 0 0 30px; font-size: 24px; font-weight: 700; color: <?php echo esc_attr( $header_color ); ?>;">
										<?php echo esc_html( scrm_format_currency( $invoice->total, $invoice->currency ) ); ?>
									</p>
									<p style="text-align: center;">
										<a href="<?php echo esc_url( $view_url ); ?>"
											style="display: inline-block; padding: 14px 32px; background: <?php echo esc_attr( $header_color ); ?>; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">
											<?php esc_html_e( 'Pay Now', 'syncpoint-crm' ); ?>
										</a>
									</p>
								</td>
							</tr>
							<tr>
								<td style="padding: 20px 40px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 14px;">
									<?php echo esc_html( $company_name ); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
