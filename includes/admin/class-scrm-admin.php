<?php
/**
 * Admin Controller
 *
 * Handles all admin functionality including menus, assets, and page rendering.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Admin
 *
 * @since 1.0.0
 */
class SCRM_Admin {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * Admin init hook.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		/**
		 * Fires on admin pages after initialization.
		 *
		 * @since 1.0.0
		 */
		do_action( 'scrm_admin_init' );
	}

	/**
	 * Register admin menus.
	 *
	 * @since 1.0.0
	 */
	public function register_menus() {
		/**
		 * Filter the capability required to access admin menus.
		 *
		 * @since 1.0.0
		 * @param string $capability Capability name.
		 */
		$capability = apply_filters( 'scrm_admin_menu_capability', 'manage_options' );

		// Main menu.
		add_menu_page(
			__( 'Starter CRM', 'syncpoint-crm' ),
			__( 'CRM', 'syncpoint-crm' ),
			$capability,
			'syncpoint-crm',
			array( $this, 'render_dashboard' ),
			'dashicons-businessperson',
			30
		);

		// Dashboard (same as main).
		add_submenu_page(
			'syncpoint-crm',
			__( 'Dashboard', 'syncpoint-crm' ),
			__( 'Dashboard', 'syncpoint-crm' ),
			$capability,
			'syncpoint-crm',
			array( $this, 'render_dashboard' )
		);

		// Contacts.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Contacts', 'syncpoint-crm' ),
			__( 'Contacts', 'syncpoint-crm' ),
			$capability,
			'scrm-contacts',
			array( $this, 'render_contacts' )
		);

		// Companies.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Companies', 'syncpoint-crm' ),
			__( 'Companies', 'syncpoint-crm' ),
			$capability,
			'scrm-companies',
			array( $this, 'render_companies' )
		);

		// Transactions.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Transactions', 'syncpoint-crm' ),
			__( 'Transactions', 'syncpoint-crm' ),
			$capability,
			'scrm-transactions',
			array( $this, 'render_transactions' )
		);

		// Invoices.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Invoices', 'syncpoint-crm' ),
			__( 'Invoices', 'syncpoint-crm' ),
			$capability,
			'scrm-invoices',
			array( $this, 'render_invoices' )
		);

		// Tags.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Tags', 'syncpoint-crm' ),
			__( 'Tags', 'syncpoint-crm' ),
			$capability,
			'scrm-tags',
			array( $this, 'render_tags' )
		);

		// Email.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Email', 'syncpoint-crm' ),
			__( 'Email', 'syncpoint-crm' ),
			$capability,
			'scrm-email',
			array( $this, 'render_email' )
		);

		// Import.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Import', 'syncpoint-crm' ),
			__( 'Import', 'syncpoint-crm' ),
			$capability,
			'scrm-import',
			array( $this, 'render_import' )
		);

		// Settings.
		add_submenu_page(
			'syncpoint-crm',
			__( 'Settings', 'syncpoint-crm' ),
			__( 'Settings', 'syncpoint-crm' ),
			$capability,
			'scrm-settings',
			array( $this, 'render_settings' )
		);

		/**
		 * Fires after admin menus are registered.
		 *
		 * @since 1.0.0
		 * @param string $parent_slug The parent menu slug.
		 */
		do_action( 'scrm_admin_menu', 'syncpoint-crm' );
	}

	/**
	 * Conditionally enqueue assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our plugin pages.
		if ( ! $this->is_scrm_page( $hook ) ) {
			return;
		}

		// Core admin styles.
		wp_enqueue_style(
			'scrm-admin',
			SCRM_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SCRM_VERSION
		);

		// Core admin scripts.
		wp_enqueue_script(
			'scrm-admin',
			SCRM_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SCRM_VERSION,
			true
		);

		// Localize script.
		wp_localize_script( 'scrm-admin', 'scrm', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'scrm_admin_nonce' ),
			'i18n'     => array(
				'confirm_delete' => __( 'Are you sure you want to delete this item?', 'syncpoint-crm' ),
				'confirm_cancel' => __( 'Are you sure you want to cancel this sync?', 'syncpoint-crm' ),
				'saving'         => __( 'Saving...', 'syncpoint-crm' ),
				'saved'          => __( 'Saved!', 'syncpoint-crm' ),
				'error'          => __( 'An error occurred. Please try again.', 'syncpoint-crm' ),
			),
		) );

		// Charts only on dashboard.
		if ( $this->is_dashboard_page( $hook ) ) {
			wp_enqueue_script(
				'chart-js',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
				array(),
				'4.4.0',
				true
			);

			wp_enqueue_script(
				'scrm-charts',
				SCRM_PLUGIN_URL . 'assets/js/charts.js',
				array( 'chart-js' ),
				SCRM_VERSION,
				true
			);

			// Pass chart data.
			wp_localize_script( 'scrm-charts', 'scrm_charts_data', array(
				'revenue'  => SCRM_Dashboard::get_revenue_chart_data(),
				'contacts' => SCRM_Dashboard::get_contacts_chart_data(),
			) );
		}

		// Import wizard scripts.
		if ( $this->is_import_page( $hook ) ) {
			wp_enqueue_script(
				'scrm-import',
				SCRM_PLUGIN_URL . 'assets/js/import.js',
				array( 'jquery' ),
				SCRM_VERSION,
				true
			);
		}
	}

	/**
	 * Check if current page is a SCRM page.
	 *
	 * @since 1.0.0
	 * @param string $hook Page hook.
	 * @return bool True if SCRM page.
	 */
	private function is_scrm_page( $hook ) {
		$scrm_pages = array(
			'toplevel_page_syncpoint-crm',
			'crm_page_scrm-contacts',
			'crm_page_scrm-companies',
			'crm_page_scrm-transactions',
			'crm_page_scrm-invoices',
			'crm_page_scrm-tags',
			'crm_page_scrm-import',
			'crm_page_scrm-settings',
		);

		return in_array( $hook, $scrm_pages, true );
	}

	/**
	 * Check if current page is dashboard.
	 *
	 * @since 1.0.0
	 * @param string $hook Page hook.
	 * @return bool True if dashboard.
	 */
	private function is_dashboard_page( $hook ) {
		return 'toplevel_page_syncpoint-crm' === $hook;
	}

	/**
	 * Check if current page is import.
	 *
	 * @since 1.0.0
	 * @param string $hook Page hook.
	 * @return bool True if import page.
	 */
	private function is_import_page( $hook ) {
		return 'crm_page_scrm-import' === $hook;
	}

	/**
	 * Render dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard() {
		/**
		 * Fires before dashboard rendering.
		 *
		 * @since 1.0.0
		 */
		do_action( 'scrm_before_dashboard_render' );

		?>
		<div class="wrap scrm-wrap">
			<h1><?php esc_html_e( 'Dashboard', 'syncpoint-crm' ); ?></h1>
			
			<div class="scrm-dashboard">
				<!-- Stats Row -->
				<div class="scrm-stats-row">
					<?php $this->render_stat_cards(); ?>
				</div>
				
				<!-- Charts Row -->
				<div class="scrm-charts-row">
					<div class="scrm-chart-container">
						<h3><?php esc_html_e( 'Revenue Overview', 'syncpoint-crm' ); ?></h3>
						<canvas id="scrm-revenue-chart"></canvas>
					</div>
					<div class="scrm-chart-container">
						<h3><?php esc_html_e( 'Contact Growth', 'syncpoint-crm' ); ?></h3>
						<canvas id="scrm-contacts-chart"></canvas>
					</div>
				</div>
				
				<!-- Widgets Row -->
				<div class="scrm-widgets-row">
					<?php
					/**
					 * Dashboard widgets hook.
					 *
					 * @since 1.0.0
					 */
					do_action( 'scrm_dashboard_widgets' );
					?>
				</div>
			</div>
		</div>
		<?php

		/**
		 * Fires after dashboard rendering.
		 *
		 * @since 1.0.0
		 */
		do_action( 'scrm_after_dashboard_render' );
	}

	/**
	 * Render stat cards.
	 *
	 * @since 1.0.0
	 */
	private function render_stat_cards() {
		$contact_count = scrm_count_contacts( array( 'status' => 'active' ) );
		// TODO: Add transaction and invoice counts.
		?>
		<div class="scrm-stat-card">
			<span class="scrm-stat-icon dashicons dashicons-groups"></span>
			<div class="scrm-stat-content">
				<span class="scrm-stat-number"><?php echo esc_html( number_format_i18n( $contact_count ) ); ?></span>
				<span class="scrm-stat-label"><?php esc_html_e( 'Active Contacts', 'syncpoint-crm' ); ?></span>
			</div>
		</div>
		
		<div class="scrm-stat-card">
			<span class="scrm-stat-icon dashicons dashicons-building"></span>
			<div class="scrm-stat-content">
				<span class="scrm-stat-number">0</span>
				<span class="scrm-stat-label"><?php esc_html_e( 'Companies', 'syncpoint-crm' ); ?></span>
			</div>
		</div>
		
		<div class="scrm-stat-card">
			<span class="scrm-stat-icon dashicons dashicons-chart-area"></span>
			<div class="scrm-stat-content">
				<span class="scrm-stat-number"><?php echo esc_html( scrm_format_currency( 0 ) ); ?></span>
				<span class="scrm-stat-label"><?php esc_html_e( 'Revenue (30 days)', 'syncpoint-crm' ); ?></span>
			</div>
		</div>
		
		<div class="scrm-stat-card">
			<span class="scrm-stat-icon dashicons dashicons-media-text"></span>
			<div class="scrm-stat-content">
				<span class="scrm-stat-number">0</span>
				<span class="scrm-stat-label"><?php esc_html_e( 'Pending Invoices', 'syncpoint-crm' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render contacts page.
	 *
	 * @since 1.0.0
	 */
	public function render_contacts() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		// Handle bulk email action.
		if ( isset( $_GET['action2'] ) && 'email' === $_GET['action2'] || isset( $_GET['action'] ) && 'email' === $_GET['action'] ) {
			$contact_ids = isset( $_GET['contact'] ) ? array_map( 'absint', (array) $_GET['contact'] ) : array();
			if ( ! empty( $contact_ids ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=scrm-email&action=compose&contacts=' . implode( ',', $contact_ids ) ) );
				exit;
			}
		}

		?>
		<div class="wrap scrm-wrap">
			<?php if ( 'edit' === $action || 'add' === $action ) : ?>
				<h1>
					<?php echo 'add' === $action ? esc_html__( 'Add Contact', 'syncpoint-crm' ) : esc_html__( 'Edit Contact', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_contact_form(); ?>
			<?php else : ?>
				<h1>
					<?php esc_html_e( 'Contacts', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_contacts_list(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render contacts list.
	 *
	 * @since 1.0.0
	 */
	private function render_contacts_list() {
		// TODO: Implement WP_List_Table for contacts.
		$contacts = scrm_get_contacts( array( 'limit' => 50 ) );
		
		if ( empty( $contacts ) ) {
			?>
			<div class="scrm-empty-state">
				<span class="dashicons dashicons-groups"></span>
				<h2><?php esc_html_e( 'No contacts yet', 'syncpoint-crm' ); ?></h2>
				<p><?php esc_html_e( 'Get started by adding your first contact.', 'syncpoint-crm' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Add Your First Contact', 'syncpoint-crm' ); ?>
				</a>
			</div>
			<?php
			return;
		}
		
		?>
		<table class="wp-list-table widefat fixed striped scrm-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Name', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Created', 'syncpoint-crm' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'syncpoint-crm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $contacts as $contact ) : ?>
					<tr>
						<td><?php echo esc_html( $contact->contact_id ); ?></td>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=edit&id=' . $contact->id ) ); ?>">
									<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ?: '—' ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html( $contact->email ); ?></td>
						<td>
							<span class="scrm-badge scrm-badge--<?php echo esc_attr( $contact->type ); ?>">
								<?php echo esc_html( ucfirst( $contact->type ) ); ?>
							</span>
						</td>
						<td>
							<span class="scrm-status scrm-status--<?php echo esc_attr( $contact->status ); ?>">
								<?php echo esc_html( ucfirst( $contact->status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( scrm_format_date( $contact->created_at ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=edit&id=' . $contact->id ) ); ?>"><?php esc_html_e( 'Edit', 'syncpoint-crm' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render contact form.
	 *
	 * @since 1.0.0
	 */
	private function render_contact_form() {
		$contact = null;
		$contact_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		
		if ( $contact_id ) {
			$contact = scrm_get_contact( $contact_id );
		}
		
		// Handle form submission.
		if ( isset( $_POST['scrm_save_contact'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'scrm_save_contact' ) ) {
			$data = array(
				'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
				'last_name'  => sanitize_text_field( $_POST['last_name'] ?? '' ),
				'email'      => sanitize_email( $_POST['email'] ?? '' ),
				'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
				'type'       => sanitize_text_field( $_POST['type'] ?? 'customer' ),
				'status'     => sanitize_text_field( $_POST['status'] ?? 'active' ),
				'company_id' => absint( $_POST['company_id'] ?? 0 ) ?: null,
				'currency'   => sanitize_text_field( $_POST['currency'] ?? scrm_get_default_currency() ),
			);
			
			if ( $contact_id ) {
				$result = scrm_update_contact( $contact_id, $data );
			} else {
				$result = scrm_create_contact( $data );
			}
			
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				$redirect_id = $contact_id ?: $result;
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Contact saved successfully.', 'syncpoint-crm' ) . '</p></div>';
				
				// Refresh contact data.
				$contact = scrm_get_contact( $redirect_id );
			}
		}
		
		$types = scrm_get_contact_types();
		$statuses = scrm_get_contact_statuses();
		$currencies = scrm_get_currencies();
		
		?>
		<form method="post" class="scrm-form">
			<?php wp_nonce_field( 'scrm_save_contact' ); ?>
			
			<table class="form-table">
				<tr>
					<th><label for="first_name"><?php esc_html_e( 'First Name', 'syncpoint-crm' ); ?></label></th>
					<td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $contact->first_name ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="last_name"><?php esc_html_e( 'Last Name', 'syncpoint-crm' ); ?></label></th>
					<td><input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $contact->last_name ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?> <span class="required">*</span></label></th>
					<td><input type="email" id="email" name="email" value="<?php echo esc_attr( $contact->email ?? '' ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th><label for="phone"><?php esc_html_e( 'Phone', 'syncpoint-crm' ); ?></label></th>
					<td><input type="tel" id="phone" name="phone" value="<?php echo esc_attr( $contact->phone ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="type"><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></label></th>
					<td>
						<select id="type" name="type">
							<?php foreach ( $types as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $contact->type ?? 'customer', $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="status"><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></label></th>
					<td>
						<select id="status" name="status">
							<?php foreach ( $statuses as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $contact->status ?? 'active', $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="currency"><?php esc_html_e( 'Currency', 'syncpoint-crm' ); ?></label></th>
					<td>
						<select id="currency" name="currency">
							<?php foreach ( $currencies as $code => $data ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $contact->currency ?? scrm_get_default_currency(), $code ); ?>><?php echo esc_html( $code . ' - ' . $data['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<button type="submit" name="scrm_save_contact" class="button button-primary"><?php esc_html_e( 'Save Contact', 'syncpoint-crm' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Render companies page.
	 *
	 * @since 1.0.0
	 */
	public function render_companies() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		
		?>
		<div class="wrap scrm-wrap">
			<?php if ( 'add' === $action ) : ?>
				<h1>
					<?php esc_html_e( 'Add Company', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-companies' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_company_form(); ?>
			<?php elseif ( 'view' === $action && isset( $_GET['id'] ) ) : ?>
				<?php $this->render_company_detail( absint( $_GET['id'] ) ); ?>
			<?php else : ?>
				<h1>
					<?php esc_html_e( 'Companies', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-companies&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_companies_list(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render companies list.
	 *
	 * @since 1.0.0
	 */
	private function render_companies_list() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_companies';
		
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		
		$where = array( '1=1' );
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = $wpdb->prepare( '(name LIKE %s OR email LIKE %s)', $like, $like );
		}
		
		$companies = $wpdb->get_results( "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY name ASC LIMIT 50" );
		
		?>
		<!-- Search -->
		<form method="get" style="margin-bottom: 15px;">
			<input type="hidden" name="page" value="scrm-companies">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search companies...', 'syncpoint-crm' ); ?>">
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'syncpoint-crm' ); ?></button>
		</form>
		
		<?php if ( empty( $companies ) ) : ?>
			<div class="scrm-empty-state">
				<span class="dashicons dashicons-building"></span>
				<h2><?php esc_html_e( 'No companies yet', 'syncpoint-crm' ); ?></h2>
				<p><?php esc_html_e( 'Add your first company to get started.', 'syncpoint-crm' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-companies&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Add Company', 'syncpoint-crm' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Name', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Website', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Contacts', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'syncpoint-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $companies as $company ) : 
						$contact_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}scrm_contacts WHERE company_id = %d", $company->id ) );
					?>
						<tr>
							<td><code><?php echo esc_html( $company->company_id ); ?></code></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-companies&action=view&id=' . $company->id ) ); ?>">
									<strong><?php echo esc_html( $company->name ); ?></strong>
								</a>
							</td>
							<td>
								<?php if ( $company->email ) : ?>
									<a href="mailto:<?php echo esc_attr( $company->email ); ?>"><?php echo esc_html( $company->email ); ?></a>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $company->phone ?: '—' ); ?></td>
							<td>
								<?php if ( $company->website ) : ?>
									<a href="<?php echo esc_url( $company->website ); ?>" target="_blank"><?php echo esc_html( wp_parse_url( $company->website, PHP_URL_HOST ) ); ?></a>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><span class="scrm-badge"><?php echo esc_html( $contact_count ); ?></span></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-companies&action=view&id=' . $company->id ) ); ?>"><?php esc_html_e( 'View', 'syncpoint-crm' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif;
	}

	/**
	 * Render company form.
	 *
	 * @since 1.0.0
	 */
	private function render_company_form() {
		// Handle form submission.
		if ( isset( $_POST['scrm_save_company'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'scrm_save_company' ) ) {
			$data = array(
				'name'    => sanitize_text_field( $_POST['name'] ?? '' ),
				'email'   => sanitize_email( $_POST['email'] ?? '' ),
				'phone'   => sanitize_text_field( $_POST['phone'] ?? '' ),
				'website' => esc_url_raw( $_POST['website'] ?? '' ),
				'address' => sanitize_textarea_field( $_POST['address'] ?? '' ),
				'notes'   => sanitize_textarea_field( $_POST['notes'] ?? '' ),
			);
			
			$result = scrm_create_company( $data );
			
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Company created successfully.', 'syncpoint-crm' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=scrm-companies&action=view&id=' . $result ) ) . '">' . esc_html__( 'View Company', 'syncpoint-crm' ) . '</a></p></div>';
			}
		}
		
		?>
		<form method="post" class="scrm-form" style="max-width: 600px;">
			<?php wp_nonce_field( 'scrm_save_company' ); ?>
			
			<table class="form-table">
				<tr>
					<th><label for="name"><?php esc_html_e( 'Company Name', 'syncpoint-crm' ); ?> <span class="required">*</span></label></th>
					<td><input type="text" id="name" name="name" required class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?></label></th>
					<td><input type="email" id="email" name="email" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="phone"><?php esc_html_e( 'Phone', 'syncpoint-crm' ); ?></label></th>
					<td><input type="tel" id="phone" name="phone" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="website"><?php esc_html_e( 'Website', 'syncpoint-crm' ); ?></label></th>
					<td><input type="url" id="website" name="website" class="regular-text" placeholder="https://"></td>
				</tr>
				<tr>
					<th><label for="address"><?php esc_html_e( 'Address', 'syncpoint-crm' ); ?></label></th>
					<td><textarea id="address" name="address" rows="3" class="large-text"></textarea></td>
				</tr>
				<tr>
					<th><label for="notes"><?php esc_html_e( 'Notes', 'syncpoint-crm' ); ?></label></th>
					<td><textarea id="notes" name="notes" rows="3" class="large-text"></textarea></td>
				</tr>
			</table>
			
			<p class="submit">
				<button type="submit" name="scrm_save_company" class="button button-primary"><?php esc_html_e( 'Create Company', 'syncpoint-crm' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Render company detail.
	 *
	 * @since 1.0.0
	 * @param int $id Company ID.
	 */
	private function render_company_detail( $id ) {
		$company = scrm_get_company( $id );
		
		if ( ! $company ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Company not found.', 'syncpoint-crm' ) . '</p></div>';
			return;
		}
		
		// Get associated contacts.
		global $wpdb;
		$contacts = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}scrm_contacts WHERE company_id = %d ORDER BY first_name ASC",
			$id
		) );
		
		// Get transactions total.
		$revenue = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(t.amount) FROM {$wpdb->prefix}scrm_transactions t 
			 JOIN {$wpdb->prefix}scrm_contacts c ON t.contact_id = c.id 
			 WHERE c.company_id = %d AND t.type = 'payment' AND t.status = 'completed'",
			$id
		) );
		
		?>
		<h1>
			<?php echo esc_html( $company->name ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-companies' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
		</h1>
		
		<div style="display: flex; gap: 30px; margin-top: 20px;">
			<!-- Company Details -->
			<div style="flex: 1; max-width: 400px;">
				<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'Company Details', 'syncpoint-crm' ); ?></h3>
					
					<table class="form-table" style="margin: 0;">
						<tr>
							<th><?php esc_html_e( 'ID', 'syncpoint-crm' ); ?></th>
							<td><code><?php echo esc_html( $company->company_id ); ?></code></td>
						</tr>
						<?php if ( $company->email ) : ?>
						<tr>
							<th><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?></th>
							<td><a href="mailto:<?php echo esc_attr( $company->email ); ?>"><?php echo esc_html( $company->email ); ?></a></td>
						</tr>
						<?php endif; ?>
						<?php if ( $company->phone ) : ?>
						<tr>
							<th><?php esc_html_e( 'Phone', 'syncpoint-crm' ); ?></th>
							<td><?php echo esc_html( $company->phone ); ?></td>
						</tr>
						<?php endif; ?>
						<?php if ( $company->website ) : ?>
						<tr>
							<th><?php esc_html_e( 'Website', 'syncpoint-crm' ); ?></th>
							<td><a href="<?php echo esc_url( $company->website ); ?>" target="_blank"><?php echo esc_html( $company->website ); ?></a></td>
						</tr>
						<?php endif; ?>
						<?php if ( $company->address ) : ?>
						<tr>
							<th><?php esc_html_e( 'Address', 'syncpoint-crm' ); ?></th>
							<td><?php echo nl2br( esc_html( $company->address ) ); ?></td>
						</tr>
						<?php endif; ?>
					</table>
					
					<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
						<strong><?php esc_html_e( 'Total Revenue:', 'syncpoint-crm' ); ?></strong>
						<span style="float: right; font-size: 18px; color: #059669;"><?php echo esc_html( scrm_format_currency( $revenue ?: 0 ) ); ?></span>
					</div>
				</div>
			</div>
			
			<!-- Contacts -->
			<div style="flex: 2;">
				<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'Contacts', 'syncpoint-crm' ); ?> (<?php echo count( $contacts ); ?>)</h3>
					
					<?php if ( empty( $contacts ) ) : ?>
						<p><?php esc_html_e( 'No contacts associated with this company.', 'syncpoint-crm' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'syncpoint-crm' ); ?></th>
									<th><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?></th>
									<th><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $contacts as $contact ) : ?>
									<tr>
										<td>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=view&id=' . $contact->id ) ); ?>">
												<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ?: '(No name)' ); ?>
											</a>
										</td>
										<td><?php echo esc_html( $contact->email ); ?></td>
										<td><span class="scrm-badge"><?php echo esc_html( ucfirst( $contact->type ) ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render transactions page.
	 *
	 * @since 1.0.0
	 */
	public function render_transactions() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		
		// Handle sync action.
		if ( 'sync' === $action && isset( $_GET['gateway'] ) ) {
			$this->handle_transaction_sync();
		}
		
		?>
		<div class="wrap scrm-wrap">
			<?php if ( 'add' === $action ) : ?>
				<h1>
					<?php esc_html_e( 'Add Transaction', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-transactions' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_transaction_form(); ?>
			<?php elseif ( 'view' === $action && isset( $_GET['id'] ) ) : ?>
				<?php $this->render_transaction_detail( absint( $_GET['id'] ) ); ?>
			<?php else : ?>
				<h1>
					<?php esc_html_e( 'Transactions', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-transactions&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'syncpoint-crm' ); ?></a>
				</h1>
				
				<!-- Sync Buttons -->
				<div class="scrm-sync-buttons" style="margin: 15px 0;">
					<?php
					$paypal_settings = scrm_get_settings( 'paypal' );
					$stripe_settings = scrm_get_settings( 'stripe' );
					
					if ( ! empty( $paypal_settings['enabled'] ) ) :
					?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=scrm-transactions&action=sync&gateway=paypal' ), 'scrm_sync_paypal' ) ); ?>" class="button">
							<span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
							<?php esc_html_e( 'Sync PayPal', 'syncpoint-crm' ); ?>
						</a>
					<?php endif; ?>
					
					<?php if ( ! empty( $stripe_settings['enabled'] ) ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=scrm-transactions&action=sync&gateway=stripe' ), 'scrm_sync_stripe' ) ); ?>" class="button">
							<span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
							<?php esc_html_e( 'Sync Stripe', 'syncpoint-crm' ); ?>
						</a>
					<?php endif; ?>
				</div>
				
				<?php $this->render_transactions_list(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle transaction sync.
	 *
	 * @since 1.0.0
	 */
	private function handle_transaction_sync() {
		$gateway = sanitize_text_field( wp_unslash( $_GET['gateway'] ) );
		
		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'scrm_sync_' . $gateway ) ) {
			wp_die( esc_html__( 'Security check failed.', 'syncpoint-crm' ) );
		}
		
		$gateway_instance = null;
		
		if ( 'paypal' === $gateway ) {
			$gateway_instance = new SCRM\Gateways\PayPal();
		} elseif ( 'stripe' === $gateway ) {
			$gateway_instance = new SCRM\Gateways\Stripe();
		}
		
		if ( $gateway_instance ) {
			$result = $gateway_instance->sync_transactions();
			
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'scrm_messages', 'sync_error', $result->get_error_message(), 'error' );
			} else {
				add_settings_error(
					'scrm_messages',
					'sync_success',
					sprintf(
						/* translators: %1$d: synced count, %2$d: skipped count */
						__( 'Sync complete: %1$d transactions synced, %2$d skipped.', 'syncpoint-crm' ),
						$result['synced'],
						$result['skipped']
					),
					'success'
				);
			}
		}
		
		settings_errors( 'scrm_messages' );
	}

	/**
	 * Render transactions list.
	 *
	 * @since 1.0.0
	 */
	private function render_transactions_list() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';
		
		// Filters.
		$where = array( '1=1' );
		$filter_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
		$filter_gateway = isset( $_GET['gateway'] ) ? sanitize_text_field( wp_unslash( $_GET['gateway'] ) ) : '';
		$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		
		if ( $filter_type ) {
			$where[] = $wpdb->prepare( 'type = %s', $filter_type );
		}
		if ( $filter_gateway ) {
			$where[] = $wpdb->prepare( 'gateway = %s', $filter_gateway );
		}
		if ( $filter_status ) {
			$where[] = $wpdb->prepare( 'status = %s', $filter_status );
		}
		
		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY created_at DESC LIMIT 50";
		$transactions = $wpdb->get_results( $sql );
		
		?>
		<!-- Filters -->
		<form method="get" class="scrm-filters" style="margin-bottom: 15px;">
			<input type="hidden" name="page" value="scrm-transactions">
			
			<select name="type">
				<option value=""><?php esc_html_e( 'All Types', 'syncpoint-crm' ); ?></option>
				<?php foreach ( scrm_get_transaction_types() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			
			<select name="gateway">
				<option value=""><?php esc_html_e( 'All Gateways', 'syncpoint-crm' ); ?></option>
				<option value="paypal" <?php selected( $filter_gateway, 'paypal' ); ?>>PayPal</option>
				<option value="stripe" <?php selected( $filter_gateway, 'stripe' ); ?>>Stripe</option>
				<option value="manual" <?php selected( $filter_gateway, 'manual' ); ?>><?php esc_html_e( 'Manual', 'syncpoint-crm' ); ?></option>
				<option value="import" <?php selected( $filter_gateway, 'import' ); ?>><?php esc_html_e( 'Import', 'syncpoint-crm' ); ?></option>
			</select>
			
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'syncpoint-crm' ); ?></option>
				<option value="completed" <?php selected( $filter_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'syncpoint-crm' ); ?></option>
				<option value="pending" <?php selected( $filter_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'syncpoint-crm' ); ?></option>
				<option value="failed" <?php selected( $filter_status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'syncpoint-crm' ); ?></option>
			</select>
			
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'syncpoint-crm' ); ?></button>
		</form>
		
		<?php if ( empty( $transactions ) ) : ?>
			<div class="scrm-empty-state">
				<span class="dashicons dashicons-chart-area"></span>
				<h2><?php esc_html_e( 'No transactions yet', 'syncpoint-crm' ); ?></h2>
				<p><?php esc_html_e( 'Transactions will appear here when synced from payment gateways or added manually.', 'syncpoint-crm' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-transactions&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Add Manual Transaction', 'syncpoint-crm' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped scrm-table">
				<thead>
					<tr>
						<th style="width: 120px;"><?php esc_html_e( 'ID', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Gateway', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Date', 'syncpoint-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $transactions as $txn ) : 
						$contact = scrm_get_contact( $txn->contact_id );
					?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-transactions&action=view&id=' . $txn->id ) ); ?>">
									<?php echo esc_html( $txn->transaction_id ); ?>
								</a>
							</td>
							<td>
								<?php if ( $contact ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=edit&id=' . $contact->id ) ); ?>">
										<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ?: $contact->email ); ?>
									</a>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td>
								<span class="scrm-badge scrm-badge--<?php echo esc_attr( $txn->type ); ?>">
									<?php echo esc_html( ucfirst( $txn->type ) ); ?>
								</span>
							</td>
							<td>
								<strong style="<?php echo 'refund' === $txn->type ? 'color: #dc2626;' : 'color: #059669;'; ?>">
									<?php echo 'refund' === $txn->type ? '-' : ''; ?>
									<?php echo esc_html( scrm_format_currency( $txn->amount, $txn->currency ) ); ?>
								</strong>
							</td>
							<td>
								<?php echo esc_html( ucfirst( $txn->gateway ) ); ?>
							</td>
							<td>
								<span class="scrm-status scrm-status--<?php echo esc_attr( $txn->status ); ?>">
									<?php echo esc_html( ucfirst( $txn->status ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( scrm_format_date( $txn->created_at ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif;
	}

	/**
	 * Render transaction form.
	 *
	 * @since 1.0.0
	 */
	private function render_transaction_form() {
		// Handle form submission.
		if ( isset( $_POST['scrm_save_transaction'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'scrm_save_transaction' ) ) {
			$data = array(
				'contact_id'  => absint( $_POST['contact_id'] ?? 0 ),
				'type'        => sanitize_text_field( $_POST['type'] ?? 'payment' ),
				'amount'      => floatval( $_POST['amount'] ?? 0 ),
				'currency'    => sanitize_text_field( $_POST['currency'] ?? scrm_get_default_currency() ),
				'gateway'     => 'manual',
				'status'      => sanitize_text_field( $_POST['status'] ?? 'completed' ),
				'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
			);
			
			$result = scrm_create_transaction( $data );
			
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Transaction created successfully.', 'syncpoint-crm' ) . '</p></div>';
			}
		}
		
		$currencies = scrm_get_currencies();
		$contacts = scrm_get_contacts( array( 'status' => 'active', 'limit' => 100 ) );
		
		?>
		<form method="post" class="scrm-form">
			<?php wp_nonce_field( 'scrm_save_transaction' ); ?>
			
			<table class="form-table">
				<tr>
					<th><label for="contact_id"><?php esc_html_e( 'Contact', 'syncpoint-crm' ); ?> <span class="required">*</span></label></th>
					<td>
						<select id="contact_id" name="contact_id" required style="min-width: 300px;">
							<option value=""><?php esc_html_e( 'Select Contact', 'syncpoint-crm' ); ?></option>
							<?php foreach ( $contacts as $contact ) : ?>
								<option value="<?php echo esc_attr( $contact->id ); ?>">
									<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) . ' (' . $contact->email . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="type"><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></label></th>
					<td>
						<select id="type" name="type">
							<?php foreach ( scrm_get_transaction_types() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="amount"><?php esc_html_e( 'Amount', 'syncpoint-crm' ); ?> <span class="required">*</span></label></th>
					<td>
						<input type="number" id="amount" name="amount" step="0.01" min="0" required style="width: 150px;">
						<select id="currency" name="currency" style="width: 100px;">
							<?php foreach ( $currencies as $code => $data ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( scrm_get_default_currency(), $code ); ?>>
									<?php echo esc_html( $code ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="status"><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></label></th>
					<td>
						<select id="status" name="status">
							<option value="completed"><?php esc_html_e( 'Completed', 'syncpoint-crm' ); ?></option>
							<option value="pending"><?php esc_html_e( 'Pending', 'syncpoint-crm' ); ?></option>
							<option value="failed"><?php esc_html_e( 'Failed', 'syncpoint-crm' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="description"><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></label></th>
					<td>
						<textarea id="description" name="description" rows="3" class="large-text"></textarea>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<button type="submit" name="scrm_save_transaction" class="button button-primary"><?php esc_html_e( 'Save Transaction', 'syncpoint-crm' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Render transaction detail.
	 *
	 * @since 1.0.0
	 * @param int $id Transaction ID.
	 */
	private function render_transaction_detail( $id ) {
		global $wpdb;
		$txn = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}scrm_transactions WHERE id = %d",
			$id
		) );
		
		if ( ! $txn ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Transaction not found.', 'syncpoint-crm' ) . '</p></div>';
			return;
		}
		
		$contact = scrm_get_contact( $txn->contact_id );
		$metadata = ! empty( $txn->metadata ) ? json_decode( $txn->metadata, true ) : array();
		
		?>
		<h1>
			<?php echo esc_html( $txn->transaction_id ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-transactions' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
		</h1>
		
		<div class="scrm-detail-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; max-width: 600px;">
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Contact', 'syncpoint-crm' ); ?></th>
					<td>
						<?php if ( $contact ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=edit&id=' . $contact->id ) ); ?>">
								<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ?: $contact->email ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Type', 'syncpoint-crm' ); ?></th>
					<td><span class="scrm-badge scrm-badge--<?php echo esc_attr( $txn->type ); ?>"><?php echo esc_html( ucfirst( $txn->type ) ); ?></span></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Amount', 'syncpoint-crm' ); ?></th>
					<td><strong style="font-size: 18px; <?php echo 'refund' === $txn->type ? 'color: #dc2626;' : 'color: #059669;'; ?>"><?php echo esc_html( scrm_format_currency( $txn->amount, $txn->currency ) ); ?></strong></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Gateway', 'syncpoint-crm' ); ?></th>
					<td><?php echo esc_html( ucfirst( $txn->gateway ) ); ?></td>
				</tr>
				<?php if ( ! empty( $txn->gateway_transaction_id ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Gateway Transaction ID', 'syncpoint-crm' ); ?></th>
					<td><code><?php echo esc_html( $txn->gateway_transaction_id ); ?></code></td>
				</tr>
				<?php endif; ?>
				<tr>
					<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
					<td><span class="scrm-status scrm-status--<?php echo esc_attr( $txn->status ); ?>"><?php echo esc_html( ucfirst( $txn->status ) ); ?></span></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Date', 'syncpoint-crm' ); ?></th>
					<td><?php echo esc_html( $txn->created_at ); ?></td>
				</tr>
				<?php if ( ! empty( $txn->description ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></th>
					<td><?php echo esc_html( $txn->description ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
			
			<?php if ( ! empty( $metadata ) ) : ?>
				<h3><?php esc_html_e( 'Metadata', 'syncpoint-crm' ); ?></h3>
				<pre style="background: #f6f7f7; padding: 10px; overflow: auto; max-height: 200px;"><?php echo esc_html( wp_json_encode( $metadata, JSON_PRETTY_PRINT ) ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render invoices page.
	 *
	 * @since 1.0.0
	 */
	public function render_invoices() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		
		?>
		<div class="wrap scrm-wrap">
			<?php if ( 'add' === $action ) : ?>
				<h1>
					<?php esc_html_e( 'Create Invoice', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-invoices' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_invoice_form(); ?>
			<?php elseif ( 'view' === $action && isset( $_GET['id'] ) ) : ?>
				<?php $this->render_invoice_detail( absint( $_GET['id'] ) ); ?>
			<?php else : ?>
				<h1>
					<?php esc_html_e( 'Invoices', 'syncpoint-crm' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-invoices&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Create Invoice', 'syncpoint-crm' ); ?></a>
				</h1>
				<?php $this->render_invoices_list(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render invoices list.
	 *
	 * @since 1.0.0
	 */
	private function render_invoices_list() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';
		
		$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		
		$where = array( '1=1' );
		if ( $filter_status ) {
			$where[] = $wpdb->prepare( 'status = %s', $filter_status );
		}
		
		$invoices = $wpdb->get_results( "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY created_at DESC LIMIT 50" );
		
		?>
		<!-- Filters -->
		<form method="get" style="margin-bottom: 15px;">
			<input type="hidden" name="page" value="scrm-invoices">
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'syncpoint-crm' ); ?></option>
				<?php foreach ( scrm_get_invoice_statuses() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_status, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'syncpoint-crm' ); ?></button>
		</form>
		
		<?php if ( empty( $invoices ) ) : ?>
			<div class="scrm-empty-state">
				<span class="dashicons dashicons-media-text"></span>
				<h2><?php esc_html_e( 'No invoices yet', 'syncpoint-crm' ); ?></h2>
				<p><?php esc_html_e( 'Create your first invoice to get started.', 'syncpoint-crm' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-invoices&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create Invoice', 'syncpoint-crm' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Invoice #', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Issue Date', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Due Date', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'syncpoint-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $invoices as $inv ) : 
						$contact = scrm_get_contact( $inv->contact_id );
						$is_overdue = 'paid' !== $inv->status && strtotime( $inv->due_date ) < time();
					?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-invoices&action=view&id=' . $inv->id ) ); ?>">
									<strong><?php echo esc_html( $inv->invoice_number ); ?></strong>
								</a>
							</td>
							<td>
								<?php if ( $contact ) : ?>
									<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ?: $contact->email ); ?>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><strong><?php echo esc_html( scrm_format_currency( $inv->total, $inv->currency ) ); ?></strong></td>
							<td>
								<span class="scrm-badge scrm-badge--<?php echo esc_attr( $inv->status ); ?> <?php echo $is_overdue ? 'scrm-badge--overdue' : ''; ?>">
									<?php 
									if ( $is_overdue && 'paid' !== $inv->status ) {
										esc_html_e( 'Overdue', 'syncpoint-crm' );
									} else {
										echo esc_html( ucfirst( $inv->status ) ); 
									}
									?>
								</span>
							</td>
							<td><?php echo esc_html( scrm_format_date( $inv->issue_date ) ); ?></td>
							<td><?php echo esc_html( scrm_format_date( $inv->due_date ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-invoices&action=view&id=' . $inv->id ) ); ?>"><?php esc_html_e( 'View', 'syncpoint-crm' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif;
	}

	/**
	 * Render invoice form.
	 *
	 * @since 1.0.0
	 */
	private function render_invoice_form() {
		// Handle form submission.
		if ( isset( $_POST['scrm_save_invoice'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'scrm_save_invoice' ) ) {
			$invoice = new SCRM\Core\Invoice();
			$invoice->contact_id = absint( $_POST['contact_id'] ?? 0 );
			$invoice->currency = sanitize_text_field( $_POST['currency'] ?? scrm_get_default_currency() );
			$invoice->issue_date = sanitize_text_field( $_POST['issue_date'] ?? date( 'Y-m-d' ) );
			$invoice->due_date = sanitize_text_field( $_POST['due_date'] ?? date( 'Y-m-d', strtotime( '+30 days' ) ) );
			$invoice->notes = sanitize_textarea_field( $_POST['notes'] ?? '' );
			$invoice->terms = sanitize_textarea_field( $_POST['terms'] ?? '' );
			
			$result = $invoice->save();
			
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				// Add line items.
				if ( ! empty( $_POST['items'] ) ) {
					foreach ( $_POST['items'] as $item ) {
						if ( empty( $item['description'] ) ) continue;
						$invoice->add_item( array(
							'description' => sanitize_text_field( $item['description'] ),
							'quantity'    => floatval( $item['quantity'] ?? 1 ),
							'unit_price'  => floatval( $item['unit_price'] ?? 0 ),
						) );
					}
					$invoice->recalculate_totals();
				}
				
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Invoice created successfully.', 'syncpoint-crm' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=scrm-invoices&action=view&id=' . $invoice->id ) ) . '">' . esc_html__( 'View Invoice', 'syncpoint-crm' ) . '</a></p></div>';
			}
		}
		
		$currencies = scrm_get_currencies();
		
		?>
		<form method="post" class="scrm-form">
			<?php wp_nonce_field( 'scrm_save_invoice' ); ?>
			
			<table class="form-table">
				<tr>
					<th><label for="contact_search"><?php esc_html_e( 'Bill To', 'syncpoint-crm' ); ?> <span class="required">*</span></label></th>
					<td>
						<div class="scrm-contact-search" style="position: relative;">
							<input type="hidden" id="contact_id" name="contact_id" value="" required>
							<input type="text" 
								id="contact_search" 
								class="regular-text" 
								placeholder="<?php esc_attr_e( 'Search contacts by name or email...', 'syncpoint-crm' ); ?>"
								autocomplete="off"
								style="min-width: 300px;">
							<div id="contact_results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
							<p class="description"><?php esc_html_e( 'Type to search contacts', 'syncpoint-crm' ); ?></p>
							<p id="selected_contact" style="margin-top: 5px; display: none;">
								<span class="dashicons dashicons-yes-alt" style="color: #059669;"></span>
								<strong id="selected_contact_name"></strong>
								<a href="#" id="clear_contact" style="margin-left: 10px; color: #dc2626;"><?php esc_html_e( 'Clear', 'syncpoint-crm' ); ?></a>
							</p>
						</div>
						
						<script>
						jQuery(document).ready(function($) {
							var searchTimeout;
							
							$('#contact_search').on('input', function() {
								var query = $(this).val();
								
								clearTimeout(searchTimeout);
								
								if (query.length < 2) {
									$('#contact_results').hide();
									return;
								}
								
								searchTimeout = setTimeout(function() {
									$.post(scrm.ajax_url, {
										action: 'scrm_search_contacts',
										nonce: scrm.nonce,
										query: query
									}, function(response) {
										if (response.success && response.data.contacts.length > 0) {
											var html = '';
											var existingIds = [];
											$('input[name="contact_ids[]"]').each(function() {
												existingIds.push(parseInt($(this).val()));
											});

											$.each(response.data.contacts, function(i, contact) {
												if (existingIds.indexOf(contact.id) === -1) {
													var name = contact.name || ((contact.first_name || '') + ' ' + (contact.last_name || '')).trim() || contact.email;
													html += '<div class="scrm-suggestion-item" data-id="' + contact.id + '" data-name="' + name + '" data-email="' + contact.email + '">';
													html += name + ' <small>(' + contact.email + ')</small>';
													html += '</div>';
												}
											});
											$('#contact_results').html(html).show();
										} else {
											$('#contact_results').html('<div style="padding: 10px; color: #666;"><?php esc_html_e( 'No contacts found', 'syncpoint-crm' ); ?></div>').show();
										}
									});
								}, 300);
							});
							
							$(document).on('click', '.scrm-contact-option', function() {
								var id = $(this).data('id');
								var name = $(this).data('name');
								
								$('#contact_id').val(id);
								$('#contact_search').val('').hide();
								$('#selected_contact_name').text(name);
								$('#selected_contact').show();
								$('#contact_results').hide();
							});
							
							$('#clear_contact').on('click', function(e) {
								e.preventDefault();
								$('#contact_id').val('');
								$('#contact_search').show().val('');
								$('#selected_contact').hide();
							});
							
							$(document).on('mouseenter', '.scrm-contact-option', function() {
								$(this).css('background', '#f0f0f0');
							}).on('mouseleave', '.scrm-contact-option', function() {
								$(this).css('background', '#fff');
							});
							
							$(document).on('click', function(e) {
								if (!$(e.target).closest('.scrm-contact-search').length) {
									$('#contact_results').hide();
								}
							});
						});
						</script>
					</td>
				</tr>
				<tr>
					<th><label for="currency"><?php esc_html_e( 'Currency', 'syncpoint-crm' ); ?></label></th>
					<td>
						<select id="currency" name="currency">
							<?php foreach ( $currencies as $code => $data ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( scrm_get_default_currency(), $code ); ?>><?php echo esc_html( $code ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="issue_date"><?php esc_html_e( 'Issue Date', 'syncpoint-crm' ); ?></label></th>
					<td><input type="date" id="issue_date" name="issue_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"></td>
				</tr>
				<tr>
					<th><label for="due_date"><?php esc_html_e( 'Due Date', 'syncpoint-crm' ); ?></label></th>
					<td><input type="date" id="due_date" name="due_date" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+30 days' ) ) ); ?>"></td>
				</tr>
			</table>
			
			<h3><?php esc_html_e( 'Line Items', 'syncpoint-crm' ); ?></h3>
			<table class="wp-list-table widefat" id="invoice-items">
				<thead>
					<tr>
						<th style="width: 50%;"><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Qty', 'syncpoint-crm' ); ?></th>
						<th><?php esc_html_e( 'Unit Price', 'syncpoint-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php for ( $i = 0; $i < 3; $i++ ) : ?>
					<tr>
						<td><input type="text" name="items[<?php echo $i; ?>][description]" class="large-text"></td>
						<td><input type="number" name="items[<?php echo $i; ?>][quantity]" value="1" min="1" style="width: 80px;"></td>
						<td><input type="number" name="items[<?php echo $i; ?>][unit_price]" step="0.01" min="0" style="width: 120px;"></td>
					</tr>
					<?php endfor; ?>
				</tbody>
			</table>
			
			<table class="form-table">
				<tr>
					<th><label for="notes"><?php esc_html_e( 'Notes', 'syncpoint-crm' ); ?></label></th>
					<td><textarea id="notes" name="notes" rows="3" class="large-text"></textarea></td>
				</tr>
			</table>
			
			<p class="submit">
				<button type="submit" name="scrm_save_invoice" class="button button-primary"><?php esc_html_e( 'Create Invoice', 'syncpoint-crm' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Render invoice detail.
	 *
	 * @since 1.0.0
	 * @param int $id Invoice ID.
	 */
	private function render_invoice_detail( $id ) {
		$invoice = new SCRM\Core\Invoice( $id );
		
		if ( ! $invoice->exists() ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Invoice not found.', 'syncpoint-crm' ) . '</p></div>';
			return;
		}
		
		$contact = scrm_get_contact( $invoice->contact_id );
		$items = $invoice->get_items();
		
		// Handle actions.
		if ( isset( $_GET['do'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'scrm_invoice_action' ) ) {
			$do = sanitize_text_field( wp_unslash( $_GET['do'] ) );
			
			if ( 'send' === $do ) {
				$invoice->mark_sent();
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Invoice marked as sent.', 'syncpoint-crm' ) . '</p></div>';
			} elseif ( 'paid' === $do ) {
				$invoice->mark_paid();
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Invoice marked as paid.', 'syncpoint-crm' ) . '</p></div>';
			} elseif ( 'email' === $do ) {
				$result = SCRM\Utils\Emails::send_invoice( $invoice );
				if ( $result ) {
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Invoice emailed successfully.', 'syncpoint-crm' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to send email.', 'syncpoint-crm' ) . '</p></div>';
				}
			}
		}
		
		?>
		<h1>
			<?php echo esc_html( $invoice->invoice_number ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-invoices' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to List', 'syncpoint-crm' ); ?></a>
		</h1>
		
		<!-- Action Buttons -->
		<div style="margin: 15px 0;">
			<?php if ( 'draft' === $invoice->status ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=scrm-invoices&action=view&id=' . $id . '&do=send' ), 'scrm_invoice_action' ) ); ?>" class="button"><?php esc_html_e( 'Mark as Sent', 'syncpoint-crm' ); ?></a>
			<?php endif; ?>
			
			<?php if ( 'paid' !== $invoice->status ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=scrm-invoices&action=view&id=' . $id . '&do=paid' ), 'scrm_invoice_action' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Mark as Paid', 'syncpoint-crm' ); ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=scrm-invoices&action=view&id=' . $id . '&do=email' ), 'scrm_invoice_action' ) ); ?>" class="button"><?php esc_html_e( 'Send Email', 'syncpoint-crm' ); ?></a>
			<?php endif; ?>
			
			<a href="<?php echo esc_url( $invoice->get_public_url() ); ?>" target="_blank" class="button"><?php esc_html_e( 'View Public', 'syncpoint-crm' ); ?></a>
			<a href="<?php echo esc_url( $invoice->get_pdf_url() ); ?>" target="_blank" class="button"><?php esc_html_e( 'Download PDF', 'syncpoint-crm' ); ?></a>
		</div>
		
		<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; max-width: 800px;">
			<div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
				<div>
					<strong><?php esc_html_e( 'Bill To:', 'syncpoint-crm' ); ?></strong><br>
					<?php if ( $contact ) : ?>
						<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ); ?><br>
						<?php echo esc_html( $contact->email ); ?>
					<?php endif; ?>
				</div>
				<div style="text-align: right;">
					<span class="scrm-badge scrm-badge--<?php echo esc_attr( $invoice->status ); ?>" style="font-size: 14px;"><?php echo esc_html( ucfirst( $invoice->status ) ); ?></span>
					<br><br>
					<strong><?php esc_html_e( 'Issue:', 'syncpoint-crm' ); ?></strong> <?php echo esc_html( scrm_format_date( $invoice->issue_date ) ); ?><br>
					<strong><?php esc_html_e( 'Due:', 'syncpoint-crm' ); ?></strong> <?php echo esc_html( scrm_format_date( $invoice->due_date ) ); ?>
				</div>
			</div>
			
			<table class="wp-list-table widefat" style="margin: 20px 0;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></th>
						<th style="width: 80px; text-align: right;"><?php esc_html_e( 'Qty', 'syncpoint-crm' ); ?></th>
						<th style="width: 100px; text-align: right;"><?php esc_html_e( 'Price', 'syncpoint-crm' ); ?></th>
						<th style="width: 100px; text-align: right;"><?php esc_html_e( 'Amount', 'syncpoint-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $items ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No items.', 'syncpoint-crm' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $items as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item->description ); ?></td>
							<td style="text-align: right;"><?php echo esc_html( $item->quantity ); ?></td>
							<td style="text-align: right;"><?php echo esc_html( scrm_format_currency( $item->unit_price, $invoice->currency ) ); ?></td>
							<td style="text-align: right;"><?php echo esc_html( scrm_format_currency( $item->amount, $invoice->currency ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3" style="text-align: right;"><strong><?php esc_html_e( 'Subtotal', 'syncpoint-crm' ); ?></strong></td>
						<td style="text-align: right;"><?php echo esc_html( scrm_format_currency( $invoice->subtotal, $invoice->currency ) ); ?></td>
					</tr>
					<?php if ( $invoice->discount > 0 ) : ?>
					<tr>
						<td colspan="3" style="text-align: right;"><?php esc_html_e( 'Discount', 'syncpoint-crm' ); ?></td>
						<td style="text-align: right; color: #dc2626;">-<?php echo esc_html( scrm_format_currency( $invoice->discount, $invoice->currency ) ); ?></td>
					</tr>
					<?php endif; ?>
					<?php if ( $invoice->tax > 0 ) : ?>
					<tr>
						<td colspan="3" style="text-align: right;"><?php esc_html_e( 'Tax', 'syncpoint-crm' ); ?></td>
						<td style="text-align: right;"><?php echo esc_html( scrm_format_currency( $invoice->tax, $invoice->currency ) ); ?></td>
					</tr>
					<?php endif; ?>
					<tr>
						<td colspan="3" style="text-align: right;"><strong style="font-size: 16px;"><?php esc_html_e( 'Total', 'syncpoint-crm' ); ?></strong></td>
						<td style="text-align: right;"><strong style="font-size: 18px; color: #059669;"><?php echo esc_html( scrm_format_currency( $invoice->total, $invoice->currency ) ); ?></strong></td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}

	/**
	 * Render tags page.
	 *
	 * @since 1.0.0
	 */
	public function render_tags() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';
		
		// Handle form submission.
		if ( isset( $_POST['scrm_save_tag'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'scrm_save_tag' ) ) {
			$data = array(
				'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
				'color'       => scrm_sanitize_hex_color( $_POST['color'] ?? '#6B7280' ),
				'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
			);
			
			$result = scrm_create_tag( $data );
			
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Tag created successfully.', 'syncpoint-crm' ) . '</p></div>';
			}
		}
		
		// Handle delete.
		if ( isset( $_GET['delete'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'scrm_delete_tag' ) ) {
			$delete_id = absint( $_GET['delete'] );
			scrm_delete_tag( $delete_id );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Tag deleted.', 'syncpoint-crm' ) . '</p></div>';
		}
		
		$tags = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );
		
		?>
		<div class="wrap scrm-wrap">
			<h1><?php esc_html_e( 'Tags', 'syncpoint-crm' ); ?></h1>
			
			<div style="display: flex; gap: 30px; margin-top: 20px;">
				<!-- Add Tag Form -->
				<div style="width: 300px; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
					<h3><?php esc_html_e( 'Add New Tag', 'syncpoint-crm' ); ?></h3>
					<form method="post">
						<?php wp_nonce_field( 'scrm_save_tag' ); ?>
						<p>
							<label for="name"><?php esc_html_e( 'Name', 'syncpoint-crm' ); ?></label><br>
							<input type="text" id="name" name="name" required style="width: 100%;">
						</p>
						<p>
							<label for="color"><?php esc_html_e( 'Color', 'syncpoint-crm' ); ?></label><br>
							<input type="color" id="color" name="color" value="#6B7280" style="width: 100%; height: 40px;">
						</p>
						<p>
							<label for="description"><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></label><br>
							<textarea id="description" name="description" rows="2" style="width: 100%;"></textarea>
						</p>
						<p>
							<button type="submit" name="scrm_save_tag" class="button button-primary"><?php esc_html_e( 'Add Tag', 'syncpoint-crm' ); ?></button>
						</p>
					</form>
				</div>
				
				<!-- Tags List -->
				<div style="flex: 1;">
					<?php if ( empty( $tags ) ) : ?>
						<p><?php esc_html_e( 'No tags created yet.', 'syncpoint-crm' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'syncpoint-crm' ); ?></th>
									<th><?php esc_html_e( 'Color', 'syncpoint-crm' ); ?></th>
									<th><?php esc_html_e( 'Description', 'syncpoint-crm' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'syncpoint-crm' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $tags as $tag ) : ?>
								<tr>
									<td>
										<span class="scrm-tag" style="background-color: <?php echo esc_attr( $tag->color ); ?>;">
											<?php echo esc_html( $tag->name ); ?>
										</span>
									</td>
									<td><code><?php echo esc_html( $tag->color ); ?></code></td>
									<td><?php echo esc_html( $tag->description ); ?></td>
									<td>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=scrm-tags&delete=' . $tag->id ), 'scrm_delete_tag' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this tag?', 'syncpoint-crm' ); ?>');" style="color: #dc2626;">
											<?php esc_html_e( 'Delete', 'syncpoint-crm' ); ?>
										</a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render email page.
	 *
	 * @since 1.0.0
	 */
	public function render_email() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		if ( 'compose' === $action ) {
			$this->render_email_compose();
			return;
		}

		$logs = scrm_get_all_email_logs( array( 'limit' => 50 ) );
		?>
		<div class="wrap scrm-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Email', 'syncpoint-crm' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-email&action=compose' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Compose New', 'syncpoint-crm' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( empty( $logs ) ) : ?>
				<div class="scrm-empty-state">
					<div class="scrm-empty-state__icon">
						<span class="dashicons dashicons-email-alt"></span>
					</div>
					<h2><?php esc_html_e( 'No emails sent yet', 'syncpoint-crm' ); ?></h2>
					<p><?php esc_html_e( 'Start by composing a new email to your contacts.', 'syncpoint-crm' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-email&action=compose' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Compose Email', 'syncpoint-crm' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'syncpoint-crm' ); ?></th>
							<th><?php esc_html_e( 'Recipient', 'syncpoint-crm' ); ?></th>
							<th><?php esc_html_e( 'Subject', 'syncpoint-crm' ); ?></th>
							<th><?php esc_html_e( 'Status', 'syncpoint-crm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( scrm_format_datetime( $log->created_at ) ); ?></td>
								<td>
									<?php if ( $log->contact_id ) : ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-contacts&action=edit&id=' . $log->contact_id ) ); ?>">
											<?php echo esc_html( trim( $log->first_name . ' ' . $log->last_name ) ?: $log->contact_email ); ?>
										</a>
									<?php else : ?>
										<?php esc_html_e( 'Unknown', 'syncpoint-crm' ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $log->subject ); ?></td>
								<td>
									<span class="scrm-status scrm-status--<?php echo 'sent' === $log->status ? 'success' : 'error'; ?>">
										<?php echo esc_html( ucfirst( $log->status ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render email compose page.
	 *
	 * @since 1.0.0
	 */
	private function render_email_compose() {
		$contact_ids = isset( $_GET['contacts'] ) ? array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['contacts'] ) ) ) ) : array();
		$contacts = array();
		foreach ( $contact_ids as $id ) {
			$contact = scrm_get_contact( $id );
			if ( $contact ) {
				$contacts[] = $contact;
			}
		}
		?>
		<div class="wrap scrm-wrap">
			<h1><?php esc_html_e( 'Compose Email', 'syncpoint-crm' ); ?></h1>

			<div class="scrm-email-composer" style="max-width: 800px; margin-top: 20px;">
				<form id="scrm-email-form">
					<?php wp_nonce_field( 'scrm_send_email', 'scrm_email_nonce' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="email_recipients"><?php esc_html_e( 'Recipients', 'syncpoint-crm' ); ?></label>
							</th>
							<td>
								<div id="scrm-email-recipients" class="scrm-recipients-box">
									<?php if ( ! empty( $contacts ) ) : ?>
										<?php foreach ( $contacts as $contact ) : ?>
											<span class="scrm-recipient-tag" data-id="<?php echo esc_attr( $contact->id ); ?>">
												<?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ?: $contact->email ); ?>
												<button type="button" class="scrm-remove-recipient">&times;</button>
												<input type="hidden" name="contact_ids[]" value="<?php echo esc_attr( $contact->id ); ?>">
											</span>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
								<div style="margin-top: 10px;">
									<input type="text" id="scrm-contact-search" placeholder="<?php esc_attr_e( 'Search and add contacts...', 'syncpoint-crm' ); ?>" class="regular-text" autocomplete="off">
									<div id="scrm-contact-suggestions" class="scrm-suggestions" style="display: none;"></div>
								</div>
								<p class="description"><?php esc_html_e( 'Search for contacts to add as recipients.', 'syncpoint-crm' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="email_subject"><?php esc_html_e( 'Subject', 'syncpoint-crm' ); ?></label>
							</th>
							<td>
								<input type="text" id="email_subject" name="subject" class="large-text" required>
								<p class="description"><?php esc_html_e( 'Use {first_name}, {last_name} for personalization.', 'syncpoint-crm' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="email_message"><?php esc_html_e( 'Message', 'syncpoint-crm' ); ?></label>
							</th>
							<td>
								<?php
								wp_editor( '', 'email_message', array(
									'textarea_name' => 'message',
									'textarea_rows' => 15,
									'media_buttons' => true,
									'teeny'         => false,
								) );
								?>
								<p class="description"><?php esc_html_e( 'Available merge tags: {first_name}, {last_name}, {email}, {company}', 'syncpoint-crm' ); ?></p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large" id="scrm-send-email">
							<?php esc_html_e( 'Send Email', 'syncpoint-crm' ); ?>
						</button>
						<span id="scrm-email-status" style="margin-left: 15px;"></span>
					</p>
				</form>
			</div>
		</div>

		<style>
			.scrm-recipients-box {
				background: #fff;
				border: 1px solid #ddd;
				padding: 8px;
				min-height: 40px;
				border-radius: 4px;
			}
			.scrm-recipient-tag {
				display: inline-flex;
				align-items: center;
				background: #0073aa;
				color: #fff;
				padding: 4px 8px;
				border-radius: 3px;
				margin: 2px;
				font-size: 13px;
			}
			.scrm-remove-recipient {
				background: none;
				border: none;
				color: #fff;
				margin-left: 6px;
				cursor: pointer;
				font-size: 16px;
				line-height: 1;
				padding: 0;
			}
			.scrm-suggestions {
				position: absolute;
				background: #fff;
				border: 1px solid #ddd;
				max-height: 200px;
				overflow-y: auto;
				z-index: 1000;
				width: 300px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}
			.scrm-suggestion-item {
				padding: 8px 12px;
				cursor: pointer;
			}
			.scrm-suggestion-item:hover {
				background: #f0f0f0;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var searchTimeout;

			$('#scrm-contact-search').on('input', function() {
				var query = $(this).val();
				clearTimeout(searchTimeout);

				if (query.length < 2) {
					$('#scrm-contact-suggestions').hide();
					return;
				}

				searchTimeout = setTimeout(function() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'scrm_search_contacts',
							nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_ajax_nonce' ) ); ?>',
							search: query
						},
						success: function(response) {
							if (response.success && response.data.contacts && response.data.contacts.length > 0) {
								var html = '';
								var existingIds = [];
								$('input[name="contact_ids[]"]').each(function() {
									existingIds.push(parseInt($(this).val()));
								});

								$.each(response.data.contacts, function(i, contact) {
									if (existingIds.indexOf(contact.id) === -1) {
										var name = contact.name || ((contact.first_name || '') + ' ' + (contact.last_name || '')).trim() || contact.email;
										html += '<div class="scrm-suggestion-item" data-id="' + contact.id + '" data-name="' + name + '" data-email="' + contact.email + '">';
										html += name + ' <small>(' + contact.email + ')</small>';
										html += '</div>';
									}
								});
								$('#scrm-contact-suggestions').html(html).show();
							} else {
								$('#scrm-contact-suggestions').hide();
							}
						}
					});
				}, 300);
			});

			$(document).on('click', '.scrm-suggestion-item', function() {
				var id = $(this).data('id');
				var name = $(this).data('name');

				var tag = '<span class="scrm-recipient-tag" data-id="' + id + '">';
				tag += name;
				tag += '<button type="button" class="scrm-remove-recipient">&times;</button>';
				tag += '<input type="hidden" name="contact_ids[]" value="' + id + '">';
				tag += '</span>';

				$('#scrm-email-recipients').append(tag);
				$('#scrm-contact-search').val('');
				$('#scrm-contact-suggestions').hide();
			});

			$(document).on('click', '.scrm-remove-recipient', function() {
				$(this).closest('.scrm-recipient-tag').remove();
			});

			$(document).on('click', function(e) {
				if (!$(e.target).closest('#scrm-contact-search, #scrm-contact-suggestions').length) {
					$('#scrm-contact-suggestions').hide();
				}
			});

			$('#scrm-email-form').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var $button = $('#scrm-send-email');
				var $status = $('#scrm-email-status');

				var contactIds = [];
				$('input[name="contact_ids[]"]').each(function() {
					contactIds.push($(this).val());
				});

				if (contactIds.length === 0) {
					alert('<?php echo esc_js( __( 'Please add at least one recipient.', 'syncpoint-crm' ) ); ?>');
					return;
				}

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'syncpoint-crm' ) ); ?>');
				$status.html('<span class="spinner is-active" style="float: none;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrm_send_email',
						nonce: '<?php echo esc_js( wp_create_nonce( 'scrm_send_email' ) ); ?>',
						contact_ids: contactIds,
						subject: $('#email_subject').val(),
						message: typeof tinyMCE !== 'undefined' && tinyMCE.get('email_message') ? tinyMCE.get('email_message').getContent() : $('#email_message').val()
					},
					success: function(response) {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Send Email', 'syncpoint-crm' ) ); ?>');
						if (response.success) {
							$status.html('<span style="color: green;">' + response.data.message + '</span>');
							setTimeout(function() {
								window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=scrm-email' ) ); ?>';
							}, 2000);
						} else {
							$status.html('<span style="color: red;">' + response.data.message + '</span>');
						}
					},
					error: function() {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Send Email', 'syncpoint-crm' ) ); ?>');
						$status.html('<span style="color: red;"><?php echo esc_js( __( 'An error occurred.', 'syncpoint-crm' ) ); ?></span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render import page.
	 *
	 * @since 1.0.0
	 */
	public function render_import() {
		?>
		<div class="wrap scrm-wrap">
			<h1><?php esc_html_e( 'Import Data', 'syncpoint-crm' ); ?></h1>
			
			<div class="scrm-import-wizard" style="max-width: 800px; background: #fff; padding: 30px; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
				<div id="scrm-import-step-1">
					<h2><?php esc_html_e( 'Step 1: Upload CSV File', 'syncpoint-crm' ); ?></h2>
					<p><?php esc_html_e( 'Upload a CSV file containing your data. The first row should contain column headers.', 'syncpoint-crm' ); ?></p>
					
					<form id="scrm-import-form" enctype="multipart/form-data">
						<table class="form-table">
							<tr>
								<th><label for="import_type"><?php esc_html_e( 'Import Type', 'syncpoint-crm' ); ?></label></th>
								<td>
									<select id="import_type" name="import_type">
										<option value="contacts"><?php esc_html_e( 'Contacts', 'syncpoint-crm' ); ?></option>
										<option value="companies"><?php esc_html_e( 'Companies', 'syncpoint-crm' ); ?></option>
										<option value="transactions"><?php esc_html_e( 'Transactions', 'syncpoint-crm' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="csv_file"><?php esc_html_e( 'CSV File', 'syncpoint-crm' ); ?></label></th>
								<td>
									<input type="file" id="csv_file" name="csv_file" accept=".csv" required>
									<p class="description"><?php esc_html_e( 'Maximum file size: 2MB. Supported format: CSV.', 'syncpoint-crm' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php esc_html_e( 'Options', 'syncpoint-crm' ); ?></label></th>
								<td>
									<label><input type="checkbox" name="skip_duplicates" value="1" checked> <?php esc_html_e( 'Skip duplicate records', 'syncpoint-crm' ); ?></label><br>
									<label><input type="checkbox" name="update_existing" value="1"> <?php esc_html_e( 'Update existing records', 'syncpoint-crm' ); ?></label>
								</td>
							</tr>
						</table>
						
						<p>
							<button type="submit" class="button button-primary button-hero" id="scrm-import-upload">
								<?php esc_html_e( 'Upload & Preview', 'syncpoint-crm' ); ?>
							</button>
						</p>
					</form>
				</div>
				
				<div id="scrm-import-step-2" style="display: none;">
					<h2><?php esc_html_e( 'Step 2: Map Fields', 'syncpoint-crm' ); ?></h2>
					<p><?php esc_html_e( 'Map the columns from your CSV file to CRM fields.', 'syncpoint-crm' ); ?></p>
					<div id="scrm-import-mapping"></div>
					<p>
						<button type="button" class="button button-primary" id="scrm-import-run"><?php esc_html_e( 'Run Import', 'syncpoint-crm' ); ?></button>
						<button type="button" class="button" id="scrm-import-back"><?php esc_html_e( 'Back', 'syncpoint-crm' ); ?></button>
					</p>
				</div>
				
				<div id="scrm-import-step-3" style="display: none;">
					<h2><?php esc_html_e( 'Import Complete', 'syncpoint-crm' ); ?></h2>
					<div id="scrm-import-results"></div>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=scrm-import' ) ); ?>" class="button"><?php esc_html_e( 'Import More', 'syncpoint-crm' ); ?></a>
					</p>
				</div>
			</div>
			
			<!-- Sample CSV Templates -->
			<div style="margin-top: 30px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Sample CSV Templates', 'syncpoint-crm' ); ?></h3>
				<p><?php esc_html_e( 'Download sample templates to see the expected format:', 'syncpoint-crm' ); ?></p>
				<table class="widefat" style="max-width: 600px;">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Contacts', 'syncpoint-crm' ); ?></strong></td>
							<td><code>email, first_name, last_name, phone, type, company_name, tags</code></td>
							<td>
								<a href="<?php echo esc_url( SCRM_PLUGIN_URL . 'samples/contacts-sample.csv' ); ?>" class="button button-small" download>
									<?php esc_html_e( 'Download', 'syncpoint-crm' ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Companies', 'syncpoint-crm' ); ?></strong></td>
							<td><code>name, email, phone, website, address, industry</code></td>
							<td>
								<a href="<?php echo esc_url( SCRM_PLUGIN_URL . 'samples/companies-sample.csv' ); ?>" class="button button-small" download>
									<?php esc_html_e( 'Download', 'syncpoint-crm' ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Transactions', 'syncpoint-crm' ); ?></strong></td>
							<td><code>contact_email, amount, type, gateway, currency, status</code></td>
							<td>
								<a href="<?php echo esc_url( SCRM_PLUGIN_URL . 'samples/transactions-sample.csv' ); ?>" class="button button-small" download>
									<?php esc_html_e( 'Download', 'syncpoint-crm' ); ?>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings() {
		$settings = new SCRM_Admin_Settings();
		$settings->render();
	}
}

