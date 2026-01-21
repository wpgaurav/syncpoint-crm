<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation, database table creation, and initial setup.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Activator
 *
 * @since 1.0.0
 */
class SCRM_Activator {

	/**
	 * Database version.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		self::check_requirements();
		self::create_tables();
		self::set_default_options();
		self::create_capabilities();
		self::schedule_events();

		// Store the installed version.
		update_option( 'scrm_version', SCRM_VERSION );
		update_option( 'scrm_db_version', self::DB_VERSION );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Check plugin requirements.
	 *
	 * @since 1.0.0
	 */
	private static function check_requirements() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( SCRM_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Starter CRM requires PHP 7.4 or higher.', 'syncpoint-crm' ),
				esc_html__( 'Plugin Activation Error', 'syncpoint-crm' ),
				array( 'back_link' => true )
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			deactivate_plugins( SCRM_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Starter CRM requires WordPress 6.0 or higher.', 'syncpoint-crm' ),
				esc_html__( 'Plugin Activation Error', 'syncpoint-crm' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create database tables.
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Contacts table.
		$table_name = $wpdb->prefix . 'scrm_contacts';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contact_id varchar(50) NOT NULL,
			type varchar(20) NOT NULL DEFAULT 'customer',
			status varchar(20) NOT NULL DEFAULT 'active',
			first_name varchar(100) DEFAULT '',
			last_name varchar(100) DEFAULT '',
			email varchar(255) NOT NULL,
			phone varchar(50) DEFAULT '',
			company_id bigint(20) unsigned DEFAULT NULL,
			currency varchar(3) DEFAULT 'USD',
			tax_id varchar(100) DEFAULT '',
			address_line_1 varchar(255) DEFAULT '',
			address_line_2 varchar(255) DEFAULT '',
			city varchar(100) DEFAULT '',
			state varchar(100) DEFAULT '',
			postal_code varchar(20) DEFAULT '',
			country varchar(2) DEFAULT '',
			custom_fields longtext DEFAULT NULL,
			source varchar(100) DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			created_by bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY contact_id (contact_id),
			KEY email (email),
			KEY type_status (type, status),
			KEY company_id (company_id),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );

		// Companies table.
		$table_name = $wpdb->prefix . 'scrm_companies';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			company_id varchar(50) NOT NULL,
			name varchar(255) NOT NULL,
			website varchar(255) DEFAULT '',
			email varchar(255) DEFAULT '',
			phone varchar(50) DEFAULT '',
			tax_id varchar(100) DEFAULT '',
			address_line_1 varchar(255) DEFAULT '',
			address_line_2 varchar(255) DEFAULT '',
			city varchar(100) DEFAULT '',
			state varchar(100) DEFAULT '',
			postal_code varchar(20) DEFAULT '',
			country varchar(2) DEFAULT '',
			industry varchar(100) DEFAULT '',
			custom_fields longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			created_by bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY company_id (company_id),
			KEY name (name(191))
		) {$charset_collate};";
		dbDelta( $sql );

		// Transactions table.
		$table_name = $wpdb->prefix . 'scrm_transactions';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			transaction_id varchar(100) NOT NULL,
			contact_id bigint(20) unsigned NOT NULL,
			invoice_id bigint(20) unsigned DEFAULT NULL,
			type varchar(20) NOT NULL DEFAULT 'payment',
			gateway varchar(50) NOT NULL DEFAULT 'manual',
			gateway_transaction_id varchar(255) DEFAULT '',
			amount decimal(15,2) NOT NULL DEFAULT 0.00,
			currency varchar(3) NOT NULL DEFAULT 'USD',
			status varchar(20) NOT NULL DEFAULT 'pending',
			description text DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY transaction_id (transaction_id),
			KEY contact_id (contact_id),
			KEY invoice_id (invoice_id),
			KEY gateway (gateway),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );

