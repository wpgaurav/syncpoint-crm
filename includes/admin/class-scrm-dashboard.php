<?php
/**
 * Dashboard Statistics
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Dashboard
 *
 * Handles dashboard statistics calculations.
 *
 * @since 1.0.0
 */
class SCRM_Dashboard {

	/**
	 * Get dashboard statistics.
	 *
	 * @param string $period Period (7days, 30days, 90days, year).
	 * @return array Statistics data.
	 */
	public static function get_stats( $period = '30days' ) {
		$end_date = current_time( 'Y-m-d' );
		$start_date = self::get_start_date( $period );

		$stats = array(
			'period'       => $period,
			'start_date'   => $start_date,
			'end_date'     => $end_date,
			'contacts'     => self::get_contacts_stats( $start_date, $end_date ),
			'companies'    => self::get_companies_stats(),
			'transactions' => self::get_transactions_stats( $start_date, $end_date ),
			'invoices'     => self::get_invoices_stats(),
			'revenue'      => self::get_revenue_stats( $start_date, $end_date ),
		);

		/**
		 * Filter dashboard statistics.
		 *
		 * @since 1.0.0
		 * @param array  $stats  Statistics data.
		 * @param string $period Period.
		 */
		return apply_filters( 'scrm_dashboard_stats', $stats, $period );
	}

	/**
	 * Get start date for period.
	 *
	 * @param string $period Period.
	 * @return string Start date.
	 */
	private static function get_start_date( $period ) {
		switch ( $period ) {
			case '7days':
				return date( 'Y-m-d', strtotime( '-7 days' ) );
			case '30days':
				return date( 'Y-m-d', strtotime( '-30 days' ) );
			case '90days':
				return date( 'Y-m-d', strtotime( '-90 days' ) );
			case 'year':
				return date( 'Y-m-d', strtotime( '-1 year' ) );
			default:
				return date( 'Y-m-d', strtotime( '-30 days' ) );
		}
	}

	/**
	 * Get contacts statistics.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array Contacts stats.
	 */
	private static function get_contacts_stats( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_contacts';

		$total_active = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE status = 'active'"
		);

