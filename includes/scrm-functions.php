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
		$insert_data['postal_code']    = sanitize_text_field( $data['address']['postal_code'] ?? '' );
		$insert_data['country']        = sanitize_text_field( $data['address']['country'] ?? '' );
	}

	$result = $wpdb->insert( $table, $insert_data );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to create contact.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	$contact_id = $wpdb->insert_id;

	// Handle tags.
	if ( ! empty( $data['tags'] ) ) {
		foreach ( (array) $data['tags'] as $tag ) {
			$tag_id = is_numeric( $tag ) ? $tag : scrm_get_tag_id_by_slug( $tag );
			if ( $tag_id ) {
				scrm_assign_tag( $tag_id, $contact_id, 'contact' );
			}
		}
	}

	// Log activity.
	scrm_log_activity( 'contact', $contact_id, 'created', __( 'Contact created', 'syncpoint-crm' ) );

	/**
	 * Fires after a contact is saved.
	 *
	 * @since 1.0.0
	 * @param int   $contact_id Contact database ID.
	 * @param array $data       Contact data.
	 */
	do_action( 'scrm_after_contact_save', $contact_id, $data );

	/**
	 * Fires after a new contact is created.
	 *
	 * @since 1.0.0
	 * @param int   $contact_id Contact database ID.
	 * @param array $data       Contact data.
	 */
	do_action( 'scrm_contact_created', $contact_id, $data );

	// Clear cache.
	scrm_cache_delete_group( 'contacts' );

	return $contact_id;
}

/**
 * Update a contact.
 *
 * @since 1.0.0
 * @param int   $contact_id Contact ID.
 * @param array $data       Data to update.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function scrm_update_contact( $contact_id, $data ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	// Get existing contact.
	$existing = scrm_get_contact( $contact_id );
	if ( ! $existing ) {
		return new WP_Error( 'not_found', __( 'Contact not found.', 'syncpoint-crm' ) );
	}

	// Use database ID.
	$db_id = $existing->id;

	/**
	 * Filter contact data before saving.
	 *
	 * @since 1.0.0
	 * @param array $data Contact data.
	 */
	$data = apply_filters( 'scrm_contact_data_before_save', $data );

	// Build update data.
	$update_data = array();
	$allowed_fields = array(
		'type', 'status', 'first_name', 'last_name', 'email', 'phone',
		'company_id', 'currency', 'tax_id', 'address_line_1', 'address_line_2',
		'city', 'state', 'postal_code', 'country', 'custom_fields', 'source',
	);

	foreach ( $allowed_fields as $field ) {
		if ( isset( $data[ $field ] ) ) {
			if ( 'custom_fields' === $field ) {
				$update_data[ $field ] = wp_json_encode( $data[ $field ] );
			} elseif ( 'email' === $field ) {
				$update_data[ $field ] = sanitize_email( $data[ $field ] );
			} elseif ( 'company_id' === $field ) {
				$update_data[ $field ] = $data[ $field ] ? absint( $data[ $field ] ) : null;
			} else {
				$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
	}

	// Handle address array.
	if ( isset( $data['address'] ) && is_array( $data['address'] ) ) {
		$update_data['address_line_1'] = sanitize_text_field( $data['address']['line_1'] ?? '' );
		$update_data['address_line_2'] = sanitize_text_field( $data['address']['line_2'] ?? '' );
		$update_data['city']           = sanitize_text_field( $data['address']['city'] ?? '' );
		$update_data['state']          = sanitize_text_field( $data['address']['state'] ?? '' );
		$update_data['postal_code']    = sanitize_text_field( $data['address']['postal_code'] ?? '' );
		$update_data['country']        = sanitize_text_field( $data['address']['country'] ?? '' );
	}

	if ( empty( $update_data ) ) {
		return true; // Nothing to update.
	}

	$update_data['updated_at'] = current_time( 'mysql' );

	$result = $wpdb->update( $table, $update_data, array( 'id' => $db_id ) );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to update contact.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	// Handle tags.
	if ( isset( $data['tags'] ) ) {
		// Remove existing tags.
		scrm_remove_all_tags( $db_id, 'contact' );

		// Add new tags.
		foreach ( (array) $data['tags'] as $tag ) {
			$tag_id = is_numeric( $tag ) ? $tag : scrm_get_tag_id_by_slug( $tag );
			if ( $tag_id ) {
				scrm_assign_tag( $tag_id, $db_id, 'contact' );
			}
		}
	}

	// Log activity.
	scrm_log_activity( 'contact', $db_id, 'updated', __( 'Contact updated', 'syncpoint-crm' ) );

	// Convert existing to array for comparison.
	$old_data = (array) $existing;

	/**
	 * Fires after a contact is updated.
	 *
	 * @since 1.0.0
	 * @param int   $db_id    Contact database ID.
	 * @param array $data     New contact data.
	 * @param array $old_data Old contact data.
	 */
	do_action( 'scrm_contact_updated', $db_id, $data, $old_data );

	// Clear cache.
	scrm_cache_delete( 'contact_' . $db_id );
	scrm_cache_delete_group( 'contacts' );

	return true;
}

/**
 * Delete a contact.
 *
 * @since 1.0.0
 * @param int  $contact_id Contact ID.
 * @param bool $force      Force permanent deletion. Default false (archives).
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function scrm_delete_contact( $contact_id, $force = false ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	$contact = scrm_get_contact( $contact_id );
	if ( ! $contact ) {
		return new WP_Error( 'not_found', __( 'Contact not found.', 'syncpoint-crm' ) );
	}

	$db_id = $contact->id;

	if ( ! $force ) {
		// Soft delete - archive.
		return scrm_archive_contact( $db_id );
	}

	// Remove tags.
	scrm_remove_all_tags( $db_id, 'contact' );

	// Delete from database.
	$result = $wpdb->delete( $table, array( 'id' => $db_id ) );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to delete contact.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	/**
	 * Fires after a contact is deleted.
	 *
	 * @since 1.0.0
	 * @param int $db_id Contact database ID.
	 */
	do_action( 'scrm_contact_deleted', $db_id );

	// Clear cache.
	scrm_cache_delete( 'contact_' . $db_id );
	scrm_cache_delete_group( 'contacts' );

	return true;
}

