<?php

namespace E20R\Tests\Fixtures\Factory;

use Codeception\TestCase\WPTestCase;
use WP_UnitTestCase;

/**
 * Provides E20R/PMPro-specific test case .
 */
class E20R_TestCase extends WPTestCase {

	/**
	 * Holds the E20R_UnitTest_Factory instance.
	 *
	 * @var E20R_UnitTest_Factory
	 */
	protected $factory;

	/**
	 * Setup test case.
	 */
	public function setUp() : void {
		parent::setUp();
		// Add custom factories.
		$this->factory = new E20R_UnitTest_Factory();
	}
}
