<?php
/**
 * Starter CRM Helper Functions
 *
 * Utility functions for settings, currency, IDs, caching, and more.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Settings Functions
|--------------------------------------------------------------------------
*/

/**
 * Get a plugin setting.
 *
 * @since 1.0.0
 * @param string $group   Settings group (general, paypal, stripe, invoices, webhooks).
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 * @return mixed Setting value.
 */
function scrm_get_setting( $group, $key, $default = '' ) {
	$settings = get_option( 'scrm_settings', array() );

	if ( isset( $settings[ $group ][ $key ] ) ) {
		return $settings[ $group ][ $key ];
	}

	return $default;
}

/**
 * Get all settings in a group.
 *
 * @since 1.0.0
 * @param string $group Settings group.
 * @return array Settings array.
 */
function scrm_get_settings( $group ) {
	$settings = get_option( 'scrm_settings', array() );
	return isset( $settings[ $group ] ) ? $settings[ $group ] : array();
}

/**
 * Update a plugin setting.
 *
 * @since 1.0.0
 * @param string $group Settings group.
 * @param string $key   Setting key.
 * @param mixed  $value Setting value.
 * @return bool True on success.
 */
function scrm_update_setting( $group, $key, $value ) {
	$settings = get_option( 'scrm_settings', array() );

	if ( ! isset( $settings[ $group ] ) ) {
		$settings[ $group ] = array();
	}

	$settings[ $group ][ $key ] = $value;

	return update_option( 'scrm_settings', $settings );
}

/**
 * Check if a feature is enabled.
 *
 * @since 1.0.0
 * @param string $feature Feature name (paypal, stripe, webhooks).
 * @return bool True if enabled.
 */
function scrm_is_enabled( $feature ) {
	return (bool) scrm_get_setting( $feature, 'enabled', false );
}

/**
 * Get default settings.
 *
 * @since 1.0.0
 * @return array Default settings.
 */
function scrm_get_default_settings() {
	return array(
		'general' => array(
			'default_currency'     => 'USD',
			'date_format'          => 'Y-m-d',
			'contact_id_prefix'    => 'CUST',
			'company_id_prefix'    => 'COMP',
			'invoice_prefix'       => 'INV',
			'next_contact_number'  => 1,
			'next_company_number'  => 1,
			'next_invoice_number'  => 1,
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
			'company_name'    => '',
			'company_address' => '',
			'company_tax_id'  => '',
			'company_logo'    => '',
			'default_terms'   => '',
			'default_notes'   => '',
			'payment_methods' => array( 'paypal', 'stripe' ),
		),
		'webhooks' => array(
			'enabled'     => true,
			'secret_key'  => '',
			'allowed_ips' => '',
		),
	);
}

/*
|--------------------------------------------------------------------------
| Currency Functions
|--------------------------------------------------------------------------
*/

/**
 * Get the default currency.
 *
 * @since 1.0.0
 * @return string Currency code.
 */
function scrm_get_default_currency() {
	$currency = scrm_get_setting( 'general', 'default_currency', 'USD' );

	/**
	 * Filter the default currency.
	 *
	 * @since 1.0.0
	 * @param string $currency Currency code.
	 */
	return apply_filters( 'scrm_default_currency', $currency );
}

/**
 * Get supported currencies.
 *
 * @since 1.0.0
 * @return array Array of currency data.
 */
