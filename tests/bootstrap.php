<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package SyncPointCRM
 */

// Composer autoloader for Yoast PHPUnit Polyfills.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Determine the tests directory (needed for loading WordPress tests).
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills';
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh?" . PHP_EOL;
	echo "Running unit tests without WordPress integration..." . PHP_EOL;

	// Load plugin files directly for unit tests without WordPress.
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
	define( 'SCRM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
	define( 'SCRM_PLUGIN_FILE', dirname( __DIR__ ) . '/syncpoint-crm.php' );
	define( 'SCRM_VERSION', '1.2.2' );

	// Load base test case.
	require_once __DIR__ . '/TestCase.php';

	return;
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/syncpoint-crm.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Load base test case.
require_once __DIR__ . '/TestCase.php';
