<?php
/**
 * Plugin Name: SyncPoint CRM
 * Plugin URI: https://gatilab.com/syncpoint-crm
 * Description: A lightweight, extensible WordPress CRM with PayPal & Stripe sync, invoicing, contact management, and powerful automation capabilities.
 * Version: 1.1.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Gatilab
 * Author URI: https://gatilab.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: syncpoint-crm
 * Domain Path: /languages
 *
 * @package SyncPointCRM
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'SCRM_VERSION', '1.1.2' );
define( 'SCRM_PLUGIN_FILE', __FILE__ );
define( 'SCRM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main SyncPoint CRM Plugin Class.
 *
 * @since 1.0.0
 */
final class SyncPoint_CRM {

	/**
	 * Plugin instance.
	 *
	 * @var SyncPoint_CRM|null
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @var SCRM_Admin|null
	 */
	public $admin = null;

	/**
	 * API instance.
	 *
	 * @var SCRM_REST_API|null
	 */
	public $api = null;

	/**
	 * Gateways manager.
	 *
	 * @var SCRM_Gateways|null
	 */
	public $gateways = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 * @return SyncPoint_CRM
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->check_requirements();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Check plugin requirements.
	 *
	 * @since 1.0.0
	 */
	private function check_requirements() {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}

		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return;
		}
	}

	/**
	 * PHP version notice.
	 *
	 * @since 1.0.0
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: PHP version */
					esc_html__( 'SyncPoint CRM requires PHP version 7.4 or higher. You are running PHP %s.', 'syncpoint-crm' ),
					esc_html( PHP_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * WordPress version notice.
	 *
	 * @since 1.0.0
	 */
	public function wp_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: WordPress version */
					esc_html__( 'SyncPoint CRM requires WordPress version 6.0 or higher. You are running WordPress %s.', 'syncpoint-crm' ),
					esc_html( get_bloginfo( 'version' ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		// Autoloader.
		spl_autoload_register( array( $this, 'autoloader' ) );

		// Core includes.
		require_once SCRM_PLUGIN_DIR . 'includes/scrm-functions.php';
		require_once SCRM_PLUGIN_DIR . 'includes/scrm-helper-functions.php';

		// Activator/Deactivator.
		require_once SCRM_PLUGIN_DIR . 'includes/class-scrm-activator.php';
		require_once SCRM_PLUGIN_DIR . 'includes/class-scrm-deactivator.php';

		// AJAX handler.
		require_once SCRM_PLUGIN_DIR . 'includes/class-scrm-ajax.php';

		// Cron handler.
		require_once SCRM_PLUGIN_DIR . 'includes/class-scrm-cron.php';

		// Frontend invoice handler.
		require_once SCRM_PLUGIN_DIR . 'includes/class-scrm-frontend-invoice.php';

		// Admin includes.
		if ( is_admin() ) {
			require_once SCRM_PLUGIN_DIR . 'includes/admin/class-scrm-admin.php';
			require_once SCRM_PLUGIN_DIR . 'includes/admin/class-scrm-admin-settings.php';
			require_once SCRM_PLUGIN_DIR . 'includes/admin/class-scrm-dashboard.php';
			require_once SCRM_PLUGIN_DIR . 'includes/admin/class-scrm-contacts-list-table.php';
		}

		// API includes.
		require_once SCRM_PLUGIN_DIR . 'includes/api/class-scrm-rest-api.php';
		require_once SCRM_PLUGIN_DIR . 'includes/api/class-scrm-webhooks.php';
	}

	/**
	 * PSR-4 style autoloader.
	 *
	 * @since 1.0.0
	 * @param string $class_name Class name to load.
	 */
	public function autoloader( $class_name ) {
		// Only load SCRM classes.
		if ( 0 !== strpos( $class_name, 'SCRM_' ) && 0 !== strpos( $class_name, 'SCRM\\' ) ) {
			return;
		}

		// Convert class name to file path.
		$class_file = str_replace( array( 'SCRM_', 'SCRM\\', '_' ), array( '', '', '-' ), $class_name );
		$class_file = 'class-' . strtolower( $class_file ) . '.php';

		// Map namespaces to directories.
		$namespace_map = array(
			'Admin\\'    => 'admin/',
			'Core\\'     => 'core/',
			'Gateways\\' => 'gateways/',
			'API\\'      => 'api/',
			'Import\\'   => 'import/',
			'Export\\'   => 'export/',
			'Utils\\'    => 'utils/',
		);

		$file_path = SCRM_PLUGIN_DIR . 'includes/';

		foreach ( $namespace_map as $namespace => $directory ) {
			if ( strpos( $class_name, 'SCRM\\' . $namespace ) === 0 ) {
				$file_path .= $directory;
				$class_file = str_replace( strtolower( str_replace( '\\', '-', $namespace ) ), '', $class_file );
				break;
			}
		}

		$file_path .= $class_file;

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Activation/Deactivation.
		register_activation_hook( SCRM_PLUGIN_FILE, array( 'SCRM_Activator', 'activate' ) );
		register_deactivation_hook( SCRM_PLUGIN_FILE, array( 'SCRM_Deactivator', 'deactivate' ) );

		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		// Load textdomain.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		/**
		 * Fires before SyncPoint CRM is initialized.
		 *
		 * @since 1.0.0
		 */
		do_action( 'scrm_before_init' );

		// Initialize components.
		$this->init_gateways();

		if ( is_admin() ) {
			$this->admin = new SCRM_Admin();
		}

		$this->api = new SCRM_REST_API();

		/**
		 * Fires when SyncPoint CRM is fully initialized.
		 *
		 * @since 1.0.0
		 */
		do_action( 'scrm_init' );

		/**
		 * Fires after SyncPoint CRM is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'scrm_loaded' );
	}

	/**
	 * Initialize payment gateways.
	 *
	 * @since 1.0.0
	 */
	private function init_gateways() {
		/**
		 * Filter the available payment gateways.
		 *
		 * @since 1.0.0
		 * @param array $gateways Array of gateway class names.
		 */
		$gateway_classes = apply_filters( 'scrm_payment_gateways', array(
			'paypal' => 'SCRM_PayPal',
			'stripe' => 'SCRM_Stripe',
			'manual' => 'SCRM_Manual',
		) );

		// Initialize gateway instances.
		$this->gateways = array();
		foreach ( $gateway_classes as $id => $class ) {
			if ( class_exists( $class ) ) {
				$this->gateways[ $id ] = new $class();
			}
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'syncpoint-crm',
			false,
			dirname( SCRM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		if ( $this->api ) {
			$this->api->register_routes();
		}
	}

	/**
	 * Get a gateway by ID.
	 *
	 * @since 1.0.0
	 * @param string $gateway_id Gateway ID.
	 * @return object|null Gateway instance or null.
	 */
	public function get_gateway( $gateway_id ) {
		return isset( $this->gateways[ $gateway_id ] ) ? $this->gateways[ $gateway_id ] : null;
	}

	/**
	 * Get all active gateways.
	 *
	 * @since 1.0.0
	 * @return array Array of active gateway instances.
	 */
	public function get_active_gateways() {
		$active = array();
		foreach ( $this->gateways as $id => $gateway ) {
			if ( method_exists( $gateway, 'is_available' ) && $gateway->is_available() ) {
				$active[ $id ] = $gateway;
			}
		}
		return $active;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton.' );
	}
}

/**
 * Get the SyncPoint CRM plugin instance.
 *
 * @since 1.0.0
 * @return SyncPoint_CRM
 */
function scrm() {
	return SyncPoint_CRM::instance();
}

// Initialize the plugin.
scrm();