function scrm_get_currencies() {
	$currencies = array(
		'USD' => array(
			'name'      => 'US Dollar',
			'symbol'    => '$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'EUR' => array(
			'name'      => 'Euro',
			'symbol'    => '€',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'GBP' => array(
			'name'      => 'British Pound',
			'symbol'    => '£',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'CAD' => array(
			'name'      => 'Canadian Dollar',
			'symbol'    => 'C$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'AUD' => array(
			'name'      => 'Australian Dollar',
			'symbol'    => 'A$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'INR' => array(
			'name'      => 'Indian Rupee',
			'symbol'    => '₹',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'JPY' => array(
			'name'      => 'Japanese Yen',
			'symbol'    => '¥',
			'decimals'  => 0,
			'position'  => 'before',
		),
		'CNY' => array(
			'name'      => 'Chinese Yuan',
			'symbol'    => '¥',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'CHF' => array(
			'name'      => 'Swiss Franc',
			'symbol'    => 'CHF',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'SGD' => array(
			'name'      => 'Singapore Dollar',
			'symbol'    => 'S$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'NZD' => array(
			'name'      => 'New Zealand Dollar',
			'symbol'    => 'NZ$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'BRL' => array(
			'name'      => 'Brazilian Real',
			'symbol'    => 'R$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'MXN' => array(
			'name'      => 'Mexican Peso',
			'symbol'    => 'MX$',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'SEK' => array(
			'name'      => 'Swedish Krona',
			'symbol'    => 'kr',
			'decimals'  => 2,
			'position'  => 'after',
		),
		'NOK' => array(
			'name'      => 'Norwegian Krone',
			'symbol'    => 'kr',
			'decimals'  => 2,
			'position'  => 'after',
		),
		'DKK' => array(
			'name'      => 'Danish Krone',
			'symbol'    => 'kr',
			'decimals'  => 2,
			'position'  => 'after',
		),
		'PLN' => array(
			'name'      => 'Polish Zloty',
			'symbol'    => 'zł',
			'decimals'  => 2,
			'position'  => 'after',
		),
		'ZAR' => array(
			'name'      => 'South African Rand',
			'symbol'    => 'R',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'AED' => array(
			'name'      => 'UAE Dirham',
			'symbol'    => 'د.إ',
			'decimals'  => 2,
			'position'  => 'before',
		),
		'HKD' => array(
			'name'      => 'Hong Kong Dollar',
			'symbol'    => 'HK$',
			'decimals'  => 2,
			'position'  => 'before',
		),
	);

	/**
	 * Filter supported currencies.
	 *
	 * @since 1.0.0
	 * @param array $currencies Array of currency data.
	 */
	return apply_filters( 'scrm_supported_currencies', $currencies );
}

/**
 * Get currency symbol.
 *
 * @since 1.0.0
 * @param string $currency_code Currency code.
 * @return string Currency symbol.
 */
function scrm_get_currency_symbol( $currency_code ) {
	$currencies = scrm_get_currencies();

	if ( isset( $currencies[ $currency_code ]['symbol'] ) ) {
		return $currencies[ $currency_code ]['symbol'];
	}

	return $currency_code;
}

/**
 * Format a currency amount.
 *
 * @since 1.0.0
 * @param float  $amount   Amount.
 * @param string $currency Currency code.
 * @return string Formatted currency string.
 */
function scrm_format_currency( $amount, $currency = '' ) {
	if ( empty( $currency ) ) {
		$currency = scrm_get_default_currency();
	}

	$currencies = scrm_get_currencies();
	$currency_data = isset( $currencies[ $currency ] ) ? $currencies[ $currency ] : array(
		'symbol'   => $currency,
		'decimals' => 2,
		'position' => 'before',
	);

	$symbol = $currency_data['symbol'];
	$decimals = $currency_data['decimals'];
	$position = $currency_data['position'];

	$formatted_amount = number_format( $amount, $decimals, '.', ',' );

	if ( 'after' === $position ) {
		$formatted = $formatted_amount . ' ' . $symbol;
	} else {
		$formatted = $symbol . $formatted_amount;
	}

	/**
	 * Filter formatted currency.
	 *
	 * @since 1.0.0
	 * @param string $formatted Formatted string.
	 * @param float  $amount    Amount.
	 * @param string $currency  Currency code.
	 */
	return apply_filters( 'scrm_format_currency', $formatted, $amount, $currency );
}

/*
|--------------------------------------------------------------------------
| ID Generation Functions
|--------------------------------------------------------------------------
*/

/**
 * Generate a custom ID.
 *
 * @since 1.0.0
 * @param string $type    ID type (contact, company, invoice, transaction).
 * @param string $subtype Subtype (for contacts: customer, lead, prospect).
 * @return string Generated ID.
 */
function scrm_generate_id( $type, $subtype = '' ) {
	$format = '';
	$number = 1;

	switch ( $type ) {
		case 'contact':
			$prefixes = array(
				'customer' => scrm_get_setting( 'general', 'contact_id_prefix', 'CUST' ),
				'lead'     => 'LEAD',
				'prospect' => 'PROS',
			);
			$prefix = isset( $prefixes[ $subtype ] ) ? $prefixes[ $subtype ] : $prefixes['customer'];
			$format = $prefix . '-{number}';
			$number = scrm_get_next_number( 'contact' );

			/**
			 * Filter contact ID format.
			 *
			 * @since 1.0.0
			 * @param string $format  ID format.
			 * @param string $subtype Contact type.
			 */
			$format = apply_filters( 'scrm_contact_id_format', $format, $subtype );
			break;

		case 'company':
			$prefix = scrm_get_setting( 'general', 'company_id_prefix', 'COMP' );
			$format = $prefix . '-{number}';
			$number = scrm_get_next_number( 'company' );

			/**
			 * Filter company ID format.
			 *
			 * @since 1.0.0
			 * @param string $format ID format.
			 */
			$format = apply_filters( 'scrm_company_id_format', $format );
			break;

		case 'invoice':
			$prefix = scrm_get_setting( 'general', 'invoice_prefix', 'INV' );
			$format = $prefix . '-{year}-{number}';
			$number = scrm_get_next_number( 'invoice' );

			/**
			 * Filter invoice number format.
			 *
			 * @since 1.0.0
			 * @param string $format Number format.
			 */
			$format = apply_filters( 'scrm_invoice_number_format', $format );
			break;

		case 'transaction':
			$format = 'TXN-{year}-{number}';
			$number = scrm_get_next_number( 'transaction' );
			break;

		default:
			return wp_generate_uuid4();
	}

	// Replace placeholders.
	$padded_number = str_pad( $number, 3, '0', STR_PAD_LEFT );
	$replacements = array(
		'{number}' => $padded_number,
		'{year}'   => date( 'Y' ),
		'{month}'  => date( 'm' ),
	);

	$id = str_replace( array_keys( $replacements ), array_values( $replacements ), $format );

	return $id;
}

/**
 * Get and increment the next number for an ID type.
 *
 * @since 1.0.0
 * @param string $type ID type.
 * @return int Next number.
 */
function scrm_get_next_number( $type ) {
	$key = 'next_' . $type . '_number';
	$number = (int) scrm_get_setting( 'general', $key, 1 );

	// Increment for next use.
	scrm_update_setting( 'general', $key, $number + 1 );

	return $number;
}

/*
|--------------------------------------------------------------------------
| Capability Functions
|--------------------------------------------------------------------------
*/

/**
 * Check if current user can perform an action.
 *
 * @since 1.0.0
 * @param string $capability Capability to check (without scrm_ prefix).
 * @return bool True if user has capability.
 */
function scrm_current_user_can( $capability ) {
	$capability = 'scrm_' . $capability;

	/**
	 * Filter capability mappings.
	 *
	 * @since 1.0.0
	 * @param array $mappings Capability to role mappings.
	 */
	$mappings = apply_filters( 'scrm_capability_mappings', array(
		'scrm_manage_contacts'     => 'manage_options',
		'scrm_manage_companies'    => 'manage_options',
		'scrm_manage_transactions' => 'manage_options',
		'scrm_manage_invoices'     => 'manage_options',
		'scrm_manage_settings'     => 'manage_options',
		'scrm_view_dashboard'      => 'manage_options',
		'scrm_import_data'         => 'manage_options',
		'scrm_export_data'         => 'manage_options',
	) );

	// Map SCRM capability to WordPress capability.
	$wp_capability = isset( $mappings[ $capability ] ) ? $mappings[ $capability ] : 'manage_options';

	return current_user_can( $wp_capability );
}

/*
|--------------------------------------------------------------------------
| Caching Functions
|--------------------------------------------------------------------------
*/

/**
 * Get cached value.
 *
 * @since 1.0.0
 * @param string $key Cache key.
 * @return mixed|false Cached value or false.
 */
function scrm_cache_get( $key ) {
	return get_transient( 'scrm_' . $key );
}

/**
 * Set cached value.
 *
 * @since 1.0.0
 * @param string $key        Cache key.
 * @param mixed  $value      Value to cache.
 * @param int    $expiration Expiration in seconds.
 * @return bool True on success.
 */
function scrm_cache_set( $key, $value, $expiration = HOUR_IN_SECONDS ) {
	return set_transient( 'scrm_' . $key, $value, $expiration );
}

/**
 * Delete cached value.
 *
 * @since 1.0.0
 * @param string $key Cache key.
 * @return bool True on success.
 */
function scrm_cache_delete( $key ) {
	return delete_transient( 'scrm_' . $key );
}

/**
 * Delete all cached values in a group.
 *
 * @since 1.0.0
 * @param string $group Cache group prefix.
 * @return bool True on success.
 */
function scrm_cache_delete_group( $group ) {
	global $wpdb;

	$like = $wpdb->esc_like( 'scrm_' . $group );

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_' . $like . '%',
		'_transient_timeout_' . $like . '%'
	) );

	return true;
}

/*
|--------------------------------------------------------------------------
| Utility Functions
|--------------------------------------------------------------------------
*/

/**
 * Get client IP address.
 *
 * @since 1.0.0
 * @return string IP address.
 */
function scrm_get_client_ip() {
	$ip = '';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		// Take the first IP if multiple.
		if ( strpos( $ip, ',' ) !== false ) {
			$ip = trim( explode( ',', $ip )[0] );
		}
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	// Validate IP.
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return $ip;
	}

	return '0.0.0.0';
}

/**
 * Generate a random string.
 *
 * @since 1.0.0
 * @param int $length String length.
 * @return string Random string.
 */
function scrm_generate_random_string( $length = 32 ) {
	return bin2hex( random_bytes( $length / 2 ) );
}

/**
 * Encrypt sensitive data.
 *
 * @since 1.0.0
 * @param string $data Data to encrypt.
 * @return string Encrypted data.
 */
function scrm_encrypt( $data ) {
	if ( empty( $data ) ) {
		return '';
	}

	$key = wp_salt( 'auth' );
	$iv = substr( $key, 0, 16 );

	$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );

	return base64_encode( $encrypted );
}

/**
 * Decrypt sensitive data.
 *
 * @since 1.0.0
 * @param string $data Encrypted data.
 * @return string Decrypted data.
 */
function scrm_decrypt( $data ) {
	if ( empty( $data ) ) {
		return '';
	}

	$key = wp_salt( 'auth' );
	$iv = substr( $key, 0, 16 );

	$decrypted = openssl_decrypt( base64_decode( $data ), 'AES-256-CBC', $key, 0, $iv );

	return $decrypted ?: '';
}

/**
 * Sanitize hex color.
 *
 * @since 1.0.0
 * @param string $color Hex color value.
 * @return string Sanitized color or default.
 */
function scrm_sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}

	return '#6B7280'; // Default gray.
}

/**
 * Get available contact types.
 *
 * @since 1.0.0
 * @return array Contact types.
 */
function scrm_get_contact_types() {
	$types = array(
		'customer' => __( 'Customer', 'syncpoint-crm' ),
		'lead'     => __( 'Lead', 'syncpoint-crm' ),
		'prospect' => __( 'Prospect', 'syncpoint-crm' ),
	);

	/**
	 * Filter contact types.
	 *
	 * @since 1.0.0
	 * @param array $types Contact types.
	 */
	return apply_filters( 'scrm_contact_types', $types );
}

/**
 * Get available contact statuses.
 *
 * @since 1.0.0
 * @return array Contact statuses.
 */
function scrm_get_contact_statuses() {
	$statuses = array(
		'active'   => __( 'Active', 'syncpoint-crm' ),
		'inactive' => __( 'Inactive', 'syncpoint-crm' ),
		'archived' => __( 'Archived', 'syncpoint-crm' ),
	);

	/**
	 * Filter contact statuses.
	 *
	 * @since 1.0.0
	 * @param array $statuses Contact statuses.
	 */
	return apply_filters( 'scrm_contact_statuses', $statuses );
}

/**
 * Get available transaction types.
 *
 * @since 1.0.0
 * @return array Transaction types.
 */
function scrm_get_transaction_types() {
	$types = array(
		'payment'      => __( 'Payment', 'syncpoint-crm' ),
		'refund'       => __( 'Refund', 'syncpoint-crm' ),
		'subscription' => __( 'Subscription', 'syncpoint-crm' ),
		'payout'       => __( 'Payout', 'syncpoint-crm' ),
	);

	/**
	 * Filter transaction types.
	 *
	 * @since 1.0.0
	 * @param array $types Transaction types.
	 */
	return apply_filters( 'scrm_transaction_types', $types );
}

/**
 * Get available invoice statuses.
 *
 * @since 1.0.0
 * @return array Invoice statuses.
 */
function scrm_get_invoice_statuses() {
	$statuses = array(
		'draft'     => __( 'Draft', 'syncpoint-crm' ),
		'sent'      => __( 'Sent', 'syncpoint-crm' ),
		'viewed'    => __( 'Viewed', 'syncpoint-crm' ),
		'paid'      => __( 'Paid', 'syncpoint-crm' ),
		'overdue'   => __( 'Overdue', 'syncpoint-crm' ),
		'cancelled' => __( 'Cancelled', 'syncpoint-crm' ),
	);

	/**
	 * Filter invoice statuses.
	 *
	 * @since 1.0.0
	 * @param array $statuses Invoice statuses.
	 */
	return apply_filters( 'scrm_invoice_statuses', $statuses );
}

/**
 * Get template part.
 *
 * @since 1.0.0
 * @param string $template Template path relative to templates folder.
 * @param array  $args     Variables to pass to template.
 */
function scrm_get_template( $template, $args = array() ) {
	$template_path = scrm_locate_template( $template );

	if ( ! $template_path ) {
		return;
	}

	// Extract args so they're available in template.
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	}

	include $template_path;
}

