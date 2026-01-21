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
			'general'       => __( 'General', 'syncpoint-crm' ),
			'paypal'        => __( 'PayPal', 'syncpoint-crm' ),
			'paypal_import' => __( 'PayPal Import', 'syncpoint-crm' ),
			'stripe'        => __( 'Stripe', 'syncpoint-crm' ),
			'invoices'      => __( 'Invoices', 'syncpoint-crm' ),
			'webhooks'      => __( 'Webhooks', 'syncpoint-crm' ),
			'tools'         => __( 'Tools', 'syncpoint-crm' ),
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
					case 'paypal_import':
						$this->render_paypal_import_settings();
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
					case 'tools':
						$this->render_tools_settings();
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
		$last_sync = scrm_get_last_sync( 'paypal' );
		$next_sync = scrm_get_next_sync_time( 'paypal' );
		$is_running = scrm_is_sync_running( 'paypal' );
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

		<h2 class="title"><?php esc_html_e( 'Legacy NVP API (Historical Import)', 'syncpoint-crm' ); ?></h2>
		<p class="description" style="margin-bottom: 15px;">
			<?php esc_html_e( 'The NVP API allows importing historical transactions up to 3 years back. Get these credentials from PayPal Developer Dashboard > API Credentials.', 'syncpoint-crm' ); ?>
		</p>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="paypal_email"><?php esc_html_e( 'PayPal Email', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="email" name="paypal[paypal_email]" id="paypal_email"
						   value="<?php echo esc_attr( $settings['paypal_email'] ?? '' ); ?>"
						   class="regular-text">
					<p class="description"><?php esc_html_e( 'Your PayPal business account email.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_api_username"><?php esc_html_e( 'API Username', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="paypal[api_username]" id="paypal_api_username"
						   value="<?php echo esc_attr( $settings['api_username'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_api_password"><?php esc_html_e( 'API Password', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="password" name="paypal[api_password]" id="paypal_api_password"
						   value="<?php echo esc_attr( $settings['api_password'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_api_signature"><?php esc_html_e( 'API Signature', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="paypal[api_signature]" id="paypal_api_signature"
						   value="<?php echo esc_attr( $settings['api_signature'] ?? '' ); ?>"
						   class="regular-text code">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_first_txn_date"><?php esc_html_e( 'First Transaction Date', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="date" name="paypal[first_txn_date]" id="paypal_first_txn_date"
						   value="<?php echo esc_attr( $settings['first_txn_date'] ?? date( 'Y-m-d', strtotime( '-3 years' ) ) ); ?>">
					<p class="description"><?php esc_html_e( 'Import transactions starting from this date. PayPal allows up to 3 years of history.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Import Historical', 'syncpoint-crm' ); ?></th>
				<td>
					<button type="button" id="scrm-paypal-import-historical" class="button button-secondary" <?php disabled( $is_running ); ?>>
						<?php esc_html_e( 'Import Historical Transactions', 'syncpoint-crm' ); ?>
					</button>
					<span id="scrm-paypal-import-status" style="margin-left: 10px;"></span>
					<p class="description"><?php esc_html_e( 'One-time import using the NVP API. This may take several minutes for large transaction histories.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Transaction Sync', 'syncpoint-crm' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto Sync', 'syncpoint-crm' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="paypal[auto_sync]" value="1"
							<?php checked( ! empty( $settings['auto_sync'] ) ); ?>>
						<?php esc_html_e( 'Enable automatic transaction sync', 'syncpoint-crm' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_sync_frequency"><?php esc_html_e( 'Sync Frequency', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<select name="paypal[sync_frequency]" id="paypal_sync_frequency">
						<option value="hourly" <?php selected( $settings['sync_frequency'] ?? 'daily', 'hourly' ); ?>>
							<?php esc_html_e( 'Hourly', 'syncpoint-crm' ); ?>
						</option>
						<option value="twicedaily" <?php selected( $settings['sync_frequency'] ?? 'daily', 'twicedaily' ); ?>>
							<?php esc_html_e( 'Twice Daily', 'syncpoint-crm' ); ?>
						</option>
						<option value="daily" <?php selected( $settings['sync_frequency'] ?? 'daily', 'daily' ); ?>>
							<?php esc_html_e( 'Daily', 'syncpoint-crm' ); ?>
						</option>
					</select>
					<?php if ( $next_sync ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: next sync time */
								esc_html__( 'Next scheduled sync: %s', 'syncpoint-crm' ),
								esc_html( scrm_format_datetime( date( 'Y-m-d H:i:s', $next_sync ) ) )
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Manual Sync', 'syncpoint-crm' ); ?></th>
				<td>
					<button type="button" id="scrm-paypal-sync-now" class="button button-secondary" <?php disabled( $is_running ); ?>>
						<?php $is_running ? esc_html_e( 'Sync in Progress...', 'syncpoint-crm' ) : esc_html_e( 'Sync Now', 'syncpoint-crm' ); ?>
					</button>
					<span id="scrm-paypal-sync-status" style="margin-left: 10px;"></span>
					<?php if ( $last_sync ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: 1: last sync time, 2: transactions synced */
								esc_html__( 'Last sync: %1$s (%2$d transactions synced)', 'syncpoint-crm' ),
								esc_html( scrm_format_datetime( $last_sync->completed_at ) ),
								absint( $last_sync->transactions_synced )
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php $this->render_sync_history( 'paypal' ); ?>

		<script>
		jQuery(document).ready(function($) {
			$('#scrm-paypal-sync-now').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-paypal-sync-status');

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Syncing...', 'syncpoint-crm' ) ); ?>');
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrm_sync_paypal',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_sync_paypal' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Sync Now', 'syncpoint-crm' ) ); ?>');
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
							setTimeout(function() { location.reload(); }, 2000);
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Sync Now', 'syncpoint-crm' ) ); ?>');
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});

			$('#scrm-paypal-import-historical').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-paypal-import-status');

				if (!confirm('<?php echo esc_js( __( 'This will import all historical transactions from PayPal. This may take several minutes. Continue?', 'syncpoint-crm' ) ); ?>')) {
					return;
				}

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Importing...', 'syncpoint-crm' ) ); ?>');
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					timeout: 300000,
					data: {
						action: 'scrm_sync_paypal_nvp',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_sync_paypal_nvp' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Import Historical Transactions', 'syncpoint-crm' ) ); ?>');
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
							setTimeout(function() { location.reload(); }, 2000);
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Import Historical Transactions', 'syncpoint-crm' ) ); ?>');
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred or the request timed out.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render PayPal Import settings (separate tab).
	 */
	private function render_paypal_import_settings() {
		$settings = scrm_get_settings( 'paypal' );
		$is_running = scrm_is_sync_running( 'paypal' );
		?>
		<h2><?php esc_html_e( 'PayPal Historical Transaction Import', 'syncpoint-crm' ); ?></h2>
		<p class="description" style="margin-bottom: 20px;">
			<?php esc_html_e( 'Import historical transactions from PayPal using the Legacy NVP API. This allows importing transactions up to 3 years back.', 'syncpoint-crm' ); ?>
		</p>

		<h3><?php esc_html_e( 'NVP API Credentials', 'syncpoint-crm' ); ?></h3>
		<p class="description" style="margin-bottom: 15px;">
			<?php esc_html_e( 'Get these credentials from PayPal Developer Dashboard → API Credentials → NVP/SOAP API.', 'syncpoint-crm' ); ?>
		</p>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="paypal_email"><?php esc_html_e( 'PayPal Email', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="email" name="paypal[paypal_email]" id="paypal_email"
						   value="<?php echo esc_attr( $settings['paypal_email'] ?? '' ); ?>"
						   class="regular-text">
					<p class="description"><?php esc_html_e( 'Your PayPal business account email.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_api_username"><?php esc_html_e( 'API Username', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="paypal[api_username]" id="paypal_api_username"
						   value="<?php echo esc_attr( $settings['api_username'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_api_password"><?php esc_html_e( 'API Password', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="password" name="paypal[api_password]" id="paypal_api_password"
						   value="<?php echo esc_attr( $settings['api_password'] ?? '' ); ?>"
						   class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_api_signature"><?php esc_html_e( 'API Signature', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="text" name="paypal[api_signature]" id="paypal_api_signature"
						   value="<?php echo esc_attr( $settings['api_signature'] ?? '' ); ?>"
						   class="regular-text code">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="paypal_first_txn_date"><?php esc_html_e( 'Start Date', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<input type="date" name="paypal[first_txn_date]" id="paypal_first_txn_date"
						   value="<?php echo esc_attr( $settings['first_txn_date'] ?? date( 'Y-m-d', strtotime( '-3 years' ) ) ); ?>">
					<p class="description"><?php esc_html_e( 'Import transactions starting from this date. PayPal allows up to 3 years of history.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Import Transactions', 'syncpoint-crm' ); ?></h3>
		<p>
			<button type="button" id="scrm-paypal-import-historical" class="button button-primary button-hero" <?php disabled( $is_running ); ?>>
				<?php esc_html_e( 'Import Historical Transactions', 'syncpoint-crm' ); ?>
			</button>
		</p>
		<p id="scrm-paypal-import-status"></p>
		<p class="description"><?php esc_html_e( 'This will import all transactions from the start date. This process may take several minutes for large transaction histories.', 'syncpoint-crm' ); ?></p>

		<?php $this->render_sync_history( 'paypal' ); ?>

		<script>
		jQuery(document).ready(function($) {
			$('#scrm-paypal-import-historical').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-paypal-import-status');

				if (!confirm('<?php echo esc_js( __( 'This will import all historical transactions from PayPal. This may take several minutes. Continue?', 'syncpoint-crm' ) ); ?>')) {
					return;
				}

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Importing...', 'syncpoint-crm' ) ); ?>');
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span> <?php echo esc_js( __( 'Importing transactions, please wait...', 'syncpoint-crm' ) ); ?>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					timeout: 300000,
					data: {
						action: 'scrm_sync_paypal_nvp',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_sync_paypal_nvp' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Import Historical Transactions', 'syncpoint-crm' ) ); ?>');
						if (response.success) {
							$status.html('<span style="color: green; font-weight: bold;">' + response.data.message + '</span>');
							setTimeout(function() { location.reload(); }, 2000);
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Import Historical Transactions', 'syncpoint-crm' ) ); ?>');
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred or the request timed out.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render sync history table.
	 *
	 * @param string $gateway Gateway name.
	 */
	private function render_sync_history( $gateway ) {
		$logs = scrm_get_sync_logs( $gateway, 5 );

		if ( empty( $logs ) ) {
			return;
		}
		?>
		<h3><?php esc_html_e( 'Sync History', 'syncpoint-crm' ); ?></h3>
		<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Synced', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Skipped', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Contacts', 'syncpoint-crm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( scrm_format_datetime( $log->started_at ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $log->sync_type ) ); ?></td>
						<td>
							<?php
							$status_class = 'completed' === $log->status ? 'success' : ( 'running' === $log->status ? 'warning' : 'error' );
							?>
							<span class="scrm-status scrm-status--<?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( ucfirst( $log->status ) ); ?>
							</span>
							<?php if ( ! empty( $log->error_message ) ) : ?>
								<br><small style="color: red;"><?php echo esc_html( $log->error_message ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo absint( $log->transactions_synced ); ?></td>
						<td><?php echo absint( $log->transactions_skipped ); ?></td>
						<td><?php echo absint( $log->contacts_created ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Stripe settings.
	 */
	private function render_stripe_settings() {
		$settings = scrm_get_settings( 'stripe' );
		$last_sync = scrm_get_last_sync( 'stripe' );
		$next_sync = scrm_get_next_sync_time( 'stripe' );
		$is_running = scrm_is_sync_running( 'stripe' );
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

		<h2 class="title"><?php esc_html_e( 'Transaction Sync', 'syncpoint-crm' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto Sync', 'syncpoint-crm' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="stripe[auto_sync]" value="1"
							<?php checked( ! empty( $settings['auto_sync'] ) ); ?>>
						<?php esc_html_e( 'Enable automatic transaction sync', 'syncpoint-crm' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="stripe_sync_frequency"><?php esc_html_e( 'Sync Frequency', 'syncpoint-crm' ); ?></label>
				</th>
				<td>
					<select name="stripe[sync_frequency]" id="stripe_sync_frequency">
						<option value="hourly" <?php selected( $settings['sync_frequency'] ?? 'daily', 'hourly' ); ?>>
							<?php esc_html_e( 'Hourly', 'syncpoint-crm' ); ?>
						</option>
						<option value="twicedaily" <?php selected( $settings['sync_frequency'] ?? 'daily', 'twicedaily' ); ?>>
							<?php esc_html_e( 'Twice Daily', 'syncpoint-crm' ); ?>
						</option>
						<option value="daily" <?php selected( $settings['sync_frequency'] ?? 'daily', 'daily' ); ?>>
							<?php esc_html_e( 'Daily', 'syncpoint-crm' ); ?>
						</option>
					</select>
					<?php if ( $next_sync ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: next sync time */
								esc_html__( 'Next scheduled sync: %s', 'syncpoint-crm' ),
								esc_html( scrm_format_datetime( date( 'Y-m-d H:i:s', $next_sync ) ) )
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Manual Sync', 'syncpoint-crm' ); ?></th>
				<td>
					<button type="button" id="scrm-stripe-sync-now" class="button button-secondary" <?php disabled( $is_running ); ?>>
						<?php $is_running ? esc_html_e( 'Sync in Progress...', 'syncpoint-crm' ) : esc_html_e( 'Sync Now', 'syncpoint-crm' ); ?>
					</button>
					<span id="scrm-stripe-sync-status" style="margin-left: 10px;"></span>
					<?php if ( $last_sync ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: 1: last sync time, 2: transactions synced */
								esc_html__( 'Last sync: %1$s (%2$d transactions synced)', 'syncpoint-crm' ),
								esc_html( scrm_format_datetime( $last_sync->completed_at ) ),
								absint( $last_sync->transactions_synced )
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php $this->render_sync_history( 'stripe' ); ?>

		<script>
		jQuery(document).ready(function($) {
			$('#scrm-stripe-sync-now').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-stripe-sync-status');

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Syncing...', 'syncpoint-crm' ) ); ?>');
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrm_sync_stripe',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_sync_stripe' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Sync Now', 'syncpoint-crm' ) ); ?>');
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
							setTimeout(function() { location.reload(); }, 2000);
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Sync Now', 'syncpoint-crm' ) ); ?>');
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});
		});
		</script>
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
	 * Render Tools settings.
	 */
	private function render_tools_settings() {
		global $wpdb;

		// Get table status.
		$tables = array(
			'scrm_contacts'          => __( 'Contacts', 'syncpoint-crm' ),
			'scrm_companies'         => __( 'Companies', 'syncpoint-crm' ),
			'scrm_transactions'      => __( 'Transactions', 'syncpoint-crm' ),
			'scrm_invoices'          => __( 'Invoices', 'syncpoint-crm' ),
			'scrm_invoice_items'     => __( 'Invoice Items', 'syncpoint-crm' ),
			'scrm_tags'              => __( 'Tags', 'syncpoint-crm' ),
			'scrm_tag_relationships' => __( 'Tag Relationships', 'syncpoint-crm' ),
			'scrm_activity_log'      => __( 'Activity Log', 'syncpoint-crm' ),
			'scrm_webhook_log'       => __( 'Webhook Log', 'syncpoint-crm' ),
			'scrm_sync_log'          => __( 'Sync Log', 'syncpoint-crm' ),
			'scrm_email_log'         => __( 'Email Log', 'syncpoint-crm' ),
		);

		$table_status = array();
		foreach ( $tables as $table => $label ) {
			$full_name = $wpdb->prefix . $table;
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_name ) ) === $full_name;
			$rows = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM {$full_name}" ) : 0;
			$table_status[ $table ] = array(
				'label'  => $label,
				'exists' => $exists,
				'rows'   => $rows,
			);
		}
		?>
		<h2><?php esc_html_e( 'Database Tools', 'syncpoint-crm' ); ?></h2>

		<h3><?php esc_html_e( 'Table Status', 'syncpoint-crm' ); ?></h3>
		<table class="wp-list-table widefat fixed striped" style="max-width: 600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Table', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Records', 'syncpoint-crm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $table_status as $table => $info ) : ?>
					<tr>
						<td><code><?php echo esc_html( $wpdb->prefix . $table ); ?></code></td>
						<td>
							<?php if ( $info['exists'] ) : ?>
								<span style="color: green;">&#10004; <?php esc_html_e( 'OK', 'syncpoint-crm' ); ?></span>
							<?php else : ?>
								<span style="color: red;">&#10008; <?php esc_html_e( 'Missing', 'syncpoint-crm' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo $info['exists'] ? number_format_i18n( $info['rows'] ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3 style="margin-top: 30px;"><?php esc_html_e( 'Database Actions', 'syncpoint-crm' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Recreate Tables', 'syncpoint-crm' ); ?></th>
				<td>
					<button type="button" id="scrm-recreate-tables" class="button button-secondary">
						<?php esc_html_e( 'Recreate Missing Tables', 'syncpoint-crm' ); ?>
					</button>
					<span id="scrm-recreate-status" style="margin-left: 10px;"></span>
					<p class="description"><?php esc_html_e( 'Creates any missing database tables. Existing tables will not be affected.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Optimize Tables', 'syncpoint-crm' ); ?></th>
				<td>
					<button type="button" id="scrm-optimize-tables" class="button button-secondary">
						<?php esc_html_e( 'Optimize All Tables', 'syncpoint-crm' ); ?>
					</button>
					<span id="scrm-optimize-status" style="margin-left: 10px;"></span>
					<p class="description"><?php esc_html_e( 'Optimizes database tables for better performance.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
		</table>

		<h3 style="margin-top: 30px;"><?php esc_html_e( 'Export Data', 'syncpoint-crm' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Export All Data', 'syncpoint-crm' ); ?></th>
				<td>
					<button type="button" id="scrm-export-all" class="button button-primary">
						<?php esc_html_e( 'Export All Data (CSV)', 'syncpoint-crm' ); ?>
					</button>
					<span id="scrm-export-status" style="margin-left: 10px;"></span>
					<p class="description"><?php esc_html_e( 'Downloads a ZIP file containing CSV exports of all CRM data.', 'syncpoint-crm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Export Contacts', 'syncpoint-crm' ); ?></th>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=export' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Export Contacts Only', 'syncpoint-crm' ); ?>
					</a>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			$('#scrm-recreate-tables').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-recreate-status');

				$button.prop('disabled', true);
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrm_recreate_tables',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_recreate_tables' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false);
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
							setTimeout(function() { location.reload(); }, 1500);
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false);
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});

			$('#scrm-optimize-tables').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-optimize-status');

				$button.prop('disabled', true);
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrm_optimize_tables',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_optimize_tables' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false);
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false);
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});

			$('#scrm-export-all').on('click', function() {
				var $button = $(this);
				var $status = $('#scrm-export-status');

				$button.prop('disabled', true);
				$status.html('<span class="spinner is-active" style="float: none; margin: 0;"></span> <?php echo esc_js( __( 'Preparing export...', 'syncpoint-crm' ) ); ?>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrm_export_all',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_export_all' ) ); ?>'
					},
					success: function(response) {
						$button.prop('disabled', false);
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
							window.location.href = response.data.download_url;
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false);
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
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