		// Tags table.
		$table_name = $wpdb->prefix . 'scrm_tags';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			color varchar(7) DEFAULT '#6B7280',
			description text DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$charset_collate};";
		dbDelta( $sql );

		// Tag relationships table.
		$table_name = $wpdb->prefix . 'scrm_tag_relationships';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tag_id bigint(20) unsigned NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			object_type varchar(20) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY tag_object (tag_id, object_id, object_type),
			KEY object_lookup (object_id, object_type)
		) {$charset_collate};";
		dbDelta( $sql );

		// Invoices table.
		$table_name = $wpdb->prefix . 'scrm_invoices';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			invoice_number varchar(50) NOT NULL,
			contact_id bigint(20) unsigned NOT NULL,
			company_id bigint(20) unsigned DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			issue_date date NOT NULL,
			due_date date NOT NULL,
			subtotal decimal(15,2) NOT NULL DEFAULT 0.00,
			tax_rate decimal(5,2) DEFAULT 0.00,
			tax_amount decimal(15,2) DEFAULT 0.00,
			discount_type varchar(20) DEFAULT 'fixed',
			discount_value decimal(15,2) DEFAULT 0.00,
			total decimal(15,2) NOT NULL DEFAULT 0.00,
			currency varchar(3) NOT NULL DEFAULT 'USD',
			notes text DEFAULT NULL,
			terms text DEFAULT NULL,
			payment_methods text DEFAULT NULL,
			paypal_payment_link varchar(500) DEFAULT '',
			stripe_payment_link varchar(500) DEFAULT '',
			pdf_path varchar(500) DEFAULT '',
			viewed_at datetime DEFAULT NULL,
			paid_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			created_by bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY invoice_number (invoice_number),
			KEY contact_id (contact_id),
			KEY status (status),
			KEY due_date (due_date)
		) {$charset_collate};";
		dbDelta( $sql );

		// Invoice items table.
		$table_name = $wpdb->prefix . 'scrm_invoice_items';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			invoice_id bigint(20) unsigned NOT NULL,
			description text NOT NULL,
			quantity decimal(10,2) NOT NULL DEFAULT 1.00,
			unit_price decimal(15,2) NOT NULL DEFAULT 0.00,
			total decimal(15,2) NOT NULL DEFAULT 0.00,
			sort_order int(11) DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY invoice_id (invoice_id)
		) {$charset_collate};";
		dbDelta( $sql );

		// Activity log table.
		$table_name = $wpdb->prefix . 'scrm_activity_log';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_id bigint(20) unsigned NOT NULL,
			object_type varchar(50) NOT NULL,
			action varchar(100) NOT NULL,
			description text DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			ip_address varchar(45) DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY object_lookup (object_id, object_type),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );

		// Webhook log table.
		$table_name = $wpdb->prefix . 'scrm_webhook_log';
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source varchar(100) NOT NULL,
			endpoint varchar(255) DEFAULT '',
			payload longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			response text DEFAULT NULL,
			processed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY source (source),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );
	}

	/**
	 * Set default options.
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		// Only set if not already set.
		if ( get_option( 'scrm_settings' ) ) {
			return;
		}

		$default_settings = array(
			'general' => array(
				'default_currency'     => 'USD',
				'date_format'          => get_option( 'date_format', 'Y-m-d' ),
				'contact_id_prefix'    => 'CUST',
				'company_id_prefix'    => 'COMP',
				'invoice_prefix'       => 'INV',
				'next_contact_number'  => 1,
				'next_company_number'  => 1,
				'next_invoice_number'  => 1,
				'next_transaction_number' => 1,
			),
			'paypal'   => array(
				'enabled'       => false,
				'mode'          => 'sandbox',
				'client_id'     => '',
				'client_secret' => '',
				'webhook_id'    => '',
			),
			'stripe'   => array(
				'enabled'          => false,
				'mode'             => 'test',
				'test_publishable' => '',
				'test_secret'      => '',
				'live_publishable' => '',
				'live_secret'      => '',
				'webhook_secret'   => '',
			),
			'invoices' => array(
				'company_name'    => get_bloginfo( 'name' ),
				'company_address' => '',
				'company_tax_id'  => '',
				'company_logo'    => '',
				'default_terms'   => __( 'Payment is due within 30 days of invoice date.', 'syncpoint-crm' ),
				'default_notes'   => __( 'Thank you for your business!', 'syncpoint-crm' ),
				'payment_methods' => array( 'paypal', 'stripe' ),
			),
			'webhooks' => array(
				'enabled'     => true,
				'secret_key'  => wp_generate_password( 32, false ),
				'allowed_ips' => '',
			),
		);

		update_option( 'scrm_settings', $default_settings );
	}

	/**
	 * Create custom capabilities.
	 *
	 * @since 1.0.0
	 */
	private static function create_capabilities() {
		$admin = get_role( 'administrator' );

		if ( ! $admin ) {
			return;
		}

		$capabilities = array(
			'scrm_manage_contacts',
			'scrm_manage_companies',
			'scrm_manage_transactions',
			'scrm_manage_invoices',
			'scrm_manage_settings',
			'scrm_view_dashboard',
			'scrm_import_data',
			'scrm_export_data',
		);

		foreach ( $capabilities as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Schedule cron events.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_events() {
		// Check for overdue invoices daily.
		if ( ! wp_next_scheduled( 'scrm_check_overdue_invoices' ) ) {
			wp_schedule_event( time(), 'daily', 'scrm_check_overdue_invoices' );
		}

		// Cleanup old webhook logs weekly.
		if ( ! wp_next_scheduled( 'scrm_cleanup_webhook_logs' ) ) {
			wp_schedule_event( time(), 'weekly', 'scrm_cleanup_webhook_logs' );
		}
	}
}