/**
 * Locate a template file.
 *
 * @since 1.0.0
 * @param string $template Template path.
 * @return string|false Template path or false.
 */
function scrm_locate_template( $template ) {
	// Look in theme first.
	$theme_path = get_stylesheet_directory() . '/starter-crm/' . $template;
	if ( file_exists( $theme_path ) ) {
		return $theme_path;
	}

	$theme_path = get_stylesheet_directory() . '/starter-crm/templates/' . $template;
	if ( file_exists( $theme_path ) ) {
		return $theme_path;
	}

	// Fall back to plugin templates.
	$plugin_path = SCRM_PLUGIN_DIR . 'templates/' . $template;
	if ( file_exists( $plugin_path ) ) {
		return $plugin_path;
	}

	return false;
}

/**
 * Check if template exists in theme.
 *
 * @since 1.0.0
 * @param string $template Template path.
 * @return bool True if exists in theme.
 */
function scrm_template_exists_in_theme( $template ) {
	$theme_path = get_stylesheet_directory() . '/starter-crm/' . $template;
	if ( file_exists( $theme_path ) ) {
		return true;
	}

	$theme_path = get_stylesheet_directory() . '/starter-crm/templates/' . $template;
	return file_exists( $theme_path );
}

/**
 * Format a date.
 *
 * @since 1.0.0
 * @param string $date   Date string.
 * @param string $format Format (optional, uses WordPress setting).
 * @return string Formatted date.
 */
