<?php
/**
 * Contact Model
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Contact
 *
 * @since 1.0.0
 */
class Contact {

	/**
	 * Contact ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Custom contact ID.
	 *
	 * @var string
	 */
	public $contact_id = '';

	/**
	 * Contact type.
	 *
	 * @var string
	 */
	public $type = 'customer';

	/**
	 * Contact status.
	 *
	 * @var string
	 */
	public $status = 'active';

	/**
	 * First name.
	 *
	 * @var string
	 */
	public $first_name = '';

	/**
	 * Last name.
	 *
	 * @var string
	 */
	public $last_name = '';

	/**
	 * Email address.
	 *
	 * @var string
	 */
	public $email = '';

	/**
	 * Phone number.
	 *
	 * @var string
	 */
	public $phone = '';

	/**
	 * Company ID.
	 *
	 * @var int|null
	 */
	public $company_id = null;

	/**
	 * Currency.
	 *
	 * @var string
	 */
	public $currency = 'USD';

	/**
	 * Tax ID.
	 *
	 * @var string
	 */
	public $tax_id = '';

	/**
	 * Address line 1.
	 *
	 * @var string
	 */
	public $address_line_1 = '';

	/**
	 * Address line 2.
	 *
	 * @var string
	 */
	public $address_line_2 = '';

	/**
	 * City.
	 *
	 * @var string
	 */
	public $city = '';

	/**
	 * State/Province.
	 *
	 * @var string
	 */
	public $state = '';

	/**
	 * Postal code.
	 *
	 * @var string
	 */
	public $postal_code = '';

	/**
	 * Country code.
	 *
	 * @var string
	 */
	public $country = '';

	/**
	 * Custom fields.
	 *
	 * @var array
	 */
	public $custom_fields = array();

	/**
	 * Source.
	 *
	 * @var string
	 */
	public $source = '';

	/**
	 * Created at.
	 *
	 * @var string
	 */
	public $created_at = '';

	/**
	 * Updated at.
	 *
	 * @var string
	 */
	public $updated_at = '';

	/**
	 * Created by user ID.
	 *
	 * @var int
	 */
	public $created_by = 0;

	/**
	 * Constructor.
	 *
	 * @param int|object $contact Contact ID or object.
	 */
	public function __construct( $contact = 0 ) {
		if ( is_numeric( $contact ) && $contact > 0 ) {
			$this->read( $contact );
		} elseif ( is_object( $contact ) ) {
			$this->set_props( $contact );
		}
	}

	/**
	 * Read contact from database.
	 *
	 * @param int $id Contact ID.
	 */
	public function read( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_contacts';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);

		if ( $row ) {
			$this->set_props( $row );
		}
	}

	/**
	 * Set properties from object.
	 *
	 * @param object $data Data object.
	 */
	public function set_props( $data ) {
		$this->id             = (int) $data->id;
		$this->contact_id     = $data->contact_id;
		$this->type           = $data->type;
		$this->status         = $data->status;
		$this->first_name     = $data->first_name;
		$this->last_name      = $data->last_name;
		$this->email          = $data->email;
		$this->phone          = $data->phone;
		$this->company_id     = $data->company_id ? (int) $data->company_id : null;
		$this->currency       = $data->currency;
		$this->tax_id         = $data->tax_id;
		$this->address_line_1 = $data->address_line_1;
		$this->address_line_2 = $data->address_line_2;
		$this->city           = $data->city;
		$this->state          = $data->state;
		$this->postal_code    = $data->postal_code;
		$this->country        = $data->country;
		$this->custom_fields  = is_string( $data->custom_fields ) ? json_decode( $data->custom_fields, true ) : $data->custom_fields;
		$this->source         = $data->source;
		$this->created_at     = $data->created_at;
		$this->updated_at     = $data->updated_at;
		$this->created_by     = (int) $data->created_by;

		if ( ! is_array( $this->custom_fields ) ) {
			$this->custom_fields = array();
		}
	}

	/**
	 * Save contact.
	 *
	 * @return int|WP_Error Contact ID or error.
	 */
	public function save() {
		if ( $this->id ) {
			return $this->upgmdate();
		}
		return $this->create();
	}

	/**
	 * Create new contact.
	 *
	 * @return int|\WP_Error Contact ID or error.
	 */
	public function create() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_contacts';

		if ( empty( $this->email ) ) {
			return new \WP_Error( 'missing_email', __( 'Email is required.', 'syncpoint-crm' ) );
		}

