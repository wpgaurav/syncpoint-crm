<?php
/**
 * Helper functions tests.
 *
 * @package SyncPointCRM
 */

namespace SCRM\Tests\Unit;

use SCRM\Tests\TestCase;

/**
 * Test helper functions.
 */
class HelperFunctionsTest extends TestCase {

	/**
	 * Test scrm_sanitize_amount with valid values.
	 */
	public function test_sanitize_amount_valid_values() {
		// Skip if function not available (no WordPress loaded).
		if ( ! function_exists( 'scrm_sanitize_amount' ) ) {
			$this->markTestSkipped( 'Helper functions not loaded without WordPress.' );
		}

		$this->assertEquals( 100.00, scrm_sanitize_amount( '100' ) );
		$this->assertEquals( 100.50, scrm_sanitize_amount( '100.50' ) );
		$this->assertEquals( 0.00, scrm_sanitize_amount( '' ) );
		$this->assertEquals( 0.00, scrm_sanitize_amount( 'invalid' ) );
	}

	/**
	 * Test scrm_format_currency.
	 */
	public function test_format_currency() {
		// Skip if function not available.
		if ( ! function_exists( 'scrm_format_currency' ) ) {
			$this->markTestSkipped( 'Helper functions not loaded without WordPress.' );
		}

		$formatted = scrm_format_currency( 100.00, 'USD' );
		$this->assertStringContainsString( '100', $formatted );
	}

	/**
	 * Test scrm_generate_unique_id format.
	 */
	public function test_generate_unique_id_format() {
		// Skip if function not available.
		if ( ! function_exists( 'scrm_generate_unique_id' ) ) {
			$this->markTestSkipped( 'Helper functions not loaded without WordPress.' );
		}

		$id = scrm_generate_unique_id( 'CUST' );
		$this->assertMatchesRegularExpression( '/^CUST-\d+$/', $id );
	}
}