function scrm_format_date( $date, $format = '' ) {
	if ( empty( $format ) ) {
		$format = get_option( 'date_format' );
	}

	$timestamp = strtotime( $date );

	if ( ! $timestamp ) {
		return $date;
	}

	return date_i18n( $format, $timestamp );
}

/**
 * Format a datetime.
 *
 * @since 1.0.0
 * @param string $datetime Datetime string.
 * @return string Formatted datetime.
 */
function scrm_format_datetime( $datetime ) {
	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );

	$timestamp = strtotime( $datetime );

	if ( ! $timestamp ) {
		return $datetime;
	}

	return date_i18n( $date_format . ' ' . $time_format, $timestamp );
}

/**
 * Get countries list.
 *
 * @since 1.0.0
 * @return array Country code => Country name.
 */
function scrm_get_countries() {
	return array(
		'US' => 'United States',
		'CA' => 'Canada',
		'GB' => 'United Kingdom',
		'AU' => 'Australia',
		'DE' => 'Germany',
		'FR' => 'France',
		'IN' => 'India',
		'JP' => 'Japan',
		'CN' => 'China',
		'BR' => 'Brazil',
		'MX' => 'Mexico',
		'IT' => 'Italy',
		'ES' => 'Spain',
		'NL' => 'Netherlands',
		'SE' => 'Sweden',
		'NO' => 'Norway',
		'DK' => 'Denmark',
		'FI' => 'Finland',
		'PL' => 'Poland',
		'CH' => 'Switzerland',
		'AT' => 'Austria',
		'BE' => 'Belgium',
		'IE' => 'Ireland',
		'NZ' => 'New Zealand',
		'SG' => 'Singapore',
		'HK' => 'Hong Kong',
		'AE' => 'United Arab Emirates',
		'ZA' => 'South Africa',
		'IL' => 'Israel',
		'KR' => 'South Korea',
		// Add more as needed.
	);
}

