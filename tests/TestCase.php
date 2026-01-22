<?php
/**
 * Base test case for SyncPoint CRM.
 *
 * @package SyncPointCRM
 */

namespace SCRM\Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Base test case class.
 */
abstract class TestCase extends PolyfillTestCase {

	/**
	 * Set up before each test.
	 */
	protected function set_up() {
		parent::set_up();
	}

	/**
	 * Tear down after each test.
	 */
	protected function tear_down() {
		parent::tear_down();
	}
}