/**
 * Archive a contact (soft delete).
 *
 * @since 1.0.0
 * @param int $contact_id Contact ID.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function scrm_archive_contact( $contact_id ) {
	return scrm_update_contact( $contact_id, array( 'status' => 'archived' ) );
}

/**
 * Get contacts with query parameters.
 *
 * @since 1.0.0
 * @param array $args Query arguments.
 * @return array Array of contact objects.
 */
function scrm_get_contacts( $args = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	$defaults = array(
		'type'           => '',
		'status'         => '',
		'company_id'     => '',
		'tag'            => '',
		'search'         => '',
		'created_after'  => '',
		'created_before' => '',
		'orderby'        => 'created_at',
		'order'          => 'DESC',
		'limit'          => 20,
		'offset'         => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$where = array( '1=1' );
	$values = array();

	if ( ! empty( $args['type'] ) ) {
		$where[] = 'type = %s';
		$values[] = $args['type'];
	}

	if ( ! empty( $args['status'] ) ) {
		$where[] = 'status = %s';
		$values[] = $args['status'];
	} else {
		// Exclude archived by default.
		$where[] = "status != 'archived'";
	}

	if ( ! empty( $args['company_id'] ) ) {
		$where[] = 'company_id = %d';
		$values[] = absint( $args['company_id'] );
	}

	if ( ! empty( $args['search'] ) ) {
		$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		$where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
		$values[] = $search;
		$values[] = $search;
		$values[] = $search;
	}

	if ( ! empty( $args['created_after'] ) ) {
		$where[] = 'created_at >= %s';
		$values[] = $args['created_after'];
	}

	if ( ! empty( $args['created_before'] ) ) {
		$where[] = 'created_at <= %s';
		$values[] = $args['created_before'];
	}

	// Validate orderby.
	$allowed_orderby = array( 'id', 'first_name', 'last_name', 'email', 'created_at', 'updated_at' );
	$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
	$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

	$where_clause = implode( ' AND ', $where );

	$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

	if ( $args['limit'] > 0 ) {
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
	}

	if ( ! empty( $values ) ) {
		$sql = $wpdb->prepare( $sql, $values );
	}

	$contacts = $wpdb->get_results( $sql );

	// Decode JSON fields.
	foreach ( $contacts as &$contact ) {
		$contact->custom_fields = json_decode( $contact->custom_fields, true ) ?: array();
	}

	// Filter by tag if specified (separate query due to join complexity).
	if ( ! empty( $args['tag'] ) ) {
		$tag_id = is_numeric( $args['tag'] ) ? $args['tag'] : scrm_get_tag_id_by_slug( $args['tag'] );
		if ( $tag_id ) {
			$tagged_ids = scrm_get_tagged_object_ids( $tag_id, 'contact' );
			$contacts = array_filter( $contacts, function( $c ) use ( $tagged_ids ) {
				return in_array( $c->id, $tagged_ids, true );
			} );
		}
	}

	return $contacts;
}

/**
 * Count contacts.
 *
 * @since 1.0.0
 * @param array $args Query arguments (same as scrm_get_contacts).
 * @return int Contact count.
 */
function scrm_count_contacts( $args = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_contacts';

	$where = array( '1=1' );
	$values = array();

	if ( ! empty( $args['type'] ) ) {
		$where[] = 'type = %s';
		$values[] = $args['type'];
	}

	if ( ! empty( $args['status'] ) ) {
		$where[] = 'status = %s';
		$values[] = $args['status'];
	} else {
		$where[] = "status != 'archived'";
	}

	$where_clause = implode( ' AND ', $where );
	$sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

	if ( ! empty( $values ) ) {
		$sql = $wpdb->prepare( $sql, $values );
	}

	return (int) $wpdb->get_var( $sql );
}

/*
|--------------------------------------------------------------------------
| Company Functions
|--------------------------------------------------------------------------
*/

/**
 * Get a company by ID.
 *
 * @since 1.0.0
 * @param int|string $company_id Database ID or custom company ID.
 * @return object|null Company object or null.
 */
function scrm_get_company( $company_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_companies';

	if ( ! is_numeric( $company_id ) ) {
		$company = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE company_id = %s", $company_id )
		);
	} else {
		$company = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $company_id )
		);
	}

	if ( ! $company ) {
		return null;
	}

	$company->custom_fields = json_decode( $company->custom_fields, true ) ?: array();

	return apply_filters( 'scrm_get_company', $company );
}