/**
 * Log a webhook request.
 *
 * @since 1.0.0
 * @param string $source   Webhook source.
 * @param string $endpoint Endpoint URL.
 * @param array  $payload  Request payload.
 * @param string $status   Status (success, failed, pending).
 * @param string $response Response message.
 * @return int|false Log ID on success, false on failure.
 */
function scrm_log_webhook( $source, $endpoint, $payload, $status = 'success', $response = '' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_webhook_log';

	$result = $wpdb->insert( $table, array(
		'source'       => sanitize_text_field( $source ),
		'endpoint'     => esc_url_raw( $endpoint ),
		'payload'      => wp_json_encode( $payload ),
		'status'       => sanitize_text_field( $status ),
		'response'     => sanitize_textarea_field( $response ),
		'processed_at' => 'success' === $status ? current_time( 'mysql' ) : null,
		'created_at'   => current_time( 'mysql' ),
	) );

	return $result ? $wpdb->insert_id : false;
}

/*
|--------------------------------------------------------------------------
| Gateway Sync Functions
|--------------------------------------------------------------------------
*/

/**
 * Start a sync log entry.
 *
 * @since 1.0.0
 * @param string $gateway   Gateway name (paypal, stripe).
 * @param string $sync_type Sync type (manual, cron).
 * @return int Sync log ID.
 */