		$new_in_period = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND created_at <= %s",
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );

		$by_type = array();
		$types = $wpdb->get_results(
			"SELECT type, COUNT(*) as count FROM {$table} WHERE status != 'archived' GROUP BY type"
		);
		foreach ( $types as $row ) {
			$by_type[ $row->type ] = (int) $row->count;
		}

		return array(
			'total'   => $total_active,
			'new'     => $new_in_period,
			'by_type' => $by_type,
		);
	}

	/**
	 * Get companies statistics.
	 *
	 * @return array Companies stats.
	 */
	private static function get_companies_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_companies';

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		return array(
			'total' => $total,
		);
	}

	/**
	 * Get transactions statistics.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array Transactions stats.
	 */
	private static function get_transactions_stats( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND created_at <= %s",
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );

		$by_gateway = array();
		$gateways = $wpdb->get_results( $wpdb->prepare(
			"SELECT gateway, COUNT(*) as count, SUM(amount) as total
			FROM {$table}
			WHERE created_at >= %s AND created_at <= %s AND type = 'payment' AND status = 'completed'
			GROUP BY gateway",
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );
		foreach ( $gateways as $row ) {
			$by_gateway[ $row->gateway ] = array(
				'count' => (int) $row->count,
				'total' => floatval( $row->total ),
			);
		}

		return array(
			'total'      => $total,
			'by_gateway' => $by_gateway,
		);
	}

	/**
	 * Get invoices statistics.
	 *
	 * @return array Invoices stats.
	 */
	private static function get_invoices_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		$by_status = array();
		$statuses = $wpdb->get_results(
			"SELECT status, COUNT(*) as count, SUM(total) as total FROM {$table} GROUP BY status"
		);
		foreach ( $statuses as $row ) {
			$by_status[ $row->status ] = array(
				'count' => (int) $row->count,
				'total' => floatval( $row->total ),
			);
		}

		// Count overdue.
		$overdue = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			WHERE status IN ('sent', 'viewed') AND due_date < %s",
			current_time( 'Y-m-d' )
		) );

		return array(
			'by_status' => $by_status,
			'overdue'   => $overdue,
			'pending'   => ( $by_status['sent']['count'] ?? 0 ) + ( $by_status['viewed']['count'] ?? 0 ),
			'paid'      => $by_status['paid']['count'] ?? 0,
		);
	}

	/**
	 * Get revenue statistics.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array Revenue stats.
	 */
	private static function get_revenue_stats( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';
		$default_currency = scrm_get_default_currency();

		// Get total revenue in default currency.
		$total = (float) $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(amount) FROM {$table}
			WHERE type = 'payment' AND status = 'completed'
			AND currency = %s
			AND created_at >= %s AND created_at <= %s",
			$default_currency,
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );

		// Get previous period for comparison.
		$period_days = ( strtotime( $end_date ) - strtotime( $start_date ) ) / DAY_IN_SECONDS;
		$prev_start = date( 'Y-m-d', strtotime( $start_date . ' -' . $period_days . ' days' ) );
		$prev_end = date( 'Y-m-d', strtotime( $start_date . ' -1 day' ) );

		$prev_total = (float) $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(amount) FROM {$table}
			WHERE type = 'payment' AND status = 'completed'
			AND currency = %s
			AND created_at >= %s AND created_at <= %s",
			$default_currency,
			$prev_start . ' 00:00:00',
			$prev_end . ' 23:59:59'
		) );

		// Calculate change percentage.
		$change = 0;
		if ( $prev_total > 0 ) {
			$change = round( ( ( $total - $prev_total ) / $prev_total ) * 100, 1 );
		}

		// Get by currency.
		$by_currency = array();
		$currencies = $wpdb->get_results( $wpdb->prepare(
			"SELECT currency, SUM(amount) as total FROM {$table}
			WHERE type = 'payment' AND status = 'completed'
			AND created_at >= %s AND created_at <= %s
			GROUP BY currency",
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );
		foreach ( $currencies as $row ) {
			$by_currency[ $row->currency ] = floatval( $row->total );
		}

		return array(
			'total'          => $total,
			'currency'       => $default_currency,
			'formatted'      => scrm_format_currency( $total, $default_currency ),
			'previous_total' => $prev_total,
			'change'         => $change,
			'by_currency'    => $by_currency,
		);
	}

	/**
	 * Get chart data for revenue.
	 *
	 * @param string $period Period.
	 * @return array Chart data.
	 */
	public static function get_revenue_chart_data( $period = '30days' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_transactions';
		$default_currency = scrm_get_default_currency();

		$end_date = current_time( 'Y-m-d' );
		$start_date = self::get_start_date( $period );

		// Get daily revenue.
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(created_at) as date, SUM(amount) as total
			FROM {$table}
			WHERE type = 'payment' AND status = 'completed'
			AND currency = %s
			AND created_at >= %s AND created_at <= %s
			GROUP BY DATE(created_at)
			ORDER BY date ASC",
			$default_currency,
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );

		$data = array();
		foreach ( $results as $row ) {
			$data[ $row->date ] = floatval( $row->total );
		}

		// Fill in missing dates.
		$labels = array();
		$values = array();
		$current = strtotime( $start_date );
		$end = strtotime( $end_date );

		while ( $current <= $end ) {
			$date = date( 'Y-m-d', $current );
			$labels[] = date( 'M j', $current );
			$values[] = $data[ $date ] ?? 0;
			$current = strtotime( '+1 day', $current );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label' => __( 'Revenue', 'syncpoint-crm' ),
					'data'  => $values,
				),
			),
		);
	}

	/**
	 * Get chart data for contacts.
	 *
	 * @param string $period Period.
	 * @return array Chart data.
	 */
	public static function get_contacts_chart_data( $period = '30days' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_contacts';

		$end_date = current_time( 'Y-m-d' );
		$start_date = self::get_start_date( $period );

		// Get cumulative contacts.
		$total_before = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE created_at < %s",
			$start_date . ' 00:00:00'
		) );

		$daily = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(created_at) as date, COUNT(*) as count
			FROM {$table}
			WHERE created_at >= %s AND created_at <= %s
			GROUP BY DATE(created_at)
			ORDER BY date ASC",
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59'
		) );

		$daily_data = array();
		foreach ( $daily as $row ) {
			$daily_data[ $row->date ] = (int) $row->count;
		}

		// Build cumulative data.
		$labels = array();
		$values = array();
		$cumulative = $total_before;
		$current = strtotime( $start_date );
		$end = strtotime( $end_date );

		while ( $current <= $end ) {
			$date = date( 'Y-m-d', $current );
			$cumulative += $daily_data[ $date ] ?? 0;
			$labels[] = date( 'M j', $current );
			$values[] = $cumulative;
			$current = strtotime( '+1 day', $current );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label' => __( 'Total Contacts', 'syncpoint-crm' ),
					'data'  => $values,
				),
			),
		);
	}

	/**
	 * Get recent activity.
	 *
	 * @param int $limit Number of items.
	 * @return array Activity items.
	 */
	public static function get_recent_activity( $limit = 10 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_activity_log';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
			$limit
		) );

		$activity = array();

		foreach ( $results as $row ) {
			$user = $row->user_id ? get_userdata( $row->user_id ) : null;

			$activity[] = array(
				'id'          => $row->id,
				'object_type' => $row->object_type,
				'object_id'   => $row->object_id,
				'action'      => $row->action,
				'description' => $row->description,
				'user_name'   => $user ? $user->display_name : __( 'System', 'syncpoint-crm' ),
				'created_at'  => $row->created_at,
				'time_ago'    => human_time_diff( strtotime( $row->created_at ), current_time( 'timestamp' ) ),
			);
		}

		return $activity;
	}
}