/**
 * Create a new company.
 *
 * @since 1.0.0
 * @param array $data Company data.
 * @return int|WP_Error Company ID on success, WP_Error on failure.
 */
function scrm_create_company( $data ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_companies';

	if ( empty( $data['name'] ) ) {
		return new WP_Error( 'missing_name', __( 'Company name is required.', 'syncpoint-crm' ) );
	}

	$defaults = array(
		'website'        => '',
		'email'          => '',
		'phone'          => '',
		'tax_id'         => '',
		'address_line_1' => '',
		'address_line_2' => '',
		'city'           => '',
		'state'          => '',
		'postal_code'    => '',
		'country'        => '',
		'industry'       => '',
		'custom_fields'  => array(),
	);

	$data = wp_parse_args( $data, $defaults );

	$custom_id = scrm_generate_id( 'company' );

	$insert_data = array(
		'company_id'     => $custom_id,
		'name'           => sanitize_text_field( $data['name'] ),
		'website'        => esc_url_raw( $data['website'] ),
		'email'          => sanitize_email( $data['email'] ),
		'phone'          => sanitize_text_field( $data['phone'] ),
		'tax_id'         => sanitize_text_field( $data['tax_id'] ),
		'address_line_1' => sanitize_text_field( $data['address_line_1'] ),
		'address_line_2' => sanitize_text_field( $data['address_line_2'] ),
		'city'           => sanitize_text_field( $data['city'] ),
		'state'          => sanitize_text_field( $data['state'] ),
		'postal_code'    => sanitize_text_field( $data['postal_code'] ),
		'country'        => sanitize_text_field( $data['country'] ),
		'industry'       => sanitize_text_field( $data['industry'] ),
		'custom_fields'  => wp_json_encode( $data['custom_fields'] ),
		'created_at'     => current_time( 'mysql' ),
		'updated_at'     => current_time( 'mysql' ),
		'created_by'     => get_current_user_id(),
	);

	if ( isset( $data['address'] ) && is_array( $data['address'] ) ) {
		$insert_data['address_line_1'] = sanitize_text_field( $data['address']['line_1'] ?? '' );
		$insert_data['address_line_2'] = sanitize_text_field( $data['address']['line_2'] ?? '' );
		$insert_data['city']           = sanitize_text_field( $data['address']['city'] ?? '' );
		$insert_data['state']          = sanitize_text_field( $data['address']['state'] ?? '' );
		$insert_data['postal_code']    = sanitize_text_field( $data['address']['postal_code'] ?? '' );
		$insert_data['country']        = sanitize_text_field( $data['address']['country'] ?? '' );
	}

	$result = $wpdb->insert( $table, $insert_data );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to create company.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	$company_id = $wpdb->insert_id;

	scrm_log_activity( 'company', $company_id, 'created', __( 'Company created', 'syncpoint-crm' ) );

	/**
	 * Fires after a company is created.
	 *
	 * @since 1.0.0
	 * @param int   $company_id Company database ID.
	 * @param array $data       Company data.
	 */
	do_action( 'scrm_company_created', $company_id, $data );

	return $company_id;
}

