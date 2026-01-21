<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php printf( esc_html__( 'Invoice %s', 'syncpoint-crm' ), esc_html( $invoice->invoice_number ) ); ?></title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			font-size: 14px;
			line-height: 1.5;
			color: #1f2937;
			background: #f3f4f6;
		}
		.invoice-container {
			max-width: 800px;
			margin: 40px auto;
			background: #fff;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			border-radius: 8px;
			overflow: hidden;
		}
		.invoice-header {
			padding: 40px;
			background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
			color: #fff;
		}
		.invoice-header-top {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 30px;
		}
		.company-info h1 {
			font-size: 24px;
			font-weight: 700;
			margin-bottom: 5px;
		}
		.company-info p {
			opacity: 0.9;
			font-size: 13px;
		}
		.invoice-title {
			text-align: right;
		}
		.invoice-title h2 {
			font-size: 32px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 2px;
		}
		.invoice-title .invoice-number {
			font-size: 16px;
			opacity: 0.9;
		}
		.invoice-meta {
			display: flex;
			gap: 60px;
		}
		.meta-group label {
			display: block;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 1px;
			opacity: 0.8;
			margin-bottom: 4px;
		}
		.meta-group span {
			font-size: 15px;
			font-weight: 500;
		}
		.invoice-body {
			padding: 40px;
		}
		.parties {
			display: flex;
			justify-content: space-between;
			margin-bottom: 40px;
		}
		.party {
			flex: 1;
		}
		.party h3 {
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: #6b7280;
			margin-bottom: 10px;
		}
		.party p {
			margin: 0;
		}
		.party .name {
			font-size: 16px;
			font-weight: 600;
			color: #1f2937;
		}
		.items-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 30px;
		}
		.items-table th {
			background: #f9fafb;
			padding: 12px 16px;
			text-align: left;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: #6b7280;
			border-bottom: 2px solid #e5e7eb;
		}
		.items-table th:last-child,
		.items-table td:last-child {
			text-align: right;
		}
		.items-table td {
			padding: 16px;
			border-bottom: 1px solid #e5e7eb;
		}
		.items-table tbody tr:last-child td {
			border-bottom: 2px solid #e5e7eb;
		}
		.totals {
			display: flex;
			justify-content: flex-end;
			margin-bottom: 40px;
		}
		.totals-table {
			width: 280px;
		}
		.totals-row {
			display: flex;
			justify-content: space-between;
			padding: 8px 0;
		}
		.totals-row.total {
			border-top: 2px solid #1f2937;
			margin-top: 8px;
			padding-top: 16px;
			font-size: 18px;
			font-weight: 700;
		}
		.totals-row.total .value {
			color: #3b82f6;
		}
		.payment-section {
			background: #f0fdf4;
			border: 1px solid #86efac;
			border-radius: 8px;
			padding: 24px;
			margin-bottom: 30px;
		}
		.payment-section h3 {
			font-size: 14px;
			font-weight: 600;
			color: #166534;
			margin-bottom: 16px;
		}
		.payment-buttons {
			display: flex;
			gap: 16px;
			flex-wrap: wrap;
		}
		.payment-button {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 12px 24px;
			border-radius: 6px;
			font-size: 14px;
			font-weight: 600;
			text-decoration: none;
			transition: transform 0.2s, box-shadow 0.2s;
		}
		.payment-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		}
		.payment-button.paypal {
			background: #0070ba;
			color: #fff;
		}
		.payment-button.stripe {
			background: #635bff;
			color: #fff;
		}
		.notes {
			margin-top: 30px;
			padding-top: 30px;
			border-top: 1px solid #e5e7eb;
		}
		.notes h4 {
			font-size: 13px;
			font-weight: 600;
			color: #6b7280;
			margin-bottom: 8px;
		}
		.notes p {
			color: #4b5563;
		}
		.status-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 20px;
			font-size: 12px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.status-paid {
			background: #d1fae5;
			color: #065f46;
		}
		.status-sent, .status-viewed {
			background: #dbeafe;
			color: #1e40af;
		}
		.status-overdue {
			background: #fee2e2;
			color: #991b1b;
		}
		.status-draft {
			background: #f3f4f6;
			color: #4b5563;
		}
		@media print {
			body {
				background: #fff;
			}
			.invoice-container {
				margin: 0;
				box-shadow: none;
			}
			.payment-section {
				display: none;
			}
		}
	</style>
