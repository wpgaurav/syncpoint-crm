<?php
/**
 * Contacts List Table
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class SCRM_Contacts_List_Table
 *
 * @since 1.0.0
 */
class SCRM_Contacts_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'contact',
				'plural'   => 'contacts',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns.
	 *
	 * @return array Columns.
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox">',
			'name'       => __( 'Name', 'syncpoint-crm' ),
			'email'      => __( 'Email', 'syncpoint-crm' ),
			'type'       => __( 'Type', 'syncpoint-crm' ),
			'company'    => __( 'Company', 'syncpoint-crm' ),
			'tags'       => __( 'Tags', 'syncpoint-crm' ),
			'ltv'        => __( 'LTV', 'syncpoint-crm' ),
			'created_at' => __( 'Created', 'syncpoint-crm' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		return array(
			'name'       => array( 'first_name', false ),
			'email'      => array( 'email', false ),
			'type'       => array( 'type', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array Bulk actions.
	 */
	public function get_bulk_actions() {
		return array(
			'email'   => __( 'Send Email', 'syncpoint-crm' ),
			'archive' => __( 'Archive', 'syncpoint-crm' ),
			'delete'  => __( 'Delete Permanently', 'syncpoint-crm' ),
			'export'  => __( 'Export to CSV', 'syncpoint-crm' ),
		);
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// Query args.
		$args = array(
			'limit'   => $per_page,
			'offset'  => ( $current_page - 1 ) * $per_page,
			'orderby' => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
			'order'   => isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC',
		);

		// Filters.
		if ( ! empty( $_GET['type'] ) ) {
			$args['type'] = sanitize_text_field( wp_unslash( $_GET['type'] ) );
		}

		if ( ! empty( $_GET['status'] ) ) {
			$args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		} else {
			$args['status'] = array( 'active', 'inactive' ); // Exclude archived by default.
		}

		if ( ! empty( $_GET['s'] ) ) {
			$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		if ( ! empty( $_GET['tag'] ) ) {
			$args['tag'] = absint( $_GET['tag'] );
		}

		// Get items.
		$this->items = scrm_get_contacts( $args );

		// Count for pagination.
		$total_items = scrm_count_contacts(
			array(
				'type'   => $args['type'] ?? '',
				'status' => $args['status'] ?? '',
				'search' => $args['search'] ?? '',
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Display when no items are found.
	 */
	public function no_items() {
		esc_html_e( 'No contacts found.', 'syncpoint-crm' );
	}

	/**
	 * Checkbox column.
	 *
	 * @param object $item Item.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="contact[]" value="%d">', $item->id );
	}

	/**
	 * Name column.
	 *
	 * @param object $item Item.
	 * @return string Name HTML.
	 */
	public function column_name( $item ) {
		$name = trim( $item->first_name . ' ' . $item->last_name );
		if ( empty( $name ) ) {
			$name = $item->email;
		}

		$edit_url = admin_url( 'admin.php?page=scrm-contacts&action=edit&id=' . $item->id );
		$view_url = admin_url( 'admin.php?page=scrm-contacts&action=view&id=' . $item->id );

		$actions = array(
			'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'syncpoint-crm' ) ),
			'view' => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'syncpoint-crm' ) ),
		);

		if ( 'archived' === $item->status ) {
			$restore_url        = wp_nonce_url(
				admin_url( 'admin.php?page=scrm-contacts&action=restore&id=' . $item->id ),
				'restore_contact_' . $item->id
			);
			$actions['restore'] = sprintf( '<a href="%s">%s</a>', esc_url( $restore_url ), esc_html__( 'Restore', 'syncpoint-crm' ) );
		} else {
			$archive_url        = wp_nonce_url(
				admin_url( 'admin.php?page=scrm-contacts&action=archive&id=' . $item->id ),
				'archive_contact_' . $item->id
			);
			$actions['archive'] = sprintf( '<a href="%s">%s</a>', esc_url( $archive_url ), esc_html__( 'Archive', 'syncpoint-crm' ) );
		}

		$status_indicator = '';
		if ( 'active' === $item->status ) {
			$status_indicator = '<span class="scrm-status scrm-status--active"></span>';
		} elseif ( 'archived' === $item->status ) {
			$status_indicator = '<span class="scrm-status scrm-status--archived"></span>';
		}

		return sprintf(
			'%s<strong><a href="%s" class="row-title">%s</a></strong><br><small class="scrm-contact-id">%s</small>%s',
			$status_indicator,
			esc_url( $edit_url ),
			esc_html( $name ),
			esc_html( $item->contact_id ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Email column.
	 *
	 * @param object $item Item.
	 * @return string Email HTML.
	 */
	public function column_email( $item ) {
		return sprintf(
			'<a href="mailto:%1$s">%1$s</a>%2$s',
			esc_html( $item->email ),
			! empty( $item->phone ) ? '<br><small>' . esc_html( $item->phone ) . '</small>' : ''
		);
	}

	/**
	 * Type column.
	 *
	 * @param object $item Item.
	 * @return string Type badge HTML.
	 */
	public function column_type( $item ) {
		$types = scrm_get_contact_types();
		$label = $types[ $item->type ] ?? $item->type;

		return sprintf(
			'<span class="scrm-badge scrm-badge--%s">%s</span>',
			esc_attr( $item->type ),
			esc_html( $label )
		);
	}

	/**
	 * Company column.
	 *
	 * @param object $item Item.
	 * @return string Company HTML.
	 */
	public function column_company( $item ) {
		if ( empty( $item->company_id ) ) {
			return '—';
		}

		$company = scrm_get_company( $item->company_id );
		if ( ! $company ) {
			return '—';
		}

		$url = admin_url( 'admin.php?page=scrm-companies&action=view&id=' . $company->id );

		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $company->name ) );
	}

	/**
	 * Tags column.
	 *
	 * @param object $item Item.
	 * @return string Tags HTML.
	 */
	public function column_tags( $item ) {
		$tags = scrm_get_object_tags( $item->id, 'contact' );

		if ( empty( $tags ) ) {
			return '—';
		}

		$output = array();
		foreach ( $tags as $tag ) {
			$output[] = sprintf(
				'<span class="scrm-tag" style="background-color: %s;">%s</span>',
				esc_attr( $tag->color ),
				esc_html( $tag->name )
			);
		}

		return implode( ' ', $output );
	}

	/**
	 * LTV column.
	 *
	 * @param object $item Item.
	 * @return string LTV HTML.
	 */
	public function column_ltv( $item ) {
		$ltv = scrm_get_contact_ltv( $item->id, $item->currency );

		if ( $ltv <= 0 ) {
			return '—';
		}

		return esc_html( scrm_format_currency( $ltv, $item->currency ) );
	}

	/**
	 * Created at column.
	 *
	 * @param object $item Item.
	 * @return string Created date HTML.
	 */
	public function column_created_at( $item ) {
		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( $item->created_at ),
			esc_html( scrm_format_gmdate( $item->created_at ) )
		);
	}

	/**
	 * Default column handler.
	 *
	 * @param object $item        Item.
	 * @param string $column_name Column name.
	 * @return string Column content.
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	/**
	 * Extra table nav (filters).
	 *
	 * @param string $which Top or bottom.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current_type   = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

		?>
		<div class="alignleft actions">
			<select name="type">
				<option value=""><?php esc_html_e( 'All Types', 'syncpoint-crm' ); ?></option>
				<?php foreach ( scrm_get_contact_types() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_type, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'syncpoint-crm' ); ?></option>
				<?php foreach ( scrm_get_contact_statuses() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( __( 'Filter', 'syncpoint-crm' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