/**
 * Update a company.
 *
 * @since 1.0.0
 * @param int   $company_id Company ID.
 * @param array $data       Data to update.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function scrm_update_company( $company_id, $data ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_companies';

	$existing = scrm_get_company( $company_id );
	if ( ! $existing ) {
		return new WP_Error( 'not_found', __( 'Company not found.', 'syncpoint-crm' ) );
	}

	$db_id = $existing->id;

	$update_data = array();
	$allowed_fields = array(
		'name', 'website', 'email', 'phone', 'tax_id',
		'address_line_1', 'address_line_2', 'city', 'state',
		'postal_code', 'country', 'industry', 'custom_fields',
	);

	foreach ( $allowed_fields as $field ) {
		if ( isset( $data[ $field ] ) ) {
			if ( 'custom_fields' === $field ) {
				$update_data[ $field ] = wp_json_encode( $data[ $field ] );
			} elseif ( 'website' === $field ) {
				$update_data[ $field ] = esc_url_raw( $data[ $field ] );
			} elseif ( 'email' === $field ) {
				$update_data[ $field ] = sanitize_email( $data[ $field ] );
			} else {
				$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
	}

	if ( isset( $data['address'] ) && is_array( $data['address'] ) ) {
		$update_data['address_line_1'] = sanitize_text_field( $data['address']['line_1'] ?? '' );
		$update_data['address_line_2'] = sanitize_text_field( $data['address']['line_2'] ?? '' );
		$update_data['city']           = sanitize_text_field( $data['address']['city'] ?? '' );
		$update_data['state']          = sanitize_text_field( $data['address']['state'] ?? '' );
		$update_data['postal_code']    = sanitize_text_field( $data['address']['postal_code'] ?? '' );
		$update_data['country']        = sanitize_text_field( $data['address']['country'] ?? '' );
	}

	if ( empty( $update_data ) ) {
		return true;
	}

	$update_data['updated_at'] = current_time( 'mysql' );

	$result = $wpdb->update( $table, $update_data, array( 'id' => $db_id ) );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to update company.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	scrm_log_activity( 'company', $db_id, 'updated', __( 'Company updated', 'syncpoint-crm' ) );

	do_action( 'scrm_company_updated', $db_id, $data );

	return true;
}

/**
 * Delete a company.
 *
 * @since 1.0.0
 * @param int $company_id Company ID.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function scrm_delete_company( $company_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_companies';

	$company = scrm_get_company( $company_id );
	if ( ! $company ) {
		return new WP_Error( 'not_found', __( 'Company not found.', 'syncpoint-crm' ) );
	}

	$db_id = $company->id;

	// Remove company from contacts.
	$wpdb->update(
		$wpdb->prefix . 'scrm_contacts',
		array( 'company_id' => null ),
		array( 'company_id' => $db_id )
	);

	// Remove tags.
	scrm_remove_all_tags( $db_id, 'company' );

	$result = $wpdb->delete( $table, array( 'id' => $db_id ) );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to delete company.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	do_action( 'scrm_company_deleted', $db_id );

	return true;
}

/**
 * Get companies.
 *
 * @since 1.0.0
 * @param array $args Query arguments.
 * @return array Array of company objects.
 */