function scrm_start_sync_log( $gateway, $sync_type = 'manual' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_sync_log';

	$wpdb->insert( $table, array(
		'gateway'    => sanitize_text_field( $gateway ),
		'sync_type'  => sanitize_text_field( $sync_type ),
		'status'     => 'running',
		'started_at' => current_time( 'mysql' ),
	) );

	return $wpdb->insert_id;
}

/**
 * Complete a sync log entry.
 *
 * @since 1.0.0
 * @param int    $log_id           Sync log ID.
 * @param string $status           Status (completed, failed).
 * @param int    $synced           Transactions synced.
 * @param int    $skipped          Transactions skipped.
 * @param int    $contacts_created Contacts created.
 * @param string $error_message    Error message if failed.
 * @return bool True on success.
 */
function scrm_complete_sync_log( $log_id, $status, $synced = 0, $skipped = 0, $contacts_created = 0, $error_message = '' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_sync_log';

	return (bool) $wpdb->update(
		$table,
		array(
			'status'               => sanitize_text_field( $status ),
			'transactions_synced'  => absint( $synced ),
			'transactions_skipped' => absint( $skipped ),
			'contacts_created'     => absint( $contacts_created ),
			'error_message'        => sanitize_textarea_field( $error_message ),
			'completed_at'         => current_time( 'mysql' ),
		),
		array( 'id' => absint( $log_id ) )
	);
}

/**
 * Get sync logs for a gateway.
 *
 * @since 1.0.0
 * @param string $gateway Gateway name.
 * @param int    $limit   Number of logs to return.
 * @return array Array of sync log entries.
 */
function scrm_get_sync_logs( $gateway, $limit = 10 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_sync_log';

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE gateway = %s ORDER BY started_at DESC LIMIT %d",
		$gateway,
		$limit
	) );
}

/**
 * Get last successful sync for a gateway.
 *
 * @since 1.0.0
 * @param string $gateway Gateway name.
 * @return object|null Sync log entry or null.
 */
function scrm_get_last_sync( $gateway ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_sync_log';

	return $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE gateway = %s AND status = 'completed' ORDER BY completed_at DESC LIMIT 1",
		$gateway
	) );
}

/**
 * Check if a sync is currently running for a gateway.
 *
 * @since 1.0.0
 * @param string $gateway Gateway name.
 * @return bool True if sync is running.
 */
function scrm_is_sync_running( $gateway ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_sync_log';

	$running = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE gateway = %s AND status = 'running' LIMIT 1",
		$gateway
	) );

	return (bool) $running;
}

/**
 * Get next scheduled sync time for a gateway.
 *
 * @since 1.0.0
 * @param string $gateway Gateway name.
 * @return int|false Timestamp or false if not scheduled.
 */
