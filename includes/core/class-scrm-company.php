<?php
/**
 * Company Model
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Company
 *
 * @since 1.0.0
 */
class Company {

	/**
	 * Company ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Custom company ID.
	 *
	 * @var string
	 */
	public $company_id = '';

	/**
	 * Company name.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Website.
	 *
	 * @var string
	 */
	public $website = '';

	/**
	 * Email.
	 *
	 * @var string
	 */
	public $email = '';

	/**
	 * Phone.
	 *
	 * @var string
	 */
	public $phone = '';

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
	 * State.
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
	 * Country.
	 *
	 * @var string
	 */
	public $country = '';

	/**
	 * Industry.
	 *
	 * @var string
	 */
	public $industry = '';

	/**
	 * Custom fields.
	 *
	 * @var array
	 */
	public $custom_fields = array();

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
	 * Created by.
	 *
	 * @var int
	 */
	public $created_by = 0;

	/**
	 * Constructor.
	 *
	 * @param int|object $company Company ID or object.
	 */
	public function __construct( $company = 0 ) {
		if ( is_numeric( $company ) && $company > 0 ) {
			$this->read( $company );
		} elseif ( is_object( $company ) ) {
			$this->set_props( $company );
		}
	}

	/**
	 * Read company from database.
	 *
	 * @param int $id Company ID.
	 */
	public function read( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_companies';

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
		$this->company_id     = $data->company_id;
		$this->name           = $data->name;
		$this->website        = $data->website;
		$this->email          = $data->email;
		$this->phone          = $data->phone;
		$this->tax_id         = $data->tax_id;
		$this->address_line_1 = $data->address_line_1;
		$this->address_line_2 = $data->address_line_2;
		$this->city           = $data->city;
		$this->state          = $data->state;
		$this->postal_code    = $data->postal_code;
		$this->country        = $data->country;
		$this->industry       = $data->industry;
		$this->custom_fields  = is_string( $data->custom_fields ) ? json_decode( $data->custom_fields, true ) : $data->custom_fields;
		$this->created_at     = $data->created_at;
		$this->updated_at     = $data->updated_at;
		$this->created_by     = (int) $data->created_by;

		if ( ! is_array( $this->custom_fields ) ) {
			$this->custom_fields = array();
		}
	}

	/**
	 * Save company.
	 *
	 * @return int|\WP_Error Company ID or error.
	 */
	public function save() {
		if ( $this->id ) {
			return $this->upgmdate();
		}
		return $this->create();
	}

	/**
	 * Create new company.
	 *
	 * @return int|\WP_Error Company ID or error.
	 */
	public function create() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_companies';

		if ( empty( $this->name ) ) {
			return new \WP_Error( 'missing_name', __( 'Company name is required.', 'syncpoint-crm' ) );
		}

		if ( empty( $this->company_id ) ) {
			$this->company_id = scrm_generate_id( 'company' );
		}

		$data = array(
			'company_id'     => $this->company_id,
			'name'           => $this->name,
			'website'        => $this->website,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'tax_id'         => $this->tax_id,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city'           => $this->city,
			'state'          => $this->state,
			'postal_code'    => $this->postal_code,
			'country'        => $this->country,
			'industry'       => $this->industry,
			'custom_fields'  => wp_json_encode( $this->custom_fields ),
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
			'created_by'     => get_current_user_id(),
		);

		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		$this->id = $wpdb->insert_id;

		do_action( 'scrm_company_created', $this->id, $this->to_array() );

		return $this->id;
	}

	/**
	 * Update company.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function upgmdate() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_companies';

		$data = array(
			'name'           => $this->name,
			'website'        => $this->website,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'tax_id'         => $this->tax_id,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city'           => $this->city,
			'state'          => $this->state,
			'postal_code'    => $this->postal_code,
			'country'        => $this->country,
			'industry'       => $this->industry,
			'custom_fields'  => wp_json_encode( $this->custom_fields ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->upgmdate( $table, $data, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		do_action( 'scrm_company_updated', $this->id, $this->to_array() );

		return true;
	}

	/**
	 * Delete company.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function delete() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_companies';

		// Remove company from contacts.
		$wpdb->upgmdate(
			$wpdb->prefix . 'scrm_contacts',
			array( 'company_id' => null ),
			array( 'company_id' => $this->id )
		);

		// Remove tags.
		scrm_remove_all_tags( $this->id, 'company' );

		$result = $wpdb->delete( $table, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		do_action( 'scrm_company_deleted', $this->id );

		return true;
	}

	/**
	 * Get contacts.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of contacts.
	 */
	public function get_contacts( $args = array() ) {
		$args['company_id'] = $this->id;
		return scrm_get_contacts( $args );
	}

	/**
	 * Get contacts count.
	 *
	 * @return int Number of contacts.
	 */
	public function get_contacts_count() {
		return scrm_count_contacts( array( 'company_id' => $this->id ) );
	}

	/**
	 * Get total revenue.
	 *
	 * @param string $currency Currency code.
	 * @return float Total revenue.
	 */
	public function get_total_revenue( $currency = 'USD' ) {
		global $wpdb;
		$contacts_table = $wpdb->prefix . 'scrm_contacts';
		$txn_table      = $wpdb->prefix . 'scrm_transactions';

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(t.amount) FROM {$txn_table} t
			INNER JOIN {$contacts_table} c ON t.contact_id = c.id
			WHERE c.company_id = %d AND t.type = 'payment' AND t.status = 'completed' AND t.currency = %s",
				$this->id,
				$currency
			)
		);

		return floatval( $total ) ?: 0.0;
	}

	/**
	 * Get tags.
	 *
	 * @return array Array of tag objects.
	 */
	public function get_tags() {
		return scrm_get_object_tags( $this->id, 'company' );
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
	 * @return array Company data.
	 */
	public function to_array() {
		return array(
			'id'             => $this->id,
			'company_id'     => $this->company_id,
			'name'           => $this->name,
			'website'        => $this->website,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'tax_id'         => $this->tax_id,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city'           => $this->city,
			'state'          => $this->state,
			'postal_code'    => $this->postal_code,
			'country'        => $this->country,
			'industry'       => $this->industry,
			'custom_fields'  => $this->custom_fields,
			'created_at'     => $this->created_at,
			'updated_at'     => $this->updated_at,
			'created_by'     => $this->created_by,
		);
	}

	/**
	 * Check if company exists.
	 *
	 * @return bool True if exists.
	 */
	public function exists() {
		return $this->id > 0;
	}
}