function scrm_get_companies( $args = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_companies';

	$defaults = array(
		'search'   => '',
		'industry' => '',
		'orderby'  => 'name',
		'order'    => 'ASC',
		'limit'    => 20,
		'offset'   => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$where = array( '1=1' );
	$values = array();

	if ( ! empty( $args['search'] ) ) {
		$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		$where[] = 'name LIKE %s';
		$values[] = $search;
	}

	if ( ! empty( $args['industry'] ) ) {
		$where[] = 'industry = %s';
		$values[] = $args['industry'];
	}

	$allowed_orderby = array( 'id', 'name', 'created_at' );
	$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
	$order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

	$where_clause = implode( ' AND ', $where );
	$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

	if ( $args['limit'] > 0 ) {
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
	}

	if ( ! empty( $values ) ) {
		$sql = $wpdb->prepare( $sql, $values );
	}

	$companies = $wpdb->get_results( $sql );

	foreach ( $companies as &$company ) {
		$company->custom_fields = json_decode( $company->custom_fields, true ) ?: array();
	}

	return $companies;
}

/**
 * Get contacts belonging to a company.
 *
 * @since 1.0.0
 * @param int   $company_id Company ID.
 * @param array $args       Additional query arguments.
 * @return array Array of contact objects.
 */
function scrm_get_company_contacts( $company_id, $args = array() ) {
	$company = scrm_get_company( $company_id );
	if ( ! $company ) {
		return array();
	}

	$args['company_id'] = $company->id;
	return scrm_get_contacts( $args );
}

/*
|--------------------------------------------------------------------------
| Transaction Functions
|--------------------------------------------------------------------------
*/

/**
 * Get a transaction by ID.
 *
 * @since 1.0.0
 * @param int $transaction_id Transaction ID.
 * @return object|null Transaction object or null.
 */
function scrm_get_transaction( $transaction_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_transactions';

	$transaction = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $transaction_id )
	);

	if ( ! $transaction ) {
		return null;
	}

	$transaction->metadata = json_decode( $transaction->metadata, true ) ?: array();

	return apply_filters( 'scrm_get_transaction', $transaction );
}

/**
 * Create a transaction.
 *
 * @since 1.0.0
 * @param array $data Transaction data.
 * @return int|WP_Error Transaction ID on success, WP_Error on failure.
 */