</head>
<body>
	<div class="invoice-container">
		<div class="invoice-header">
			<div class="invoice-header-top">
				<div class="company-info">
					<?php if ( ! empty( $company_logo ) ) : ?>
						<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>" style="max-height: 50px; margin-bottom: 10px;">
					<?php else : ?>
						<h1><?php echo esc_html( $company_name ); ?></h1>
					<?php endif; ?>
					<p><?php echo nl2br( esc_html( $company_address ) ); ?></p>
					<?php if ( ! empty( $company_tax_id ) ) : ?>
						<p><?php esc_html_e( 'Tax ID:', 'syncpoint-crm' ); ?> <?php echo esc_html( $company_tax_id ); ?></p>
					<?php endif; ?>
				</div>
				<div class="invoice-title">
					<h2><?php esc_html_e( 'Invoice', 'syncpoint-crm' ); ?></h2>
					<p class="invoice-number"><?php echo esc_html( $invoice->invoice_number ); ?></p>
				</div>
			</div>
			<div class="invoice-meta">
				<div class="meta-group">
					<label><?php esc_html_e( 'Issue Date', 'syncpoint-crm' ); ?></label>
					<span><?php echo esc_html( scrm_format_date( $invoice->issue_date ) ); ?></span>
				</div>
				<div class="meta-group">
					<label><?php esc_html_e( 'Due Date', 'syncpoint-crm' ); ?></label>
					<span><?php echo esc_html( scrm_format_date( $invoice->due_date ) ); ?></span>
				</div>
				<div class="meta-group">
					<label><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></label>
					<span class="status-badge status-<?php echo esc_attr( $invoice->status ); ?>">
						<?php echo esc_html( $invoice->get_status_label() ); ?>
					</span>
				</div>
			</div>
		</div>

		<div class="invoice-body">
			<div class="parties">
				<div class="party">
					<h3><?php esc_html_e( 'Bill To', 'syncpoint-crm' ); ?></h3>
					<p class="name"><?php echo esc_html( $contact->get_full_name() ); ?></p>
					<?php if ( $company = $contact->get_company() ) : ?>
						<p><?php echo esc_html( $company->name ); ?></p>
					<?php endif; ?>
					<p><?php echo esc_html( $contact->email ); ?></p>
					<?php if ( $address = $contact->get_formatted_address() ) : ?>
						<p><?php echo esc_html( $address ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $contact->tax_id ) ) : ?>
						<p><?php esc_html_e( 'Tax ID:', 'syncpoint-crm' ); ?> <?php echo esc_html( $contact->tax_id ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<table class="items-table">
				<thead>
					<tr>
						<th style="width: 50%;"><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Qty', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Unit Price', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'syncpoint-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item->description ); ?></td>
							<td><?php echo esc_html( number_format( $item->quantity, 2 ) ); ?></td>
							<td><?php echo esc_html( scrm_format_currency( $item->unit_price, $invoice->currency ) ); ?></td>
							<td><?php echo esc_html( scrm_format_currency( $item->total, $invoice->currency ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="totals">
				<div class="totals-table">
					<div class="totals-row">
						<span><?php esc_html_e( 'Subtotal', 'syncpoint-crm' ); ?></span>
						<span><?php echo esc_html( scrm_format_currency( $invoice->subtotal, $invoice->currency ) ); ?></span>
					</div>
					<?php if ( $invoice->discount_value > 0 ) : ?>
						<div class="totals-row">
							<span>
								<?php
								if ( 'percentage' === $invoice->discount_type ) {
									printf( esc_html__( 'Discount (%s%%)', 'syncpoint-crm' ), esc_html( $invoice->discount_value ) );
								} else {
									esc_html_e( 'Discount', 'syncpoint-crm' );
								}
								?>
							</span>
							<span>
								-<?php
								$discount = 'percentage' === $invoice->discount_type
									? $invoice->subtotal * ( $invoice->discount_value / 100 )
									: $invoice->discount_value;
								echo esc_html( scrm_format_currency( $discount, $invoice->currency ) );
								?>
							</span>
						</div>
					<?php endif; ?>
					<?php if ( $invoice->tax_rate > 0 ) : ?>
						<div class="totals-row">
							<span><?php printf( esc_html__( 'Tax (%s%%)', 'syncpoint-crm' ), esc_html( $invoice->tax_rate ) ); ?></span>
							<span><?php echo esc_html( scrm_format_currency( $invoice->tax_amount, $invoice->currency ) ); ?></span>
						</div>
					<?php endif; ?>
					<div class="totals-row total">
						<span><?php esc_html_e( 'Total', 'syncpoint-crm' ); ?></span>
						<span class="value"><?php echo esc_html( scrm_format_currency( $invoice->total, $invoice->currency ) ); ?></span>
					</div>
				</div>
			</div>

			<?php if ( 'paid' !== $invoice->status && ( ! empty( $invoice->paypal_payment_link ) || ! empty( $invoice->stripe_payment_link ) ) ) : ?>
				<div class="payment-section">
					<h3><?php esc_html_e( 'Pay This Invoice', 'syncpoint-crm' ); ?></h3>
					<div class="payment-buttons">
						<?php if ( ! empty( $invoice->paypal_payment_link ) ) : ?>
							<a href="<?php echo esc_url( $invoice->paypal_payment_link ); ?>" class="payment-button paypal">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
									<path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.384a.772.772 0 0 1 .762-.648h6.88c2.271 0 4.075.687 5.168 1.906.454.505.78 1.08.972 1.707a5.6 5.6 0 0 1 .227 2.023c-.002.03-.003.058-.006.088-.01.12-.023.242-.038.365-.015.118-.033.237-.054.357-.076.44-.193.868-.355 1.277a5.75 5.75 0 0 1-.912 1.512 5.33 5.33 0 0 1-1.355 1.193c-.51.326-1.095.577-1.746.748-.62.163-1.31.244-2.054.244H9.677a.95.95 0 0 0-.938.806l-.717 4.5-.198 1.24a.507.507 0 0 1-.5.433H7.076z"/>
								</svg>
								<?php esc_html_e( 'Pay with PayPal', 'syncpoint-crm' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $invoice->stripe_payment_link ) ) : ?>
							<a href="<?php echo esc_url( $invoice->stripe_payment_link ); ?>" class="payment-button stripe">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
									<path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
								</svg>
								<?php esc_html_e( 'Pay with Card', 'syncpoint-crm' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( 'paid' === $invoice->status ) : ?>
				<div class="payment-section" style="background: #d1fae5; border-color: #10b981;">
					<h3 style="color: #065f46;">
						✓ <?php esc_html_e( 'Paid', 'syncpoint-crm' ); ?>
						<?php if ( ! empty( $invoice->paid_at ) ) : ?>
							- <?php echo esc_html( scrm_format_date( $invoice->paid_at ) ); ?>
						<?php endif; ?>
					</h3>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $invoice->notes ) ) : ?>
				<div class="notes">
					<h4><?php esc_html_e( 'Notes', 'syncpoint-crm' ); ?></h4>
					<p><?php echo nl2br( esc_html( $invoice->notes ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $invoice->terms ) ) : ?>
				<div class="notes">
					<h4><?php esc_html_e( 'Terms & Conditions', 'syncpoint-crm' ); ?></h4>
					<p><?php echo nl2br( esc_html( $invoice->terms ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