function scrm_get_next_sync_time( $gateway ) {
	$hook = 'scrm_' . $gateway . '_sync';
	return wp_next_scheduled( $hook );
}

/**
 * Reschedule gateway sync based on frequency setting.
 *
 * @since 1.0.0
 * @param string $gateway   Gateway name.
 * @param string $frequency Frequency (hourly, twicedaily, daily).
 * @return void
 */
function scrm_reschedule_sync( $gateway, $frequency ) {
	$hook = 'scrm_' . $gateway . '_sync';

	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}

	if ( ! empty( $frequency ) && 'disabled' !== $frequency ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, $frequency, $hook );
	}
}

/**
 * Add contact tag helper.
 *
 * @since 1.0.0
 * @param int $contact_id Contact ID.
 * @param int $tag_id     Tag ID.
 * @return bool True on success.
 */
function scrm_add_contact_tag( $contact_id, $tag_id ) {
	return scrm_assign_tag( $tag_id, $contact_id, 'contact' );
}

/*
|--------------------------------------------------------------------------
| Email Functions
|--------------------------------------------------------------------------
*/

/**
 * Get email template HTML wrapper.
 *
 * @since 1.0.0
 * @param string $content Email content.
 * @return string Wrapped email HTML.
 */
function scrm_get_email_template( $content ) {
	$settings = scrm_get_settings( 'invoices' );
	$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );
	$company_logo = $settings['company_logo'] ?? '';

	ob_start();
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; background-color: #f5f5f5;">
		<table role="presentation" style="width: 100%; border-collapse: collapse;">
			<tr>
				<td style="padding: 40px 20px;">
					<table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
						<?php if ( $company_logo ) : ?>
						<tr>
							<td style="padding: 30px 40px 20px; text-align: center;">
								<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>" style="max-width: 200px; height: auto;">
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td style="padding: 30px 40px;">
								<div style="color: #333333; font-size: 16px; line-height: 1.6;">
									<?php echo wp_kses_post( wpautop( $content ) ); ?>
								</div>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px 40px 30px; border-top: 1px solid #eeeeee;">
								<p style="margin: 0; color: #888888; font-size: 14px; text-align: center;">
									<?php echo esc_html( $company_name ); ?>
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
 * Log an email.
 *
 * @since 1.0.0
 * @param int    $contact_id Contact ID.
 * @param string $subject    Email subject.
 * @param string $message    Email message.
 * @param string $status     Email status (sent, failed).
 * @return int|false Email log ID or false on failure.
 */
function scrm_log_email( $contact_id, $subject, $message, $status = 'sent' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_email_log';

	$result = $wpdb->insert( $table, array(
		'contact_id' => absint( $contact_id ),
		'subject'    => sanitize_text_field( $subject ),
		'message'    => wp_kses_post( $message ),
		'status'     => sanitize_text_field( $status ),
		'created_at' => current_time( 'mysql' ),
	) );

	return $result ? $wpdb->insert_id : false;
}

/**
 * Get email logs for a contact.
 *
 * @since 1.0.0
 * @param int $contact_id Contact ID.
 * @param int $limit      Number of logs to return.
 * @return array Array of email log entries.
 */
function scrm_get_email_logs( $contact_id, $limit = 20 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_email_log';

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE contact_id = %d ORDER BY created_at DESC LIMIT %d",
		$contact_id,
		$limit
	) );
}

/**
 * Get all email logs.
 *
 * @since 1.0.0
 * @param array $args Query arguments.
 * @return array Array of email log entries.
 */
function scrm_get_all_email_logs( $args = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_email_log';

	$defaults = array(
		'limit'  => 50,
		'offset' => 0,
		'status' => '',
	);
	$args = wp_parse_args( $args, $defaults );

	$where = '1=1';
	if ( ! empty( $args['status'] ) ) {
		$where .= $wpdb->prepare( ' AND status = %s', $args['status'] );
	}

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT e.*, c.first_name, c.last_name, c.email as contact_email
		FROM {$table} e
		LEFT JOIN {$wpdb->prefix}scrm_contacts c ON e.contact_id = c.id
		WHERE {$where}
		ORDER BY e.created_at DESC
		LIMIT %d OFFSET %d",
		$args['limit'],
		$args['offset']
	) );
}