		if ( ! is_email( $this->email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Invalid email address.', 'syncpoint-crm' ) );
		}

		// Generate custom ID.
		if ( empty( $this->contact_id ) ) {
			$this->contact_id = scrm_generate_id( 'contact', $this->type );
		}

		if ( empty( $this->currency ) ) {
			$this->currency = scrm_get_default_currency();
		}

		$data = array(
			'contact_id'     => $this->contact_id,
			'type'           => $this->type,
			'status'         => $this->status,
			'first_name'     => $this->first_name,
			'last_name'      => $this->last_name,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'company_id'     => $this->company_id,
			'currency'       => $this->currency,
			'tax_id'         => $this->tax_id,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city'           => $this->city,
			'state'          => $this->state,
			'postal_code'    => $this->postal_code,
			'country'        => $this->country,
			'custom_fields'  => wp_json_encode( $this->custom_fields ),
			'source'         => $this->source,
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
			'created_by'     => get_current_user_id(),
		);

		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		$this->id         = $wpdb->insert_id;
		$this->created_at = $data['created_at'];
		$this->updated_at = $data['updated_at'];

		do_action( 'scrm_contact_created', $this->id, $this->to_array() );

		return $this->id;
	}

	/**
	 * Update contact.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function upgmdate() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_contacts';

		$data = array(
			'type'           => $this->type,
			'status'         => $this->status,
			'first_name'     => $this->first_name,
			'last_name'      => $this->last_name,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'company_id'     => $this->company_id,
			'currency'       => $this->currency,
			'tax_id'         => $this->tax_id,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city'           => $this->city,
			'state'          => $this->state,
			'postal_code'    => $this->postal_code,
			'country'        => $this->country,
			'custom_fields'  => wp_json_encode( $this->custom_fields ),
			'source'         => $this->source,
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->upgmdate( $table, $data, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		$this->updated_at = $data['updated_at'];

		do_action( 'scrm_contact_updated', $this->id, $this->to_array(), array() );

		return true;
	}

	/**
	 * Delete contact.
	 *
	 * @param bool $force Force permanent deletion.
	 * @return bool|\WP_Error True or error.
	 */
	public function delete( $force = false ) {
		if ( ! $force ) {
			$this->status = 'archived';
			return $this->upgmdate();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'scrm_contacts';

		// Remove tags.
		scrm_remove_all_tags( $this->id, 'contact' );

		$result = $wpdb->delete( $table, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		do_action( 'scrm_contact_deleted', $this->id );

		return true;
	}

	/**
	 * Get full name.
	 *
	 * @return string Full name.
	 */
	public function get_full_name() {
		$name = trim( $this->first_name . ' ' . $this->last_name );
		return $name ?: $this->email;
	}

	/**
	 * Get display name.
	 *
	 * @return string Display name.
	 */
	public function get_display_name() {
		return $this->get_full_name();
	}

	/**
	 * Get company.
	 *
	 * @return Company|null Company object or null.
	 */
	public function get_company() {
		if ( ! $this->company_id ) {
			return null;
		}
		return new Company( $this->company_id );
	}

	/**
	 * Get tags.
	 *
	 * @return array Array of tag objects.
	 */
	public function get_tags() {
		return scrm_get_object_tags( $this->id, 'contact' );
	}

	/**
	 * Get transactions.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of transactions.
	 */
	public function get_transactions( $args = array() ) {
		return scrm_get_contact_transactions( $this->id, $args );
	}

	/**
	 * Get lifetime value.
	 *
	 * @param string $currency Currency code.
	 * @return float Lifetime value.
	 */
	public function get_lifetime_value( $currency = '' ) {
		return scrm_get_contact_ltv( $this->id, $currency ?: $this->currency );
	}

	/**
	 * Get formatted address.
	 *
	 * @return string Formatted address.
	 */
	public function get_formatted_address() {
		$parts = array_filter(
			array(
				$this->address_line_1,
				$this->address_line_2,
				$this->city,
				$this->state,
				$this->postal_code,
				$this->country,
			)
		);

		return implode( ', ', $parts );
	}

	/**
	 * Convert to array.
	 *
	 * @return array Contact data.
	 */
	public function to_array() {
		return array(
			'id'             => $this->id,
			'contact_id'     => $this->contact_id,
			'type'           => $this->type,
			'status'         => $this->status,
			'first_name'     => $this->first_name,
			'last_name'      => $this->last_name,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'company_id'     => $this->company_id,
			'currency'       => $this->currency,
			'tax_id'         => $this->tax_id,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city'           => $this->city,
			'state'          => $this->state,
			'postal_code'    => $this->postal_code,
			'country'        => $this->country,
			'custom_fields'  => $this->custom_fields,
			'source'         => $this->source,
			'created_at'     => $this->created_at,
			'updated_at'     => $this->updated_at,
			'created_by'     => $this->created_by,
		);
	}

	/**
	 * Check if contact exists.
	 *
	 * @return bool True if exists.
	 */
	public function exists() {
		return $this->id > 0;
	}
}