function scrm_create_transaction( $data ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_transactions';

	// Validate required fields.
	if ( empty( $data['contact_id'] ) ) {
		return new WP_Error( 'missing_contact', __( 'Contact ID is required.', 'syncpoint-crm' ) );
	}

	if ( empty( $data['amount'] ) ) {
		return new WP_Error( 'missing_amount', __( 'Amount is required.', 'syncpoint-crm' ) );
	}

	$defaults = array(
		'transaction_id'         => '',
		'invoice_id'             => null,
		'type'                   => 'payment',
		'gateway'                => 'manual',
		'gateway_transaction_id' => '',
		'currency'               => scrm_get_default_currency(),
		'status'                 => 'completed',
		'description'            => '',
		'metadata'               => array(),
	);

	$data = wp_parse_args( $data, $defaults );

	// Generate transaction ID if not provided.
	if ( empty( $data['transaction_id'] ) ) {
		$data['transaction_id'] = scrm_generate_id( 'transaction' );
	}

	$insert_data = array(
		'transaction_id'         => sanitize_text_field( $data['transaction_id'] ),
		'contact_id'             => absint( $data['contact_id'] ),
		'invoice_id'             => $data['invoice_id'] ? absint( $data['invoice_id'] ) : null,
		'type'                   => sanitize_text_field( $data['type'] ),
		'gateway'                => sanitize_text_field( $data['gateway'] ),
		'gateway_transaction_id' => sanitize_text_field( $data['gateway_transaction_id'] ),
		'amount'                 => floatval( $data['amount'] ),
		'currency'               => sanitize_text_field( $data['currency'] ),
		'status'                 => sanitize_text_field( $data['status'] ),
		'description'            => sanitize_textarea_field( $data['description'] ),
		'metadata'               => wp_json_encode( $data['metadata'] ),
		'created_at'             => current_time( 'mysql' ),
		'updated_at'             => current_time( 'mysql' ),
	);

	$result = $wpdb->insert( $table, $insert_data );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to create transaction.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	$txn_id = $wpdb->insert_id;

	scrm_log_activity( 'transaction', $txn_id, 'created', sprintf(
		/* translators: %1$s: amount, %2$s: currency */
		__( 'Transaction recorded: %1$s %2$s', 'syncpoint-crm' ),
		$data['amount'],
		$data['currency']
	) );

	/**
	 * Fires after a transaction is created.
	 *
	 * @since 1.0.0
	 * @param int   $txn_id Transaction database ID.
	 * @param array $data   Transaction data.
	 */
	do_action( 'scrm_transaction_created', $txn_id, $data );

	// For payments, also fire payment received action.
	if ( 'payment' === $data['type'] && 'completed' === $data['status'] ) {
		/**
		 * Fires when a payment is received.
		 *
		 * @since 1.0.0
		 * @param int    $txn_id     Transaction ID.
		 * @param int    $contact_id Contact ID.
		 * @param float  $amount     Amount.
		 * @param string $currency   Currency code.
		 */
		do_action( 'scrm_payment_received', $txn_id, $data['contact_id'], $data['amount'], $data['currency'] );
	}

	return $txn_id;
}

/**
 * Get transactions for a contact.
 *
 * @since 1.0.0
 * @param int   $contact_id Contact ID.
 * @param array $args       Query arguments.
 * @return array Array of transaction objects.
 */
function scrm_get_contact_transactions( $contact_id, $args = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_transactions';

	$contact = scrm_get_contact( $contact_id );
	if ( ! $contact ) {
		return array();
	}

	$defaults = array(
		'type'    => '',
		'gateway' => '',
		'status'  => '',
		'orderby' => 'created_at',
		'order'   => 'DESC',
		'limit'   => 50,
		'offset'  => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$where = array( 'contact_id = %d' );
	$values = array( $contact->id );

	if ( ! empty( $args['type'] ) ) {
		$where[] = 'type = %s';
		$values[] = $args['type'];
	}

	if ( ! empty( $args['gateway'] ) ) {
		$where[] = 'gateway = %s';
		$values[] = $args['gateway'];
	}

	if ( ! empty( $args['status'] ) ) {
		$where[] = 'status = %s';
		$values[] = $args['status'];
	}

	$where_clause = implode( ' AND ', $where );
	$orderby = $args['orderby'];
	$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

	$sql = $wpdb->prepare(
		"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
		array_merge( $values, array( $args['limit'], $args['offset'] ) )
	);

	$transactions = $wpdb->get_results( $sql );

	foreach ( $transactions as &$txn ) {
		$txn->metadata = json_decode( $txn->metadata, true ) ?: array();
	}

	return $transactions;
}

/**
 * Get contact lifetime value.
 *
 * @since 1.0.0
 * @param int    $contact_id Contact ID.
 * @param string $currency   Currency to calculate in.
 * @return float Lifetime value.
 */
function scrm_get_contact_ltv( $contact_id, $currency = '' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_transactions';

	$contact = scrm_get_contact( $contact_id );
	if ( ! $contact ) {
		return 0.0;
	}

	if ( empty( $currency ) ) {
		$currency = $contact->currency ?: scrm_get_default_currency();
	}

	$sum = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(amount) FROM {$table} WHERE contact_id = %d AND type = 'payment' AND status = 'completed' AND currency = %s",
		$contact->id,
		$currency
	) );

	return floatval( $sum ) ?: 0.0;
}

/*
|--------------------------------------------------------------------------
| Tag Functions
|--------------------------------------------------------------------------
*/

