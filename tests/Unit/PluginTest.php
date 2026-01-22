<?php
/**
 * Plugin tests.
 *
 * @package SyncPointCRM
 */

namespace SCRM\Tests\Unit;

use SCRM\Tests\TestCase;

/**
 * Test core plugin functionality.
 */
class PluginTest extends TestCase {

	/**
	 * Test that plugin constants are defined.
	 */
	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'SCRM_VERSION' ) );
		$this->assertTrue( defined( 'SCRM_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'SCRM_PLUGIN_FILE' ) );
	}

	/**
	 * Test plugin version format.
	 */
	public function test_plugin_version_format() {
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', SCRM_VERSION );
	}

	/**
	 * Test plugin directory exists.
	 */
	public function test_plugin_directory_exists() {
		$this->assertDirectoryExists( SCRM_PLUGIN_DIR );
	}

	/**
	 * Test main plugin file exists.
	 */
	public function test_main_plugin_file_exists() {
		$this->assertFileExists( SCRM_PLUGIN_FILE );
	}
}
