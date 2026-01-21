<?php
/**
 * Starter CRM Core Functions
 *
 * Core functions for creating, reading, updating, and deleting CRM data.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Contact Functions
|--------------------------------------------------------------------------
*/

/**
 * Get a contact by ID.
 *
 * @since 1.0.0
 * @param int|string $contact_id Database ID or custom contact ID (e.g., CUST-001).
 * @return object|null Contact object or null if not found.
 */
function scrm_get_contact( $contact_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	// Check if it's a custom ID (string with letters).
	if ( ! is_numeric( $contact_id ) ) {
		$contact = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE contact_id = %s", $contact_id )
		);
	} else {
		$contact = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $contact_id )
		);
	}

	if ( ! $contact ) {
		return null;
	}

	// Decode JSON fields.
	$contact->custom_fields = json_decode( $contact->custom_fields, true ) ?: array();

	/**
	 * Filter the contact object.
	 *
	 * @since 1.0.0
	 * @param object $contact The contact object.
	 */
	return apply_filters( 'scrm_get_contact', $contact );
}

/**
 * Get a contact by email.
 *
 * @since 1.0.0
 * @param string $email Email address.
 * @return object|null Contact object or null if not found.
 */
function scrm_get_contact_by_email( $email ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	$contact = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", sanitize_email( $email ) )
	);

	if ( ! $contact ) {
		return null;
	}

	$contact->custom_fields = json_decode( $contact->custom_fields, true ) ?: array();

	return apply_filters( 'scrm_get_contact', $contact );
}

/**
 * Create a new contact.
 *
 * @since 1.0.0
 * @param array $data Contact data.
 * @return int|WP_Error Contact ID on success, WP_Error on failure.
 */
function scrm_create_contact( $data ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	// Validate required fields.
	if ( empty( $data['email'] ) ) {
		return new WP_Error( 'missing_email', __( 'Email address is required.', 'syncpoint-crm' ) );
	}

	if ( ! is_email( $data['email'] ) ) {
		return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'syncpoint-crm' ) );
	}

	// Check for duplicate.
	$existing = scrm_get_contact_by_email( $data['email'] );
	if ( $existing ) {
		return new WP_Error( 'duplicate_contact', __( 'A contact with this email already exists.', 'syncpoint-crm' ), array( 'existing_id' => $existing->id ) );
	}

	// Set defaults.
	$defaults = array(
		'type'           => 'customer',
		'status'         => 'active',
		'first_name'     => '',
		'last_name'      => '',
		'phone'          => '',
		'company_id'     => null,
		'currency'       => scrm_get_default_currency(),
		'tax_id'         => '',
		'address_line_1' => '',
		'address_line_2' => '',
		'city'           => '',
		'state'          => '',
		'postal_code'    => '',
		'country'        => '',
		'custom_fields'  => array(),
		'source'         => '',
	);

	$data = wp_parse_args( $data, $defaults );

	/**
	 * Filter contact data before saving.
	 *
	 * @since 1.0.0
	 * @param array $data Contact data.
	 */
	$data = apply_filters( 'scrm_contact_data_before_save', $data );

	/**
	 * Fires before a contact is saved.
	 *
	 * @since 1.0.0
	 * @param array $data Contact data.
	 */
	do_action( 'scrm_before_contact_save', $data );

	// Generate custom ID.
	$custom_id = scrm_generate_id( 'contact', $data['type'] );

	// Prepare data for insertion.
	$insert_data = array(
		'contact_id'     => $custom_id,
		'type'           => sanitize_text_field( $data['type'] ),
		'status'         => sanitize_text_field( $data['status'] ),
		'first_name'     => sanitize_text_field( $data['first_name'] ),
		'last_name'      => sanitize_text_field( $data['last_name'] ),
		'email'          => sanitize_email( $data['email'] ),
		'phone'          => sanitize_text_field( $data['phone'] ),
		'company_id'     => $data['company_id'] ? absint( $data['company_id'] ) : null,
		'currency'       => sanitize_text_field( $data['currency'] ),
		'tax_id'         => sanitize_text_field( $data['tax_id'] ),
		'address_line_1' => sanitize_text_field( $data['address_line_1'] ),
		'address_line_2' => sanitize_text_field( $data['address_line_2'] ),
		'city'           => sanitize_text_field( $data['city'] ),
		'state'          => sanitize_text_field( $data['state'] ),
		'postal_code'    => sanitize_text_field( $data['postal_code'] ),
		'country'        => sanitize_text_field( $data['country'] ),
		'custom_fields'  => wp_json_encode( $data['custom_fields'] ),
		'source'         => sanitize_text_field( $data['source'] ),
		'created_at'     => current_time( 'mysql' ),
		'updated_at'     => current_time( 'mysql' ),
		'created_by'     => get_current_user_id(),
	);

	// Handle address array if provided.
	if ( isset( $data['address'] ) && is_array( $data['address'] ) ) {
		$insert_data['address_line_1'] = sanitize_text_field( $data['address']['line_1'] ?? '' );
		$insert_data['address_line_2'] = sanitize_text_field( $data['address']['line_2'] ?? '' );
		$insert_data['city']           = sanitize_text_field( $data['address']['city'] ?? '' );
		$insert_data['state']          = sanitize_text_field( $data['address']['state'] ?? '' );
		$insert_data['postal_code'