/**
 * Get a tag by ID.
 *
 * @since 1.0.0
 * @param int $tag_id Tag ID.
 * @return object|null Tag object or null.
 */
function scrm_get_tag( $tag_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tags';

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $tag_id )
	);
}

/**
 * Get a tag by slug.
 *
 * @since 1.0.0
 * @param string $slug Tag slug.
 * @return object|null Tag object or null.
 */
function scrm_get_tag_by_slug( $slug ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tags';

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", sanitize_title( $slug ) )
	);
}

/**
 * Get tag ID by slug.
 *
 * @since 1.0.0
 * @param string $slug Tag slug.
 * @return int|null Tag ID or null.
 */
function scrm_get_tag_id_by_slug( $slug ) {
	$tag = scrm_get_tag_by_slug( $slug );
	return $tag ? $tag->id : null;
}

/**
 * Get a tag by name.
 *
 * @since 1.0.0
 * @param string $name Tag name.
 * @return object|null Tag object or null.
 */
function scrm_get_tag_by_name( $name ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tags';

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table} WHERE name = %s", sanitize_text_field( $name ) )
	);
}

/**
 * Create a tag.
 *
 * @since 1.0.0
 * @param array $data Tag data.
 * @return int|WP_Error Tag ID on success, WP_Error on failure.
 */
function scrm_create_tag( $data ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tags';

	if ( empty( $data['name'] ) ) {
		return new WP_Error( 'missing_name', __( 'Tag name is required.', 'syncpoint-crm' ) );
	}

	$slug = sanitize_title( $data['name'] );

	// Check for duplicate.
	if ( scrm_get_tag_by_slug( $slug ) ) {
		return new WP_Error( 'duplicate_tag', __( 'A tag with this name already exists.', 'syncpoint-crm' ) );
	}

	$insert_data = array(
		'name'        => sanitize_text_field( $data['name'] ),
		'slug'        => $slug,
		'color'       => sanitize_hex_color( $data['color'] ?? '#6B7280' ),
		'description' => sanitize_textarea_field( $data['description'] ?? '' ),
		'created_at'  => current_time( 'mysql' ),
	);

	$result = $wpdb->insert( $table, $insert_data );

	if ( false === $result ) {
		return new WP_Error( 'db_error', __( 'Failed to create tag.', 'syncpoint-crm' ), $wpdb->last_error );
	}

	$tag_id = $wpdb->insert_id;

	do_action( 'scrm_tag_created', $tag_id, $data );

	return $tag_id;
}

/**
 * Assign a tag to an object.
 *
 * @since 1.0.0
 * @param int    $tag_id      Tag ID.
 * @param int    $object_id   Object ID.
 * @param string $object_type Object type (contact, company, transaction, invoice).
 * @return bool True on success.
 */
function scrm_assign_tag( $tag_id, $object_id, $object_type ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tag_relationships';

	// Check if already assigned.
	$exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE tag_id = %d AND object_id = %d AND object_type = %s",
		$tag_id,
		$object_id,
		$object_type
	) );

	if ( $exists ) {
		return true;
	}

	$result = $wpdb->insert( $table, array(
		'tag_id'      => absint( $tag_id ),
		'object_id'   => absint( $object_id ),
		'object_type' => sanitize_text_field( $object_type ),
	) );

	if ( $result ) {
		/**
		 * Fires after a tag is assigned.
		 *
		 * @since 1.0.0
		 * @param int    $tag_id      Tag ID.
		 * @param int    $object_id   Object ID.
		 * @param string $object_type Object type.
		 */
		do_action( 'scrm_tag_assigned', $tag_id, $object_id, $object_type );
	}

	return (bool) $result;
}

/**
 * Remove a tag from an object.
 *
 * @since 1.0.0
 * @param int    $tag_id      Tag ID.
 * @param int    $object_id   Object ID.
 * @param string $object_type Object type.
 * @return bool True on success.
 */
function scrm_remove_tag( $tag_id, $object_id, $object_type ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tag_relationships';

	$result = $wpdb->delete( $table, array(
		'tag_id'      => absint( $tag_id ),
		'object_id'   => absint( $object_id ),
		'object_type' => sanitize_text_field( $object_type ),
	) );

	if ( $result ) {
		do_action( 'scrm_tag_removed', $tag_id, $object_id, $object_type );
	}

	return (bool) $result;
}

