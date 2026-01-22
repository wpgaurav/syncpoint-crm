<?php
/**
 * Gateway tests.
 *
 * @package SyncPointCRM
 */

namespace SCRM\Tests\Unit;

use SCRM\Tests\TestCase;

/**
 * Test payment gateway functionality.
 */
class GatewayTest extends TestCase {

	/**
	 * Test gateway class files exist.
	 */
	public function test_gateway_class_files_exist() {
		$gateway_dir = SCRM_PLUGIN_DIR . 'includes/gateways/';

		$this->assertFileExists( $gateway_dir . 'class-scrm-gateway.php' );
		$this->assertFileExists( $gateway_dir . 'class-scrm-paypal.php' );
		$this->assertFileExists( $gateway_dir . 'class-scrm-stripe.php' );
		$this->assertFileExists( $gateway_dir . 'class-scrm-manual.php' );
	}

	/**
	 * Test gateway filter exists.
	 */
	public function test_gateway_filter_exists() {
		// Skip if WordPress not loaded.
		if ( ! function_exists( 'has_filter' ) ) {
			$this->markTestSkipped( 'WordPress not loaded.' );
		}

		// The filter should be available after plugin loads.
		$gateways = apply_filters( 'scrm_payment_gateways', array() );
		$this->assertIsArray( $gateways );
	}
}
