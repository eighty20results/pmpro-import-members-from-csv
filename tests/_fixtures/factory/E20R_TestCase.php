<?php

namespace E20R\Tests\Fixtures\Factory;

use Codeception\TestCase\WPTestCase;
use WP_UnitTestCase;

/**
 * Provides E20R/PMPro-specific test case .
 */
class E20R_TestCase extends WPTestCase {

	/**
	 * Holds the E20R_IntegrationTest_Factory instance.
	 *
	 * @var E20R_IntegrationTest_Factory|null
	 */
	public static $factory = null;

	protected static function factory() {
		if ( ! self::$factory ) {
			self::$factory = new E20R_IntegrationTest_Factory();
		}
		return self::$factory;
	}
}