/**
 * Remove all tags from an object.
 *
 * @since 1.0.0
 * @param int    $object_id   Object ID.
 * @param string $object_type Object type.
 * @return bool True on success.
 */
function scrm_remove_all_tags( $object_id, $object_type ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tag_relationships';

	return (bool) $wpdb->delete( $table, array(
		'object_id'   => absint( $object_id ),
		'object_type' => sanitize_text_field( $object_type ),
	) );
}

/**
 * Get tags for an object.
 *
 * @since 1.0.0
 * @param int    $object_id   Object ID.
 * @param string $object_type Object type.
 * @return array Array of tag objects.
 */
function scrm_get_object_tags( $object_id, $object_type ) {
	global $wpdb;
	$tags_table = $wpdb->prefix . 'scrm_tags';
	$rel_table = $wpdb->prefix . 'scrm_tag_relationships';

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT t.* FROM {$tags_table} t
		INNER JOIN {$rel_table} r ON t.id = r.tag_id
		WHERE r.object_id = %d AND r.object_type = %s
		ORDER BY t.name ASC",
		$object_id,
		$object_type
	) );
}

/**
 * Check if an object has a specific tag.
 *
 * @since 1.0.0
 * @param int       $object_id   Object ID.
 * @param string    $object_type Object type.
 * @param int|string $tag        Tag ID or slug.
 * @return bool True if object has the tag.
 */
function scrm_object_has_tag( $object_id, $object_type, $tag ) {
	$tag_id = is_numeric( $tag ) ? $tag : scrm_get_tag_id_by_slug( $tag );

	if ( ! $tag_id ) {
		return false;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tag_relationships';

	$exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE tag_id = %d AND object_id = %d AND object_type = %s",
		$tag_id,
		$object_id,
		$object_type
	) );

	return (bool) $exists;
}

/**
 * Get object IDs with a specific tag.
 *
 * @since 1.0.0
 * @param int    $tag_id      Tag ID.
 * @param string $object_type Object type.
 * @return array Array of object IDs.
 */
function scrm_get_tagged_object_ids( $tag_id, $object_type ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_tag_relationships';

	return $wpdb->get_col( $wpdb->prepare(
		"SELECT object_id FROM {$table} WHERE tag_id = %d AND object_type = %s",
		$tag_id,
		$object_type
	) );
}

/*
|--------------------------------------------------------------------------
| Activity Log Functions
|--------------------------------------------------------------------------
*/

/**
 * Log an activity.
 *
 * @since 1.0.0
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @param string $action      Action performed.
 * @param string $description Description of the activity.
 * @param array  $metadata    Additional metadata.
 * @return int|false Log ID on success, false on failure.
 */
function scrm_log_activity( $object_type, $object_id, $action, $description = '', $metadata = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_activity_log';

	$result = $wpdb->insert( $table, array(
		'object_type' => sanitize_text_field( $object_type ),
		'object_id'   => absint( $object_id ),
		'action'      => sanitize_text_field( $action ),
		'description' => sanitize_textarea_field( $description ),
		'metadata'    => wp_json_encode( $metadata ),
		'user_id'     => get_current_user_id(),
		'ip_address'  => scrm_get_client_ip(),
		'created_at'  => current_time( 'mysql' ),
	) );

	if ( $result ) {
		$log_id = $wpdb->insert_id;
		do_action( 'scrm_activity_logged', $log_id, $action, $object_id, $object_type );
		return $log_id;
	}

	return false;
}

/**
 * Get activity log for an object.
 *
 * @since 1.0.0
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @param int    $limit       Number of entries to return.
 * @return array Array of activity log entries.
 */
function scrm_get_activity_log( $object_type, $object_id, $limit = 20 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'scrm_activity_log';

	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table} WHERE object_type = %s AND object_id = %d ORDER BY created_at DESC LIMIT %d",
		$object_type,
		$object_id,
		$limit
	) );

	foreach ( $results as &$entry ) {
		$entry->metadata = json_decode( $entry->metadata, true ) ?: array();
	}

	return $results;
}
