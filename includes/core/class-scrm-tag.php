<?php
/**
 * Tag Model
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tag
 *
 * @since 1.0.0
 */
class Tag {

	/**
	 * Tag ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Tag slug.
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Tag color.
	 *
	 * @var string
	 */
	public $color = '#6B7280';

	/**
	 * Tag description.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Created at.
	 *
	 * @var string
	 */
	public $created_at = '';

	/**
	 * Constructor.
	 *
	 * @param int|object $tag Tag ID or object.
	 */
	public function __construct( $tag = 0 ) {
		if ( is_numeric( $tag ) && $tag > 0 ) {
			$this->read( $tag );
		} elseif ( is_object( $tag ) ) {
			$this->set_props( $tag );
		}
	}

	/**
	 * Read tag from database.
	 *
	 * @param int $id Tag ID.
	 */
	public function read( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';

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
	 * Read tag by slug.
	 *
	 * @param string $slug Tag slug.
	 */
	public function read_by_slug( $slug ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE slug = %s",
				$slug
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
		$this->id          = (int) $data->id;
		$this->name        = $data->name;
		$this->slug        = $data->slug;
		$this->color       = $data->color;
		$this->description = $data->description;
		$this->created_at  = $data->created_at;
	}

	/**
	 * Save tag.
	 *
	 * @return int|\WP_Error Tag ID or error.
	 */
	public function save() {
		if ( $this->id ) {
			return $this->upgmdate();
		}
		return $this->create();
	}

	/**
	 * Create new tag.
	 *
	 * @return int|\WP_Error Tag ID or error.
	 */
	public function create() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';

		if ( empty( $this->name ) ) {
			return new \WP_Error( 'missing_name', __( 'Tag name is required.', 'syncpoint-crm' ) );
		}

		$this->slug = sanitize_title( $this->name );

		// Check for duplicate.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE slug = %s",
				$this->slug
			)
		);

		if ( $existing ) {
			return new \WP_Error( 'duplicate_tag', __( 'A tag with this name already exists.', 'syncpoint-crm' ) );
		}

		$data = array(
			'name'        => $this->name,
			'slug'        => $this->slug,
			'color'       => $this->color ?: '#6B7280',
			'description' => $this->description,
			'created_at'  => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		$this->id = $wpdb->insert_id;

		do_action( 'scrm_tag_created', $this->id, $this->to_array() );

		return $this->id;
	}

	/**
	 * Update tag.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function upgmdate() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';

		$data = array(
			'name'        => $this->name,
			'slug'        => sanitize_title( $this->name ),
			'color'       => $this->color,
			'description' => $this->description,
		);

		$result = $wpdb->upgmdate( $table, $data, array( 'id' => $this->id ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		return true;
	}

	/**
	 * Delete tag.
	 *
	 * @return bool|\WP_Error True or error.
	 */
	public function delete() {
		global $wpdb;

		// Remove all relationships.
		$wpdb->delete(
			$wpdb->prefix . 'scrm_tag_relationships',
			array( 'tag_id' => $this->id )
		);

		// Delete tag.
		$result = $wpdb->delete(
			$wpdb->prefix . 'scrm_tags',
			array( 'id' => $this->id )
		);

		if ( false === $result ) {
			return new \WP_Error( 'db_error', $wpdb->last_error );
		}

		return true;
	}

	/**
	 * Get object count for a type.
	 *
	 * @param string $object_type Object type.
	 * @return int Count.
	 */
	public function get_count( $object_type ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tag_relationships';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE tag_id = %d AND object_type = %s",
				$this->id,
				$object_type
			)
		);
	}

	/**
	 * Get all counts.
	 *
	 * @return array Counts by object type.
	 */
	public function get_all_counts() {
		return array(
			'contacts'     => $this->get_count( 'contact' ),
			'companies'    => $this->get_count( 'company' ),
			'transactions' => $this->get_count( 'transaction' ),
			'invoices'     => $this->get_count( 'invoice' ),
		);
	}

	/**
	 * Get objects with this tag.
	 *
	 * @param string $object_type Object type.
	 * @param int    $limit       Limit.
	 * @return array Array of object IDs.
	 */
	public function get_objects( $object_type, $limit = 100 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tag_relationships';

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT object_id FROM {$table} WHERE tag_id = %d AND object_type = %s LIMIT %d",
				$this->id,
				$object_type,
				$limit
			)
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array Tag data.
	 */
	public function to_array() {
		return array(
			'id'          => $this->id,
			'name'        => $this->name,
			'slug'        => $this->slug,
			'color'       => $this->color,
			'description' => $this->description,
			'created_at'  => $this->created_at,
		);
	}

	/**
	 * Check if tag exists.
	 *
	 * @return bool True if exists.
	 */
	public function exists() {
		return $this->id > 0;
	}
}
