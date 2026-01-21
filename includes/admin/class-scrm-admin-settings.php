<?php
/**
 * Admin Settings
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Admin_Settings
 *
 * Handles settings page rendering and saving.
 *
 * @since 1.0.0
 */
class SCRM_Admin_Settings {

	/**
	 * Settings tabs.
	 *
	 * @var array
	 */
	private $tabs = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tabs = array(
			'general'  => __( 'General', 'syncpoint-crm' ),
			'paypal'   => __( 'PayPal', 'syncpoint-crm' ),
			'stripe'   => __( 'Stripe', 'syncpoint-crm' ),
			'invoices' => __( 'Invoices', 'syncpoint-crm' ),
			'webhooks' => __( 'Webhooks', 'syncpoint-crm' ),
		);

		add_action( 'admin_init', array( $this, 'save_settings' ) );
	}

	/**
	 * Render settings page.
	 */
	public function render() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

		if ( ! isset( $this->tabs[ $current_tab ] ) ) {
			$current_tab = 'general';
		}

		?>
		<div class="wrap scrm-wrap">
			<h1><?php esc_html_e( 'Settings', 'syncpoint-crm' ); ?></h1>

			<?php $this->render_tabs( $current_tab ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'scrm_save_settings', '_scrm_nonce' ); ?>
				<input type="hidden" name="scrm_settings_tab" value="<?php echo esc_attr( $current_tab ); ?>">

				<?php
				switch ( $current_tab ) {
					case 'general':
						$this->render_general_settings();
						break;
					case 'paypal':
						$this->render_paypal_settings();
						break;
					case 'stripe':
						$this->render_stripe_settings();
						break;
					case 'invoices':
						$this->render_invoices_settings();
						break;
					case 'webhooks':
						$this->render_webhooks_settings();
						break;
				}
				?>

				<p class="submit">
					<button type="submit" name="scrm_save_settings" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'syncpoint-crm' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render tabs.
	 *
	 * @param string $current_tab Current tab.
	 */
	private function render_tabs( $current_tab ) {
		?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $this->tabs as $tab_id => $tab_name ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-settings&tab=' . $tab_id ) ); ?>"
				   class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tab_name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render general settings.
	 */
	private function render_general_settings() {
		$settings = scrm_get_settings( 'general' );
		$currencies = scrm_get_currencies();
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="default_currency"><?php esc_html_e( 'Default Currency', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<select name="general[default_currency]" id="default_currency">
						<?php foreach ( $currencies as $code => $data ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>"
								<?php selected( $settings['default_currency'] ?? 'USD', $code ); ?>>
								<?php echo esc_html( $code . ' - ' . $data['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="contact_id_prefix"><?php esc_html_e( 'Contact ID Prefix', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="general[contact_id_prefix]" id="contact_id_prefix"
						   value="<?php echo esc_attr( $settings['contact_id_prefix'] ?? 'CUST' ); ?>"
						   class="regular-text">
					<p class="description"><?php esc_html_e( 'Prefix for contact IDs (e.g., CUST-001)', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="company_id_prefix"><?php esc_html_e( 'Company ID Prefix', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="general[company_id_prefix]" id="company_id_prefix"
						   value="<?php echo esc_attr( $settings['company_id_prefix'] ?? 'COMP' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="invoice_prefix"><?php esc_html_e( 'Invoice Prefix', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="general[invoice_prefix]" id="invoice_prefix"
						   value="<?php echo esc_attr( $settings['invoice_prefix'] ?? 'INV' ); ?>"
						   class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render PayPal settings.
	 */
	private function render_paypal_settings() {
		$settings = scrm_get_settings( 'paypal' );
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable PayPal', 'syncpoint-crm' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="paypal[enabled]" value="1"
							<?php checked( ! empty( $settings['enabled'] ) ); ?>>
						<?php esc_html_e( 'Enable PayPal integration', 'syncpoint-crm' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_mode"><?php esc_html_e( 'Mode', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<select name="paypal[mode]" id="paypal_mode">
						<option value="sandbox" <?php selected( $settings['mode'] ?? 'sandbox', 'sandbox' ); ?>>
							<?php esc_html_e( 'Sandbox (Testing)', 'syncpoint-crm' ); ?>
						</option>
						<option value="live" <?php selected( $settings['mode'] ?? '', 'live' ); ?>>
							<?php esc_html_e( 'Live', 'syncpoint-crm' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_client_id"><?php esc_html_e( 'Client ID', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="paypal[client_id]" id="paypal_client_id"
						   value="<?php echo esc_attr( $settings['client_id'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_client_secret"><?php esc_html_e( 'Client Secret', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="password" name="paypal[client_secret]" id="paypal_client_secret"
						   value="<?php echo esc_attr( $settings['client_secret'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_webhook_id"><?php esc_html_e( 'Webhook ID', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="paypal[webhook_id]" id="paypal_webhook_id"
						   value="<?php echo esc_attr( $settings['webhook_id'] ?? '' ); ?>"
						   class="regular-text">
					<p class="description">
						<?php
						printf(
							/* translators: %s: webhook URL */
							esc_html__( 'Webhook URL: %s', 'syncpoint-crm' ),
							'<code>' . esc_url( rest_url( 'scrm/v1/webhooks/paypal' ) ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Stripe settings.
	 */
	private function render_stripe_settings() {
		$settings = scrm_get_settings( 'stripe' );
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Stripe', 'syncpoint-crm' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="stripe[enabled]" value="1"
							<?php checked( ! empty( $settings['enabled'] ) ); ?>>
						<?php esc_html_e( 'Enable Stripe integration', 'syncpoint-crm' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_mode"><?php esc_html_e( 'Mode', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<select name="stripe[mode]" id="stripe_mode">
						<option value="test" <?php selected( $settings['mode'] ?? 'test', 'test' ); ?>>
							<?php esc_html_e( 'Test', 'syncpoint-crm' ); ?>
						</option>
						<option value="live" <?php selected( $settings['mode'] ?? '', 'live' ); ?>>
							<?php esc_html_e( 'Live', 'syncpoint-crm' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" colspan="2">
					<h3><?php esc_html_e( 'Test Keys', 'syncpoint-crm' ); ?></h3>
				</th>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_test_publishable"><?php esc_html_e( 'Test Publishable Key', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="stripe[test_publishable]" id="stripe_test_publishable"
						   value="<?php echo esc_attr( $settings['test_publishable'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_test_secret"><?php esc_html_e( 'Test Secret Key', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="password" name="stripe[test_secret]" id="stripe_test_secret"
						   value="<?php echo esc_attr( $settings['test_secret'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row" colspan="2">
					<h3><?php esc_html_e( 'Live Keys', 'syncpoint-crm' ); ?></h3>
				</th>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_live_publishable"><?php esc_html_e( 'Live Publishable Key', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="stripe[live_publishable]" id="stripe_live_publishable"
						   value="<?php echo esc_attr( $settings['live_publishable'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_live_secret"><?php esc_html_e( 'Live Secret Key', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="password" name="stripe[live_secret]" id="stripe_live_secret"
						   value="<?php echo esc_attr( $settings['live_secret'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row" colspan="2">
					<h3><?php esc_html_e( 'Webhook', 'syncpoint-crm' ); ?></h3>
				</th>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_webhook_secret"><?php esc_html_e( 'Webhook Signing Secret', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="password" name="stripe[webhook_secret]" id="stripe_webhook_secret"
						   value="<?php echo esc_attr( $settings['webhook_secret'] ?? '' ); ?>"
						   class="regular-text">
					<p class="description">
						<?php
						printf(
							/* translators: %s: webhook URL */
							esc_html__( 'Webhook URL: %s', 'syncpoint-crm' ),
							'<code>' . esc_url( rest_url( 'scrm/v1/webhooks/stripe' ) ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render invoices settings.
	 */
	private function render_invoices_settings() {
		$settings = scrm_get_settings( 'invoices' );
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="company_name"><?php esc_html_e( 'Company Name', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="invoices[company_name]" id="company_name"
						   value="<?php echo esc_attr( $settings['company_name'] ?? get_bloginfo( 'name' ) ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="company_address"><?php esc_html_e( 'Company Address', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<textarea name="invoices[company_address]" id="company_address"
							  rows="3" class="large-text"><?php echo esc_textarea( $settings['company_address'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="company_tax_id"><?php esc_html_e( 'Tax ID / VAT Number', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="invoices[company_tax_id]" id="company_tax_id"
						   value="<?php echo esc_attr( $settings['company_tax_id'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="company_logo"><?php esc_html_e( 'Logo URL', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="url" name="invoices[company_logo]" id="company_logo"
						   value="<?php echo esc_url( $settings['company_logo'] ?? '' ); ?>"
						   class="regular-text">
					<p class="description"><?php esc_html_e( 'URL to your logo image for invoices.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="default_terms"><?php esc_html_e( 'Default Terms', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<textarea name="invoices[default_terms]" id="default_terms"
							  rows="3" class="large-text"><?php echo esc_textarea( $settings['default_terms'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="default_notes"><?php esc_html_e( 'Default Notes', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<textarea name="invoices[default_notes]" id="default_notes"
							  rows="3" class="large-text"><?php echo esc_textarea( $settings['default_notes'] ?? '' ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render webhooks settings.
	 */
	private function render_webhooks_settings() {
		$settings = scrm_get_settings( 'webhooks' );
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Webhooks', 'syncpoint-crm' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="webhooks[enabled]" value="1"
							<?php checked( $settings['enabled'] ?? true ); ?>>
						<?php esc_html_e( 'Accept incoming webhooks', 'syncpoint-crm' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="webhook_secret_key"><?php esc_html_e( 'Secret Key', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="webhooks[secret_key]" id="webhook_secret_key"
						   value="<?php echo esc_attr( $settings['secret_key'] ?? '' ); ?>"
						   class="regular-text" readonly>
					<button type="button" class="button" id="scrm-regenerate-key">
						<?php esc_html_e( 'Regenerate', 'syncpoint-crm' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Use this key in the X-SCRM-Webhook-Key header when sending webhooks.', 'syncpoint-crm' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="allowed_ips"><?php esc_html_e( 'Allowed IPs', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<textarea name="webhooks[allowed_ips]" id="allowed_ips"
							  rows="5" class="large-text"><?php echo esc_textarea( $settings['allowed_ips'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One IP per line. Leave empty to allow all IPs.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Webhook URL', 'syncpoint-crm' ); ?></th>
				<td>
					<code><?php echo esc_url( rest_url( 'scrm/v1/webhooks/inbound' ) ); ?></code>
					<p class="description"><?php esc_html_e( 'Send POST requests to this URL.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			$('#scrm-regenerate-key').on('click', function() {
				var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
				var key = '';
				for (var i = 0; i < 32; i++) {
					key += chars.charAt(Math.floor(Math.random() * chars.length));
				}
				$('#webhook_secret_key').val(key);
			});
		});
		</script>
		<?php
	}

	/**
	 * Save settings.
	 */
	public function save_settings() {
		if ( ! isset( $_POST['scrm_save_settings'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_scrm_nonce'] ?? '', 'scrm_save_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = sanitize_text_field( $_POST['scrm_settings_tab'] ?? 'general' );

		if ( ! isset( $_POST[ $tab ] ) || ! is_array( $_POST[ $tab ] ) ) {
			return;
		}

		$settings = get_option( 'scrm_settings', array() );
		$new_settings = array();

		// Sanitize based on tab.
		foreach ( $_POST[ $tab ] as $key => $value ) {
			if ( is_array( $value ) ) {
				$new_settings[ $key ] = array_map( 'sanitize_text_field', $value );
			} else {
				$new_settings[ $key ] = sanitize_text_field( $value );
			}
		}

		// Handle checkboxes (not submitted when unchecked).
		if ( 'paypal' === $tab && ! isset( $_POST['paypal']['enabled'] ) ) {
			$new_settings['enabled'] = false;
		}
		if ( 'stripe' === $tab && ! isset( $_POST['stripe']['enabled'] ) ) {
			$new_settings['enabled'] = false;
		}
		if ( 'webhooks' === $tab && ! isset( $_POST['webhooks']['enabled'] ) ) {
			$new_settings['enabled'] = false;
		}

		// Preserve number counters for general tab.
		if ( 'general' === $tab ) {
			$old = $settings['general'] ?? array();
			$new_settings['next_contact_number']     = $old['next_contact_number'] ?? 1;
			$new_settings['next_company_number']     = $old['next_company_number'] ?? 1;
			$new_settings['next_invoice_number']     = $old['next_invoice_number'] ?? 1;
			$new_settings['next_transaction_number'] = $old['next_transaction_number'] ?? 1;
		}

		$settings[ $tab ] = $new_settings;

		update_option( 'scrm_settings', $settings );

		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Settings saved.', 'syncpoint-crm' ) . '</p></div>';
		} );
	}
}
