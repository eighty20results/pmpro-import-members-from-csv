<?php

namespace E20R\Tests\Unit;

use Codeception\Test\Unit;
use E20R\Exceptions\InvalidInstantiation;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WP_Error;
use WP_Mock;

class User_Present_UnitTest extends Unit {
	use MockeryPHPUnitIntegration;

	/**
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp() : void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Codeception tearDown() method
	 *
	 * @return void
	 */
	public function tearDown() : void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {
		Functions\stubs(
			array(
				'get_option'           => 'https://www.paypal.com/cgi-bin/webscr',
				'update_option'        => true,
				'plugin_dir_path'      => '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv',
				'is_email'             => true,
				'wp_generate_password' => 'dummy_password_string',
				'sanitize_user'        => null,
				'sanitize_title'       => null,
				'is_multisite'         => false,
				'maybe_unserialize'    => null,
				'update_user_meta'     => true,
			)
		);
	}

	/**
	 * Test status message processing for User_Present()
	 *
	 * @param int $status The status code
	 * @param int $line_number Mocked line # from the CSV file
	 * @param string $expected_msg The returned message we expect for that status and line #
	 * @param string $expected_category Error/Warning category for the message
	 *
	 * @return void
	 *
	 * @test
	 *
	 * @dataProvider fixture_status_msgs
	 */
	public function it_should_validate_status_msgs( $status, $line_number, $expected_msg, $expected_category ) {
		global $e20r_import_err;
		global $e20r_import_warn;
		global $active_line_number;

	}

	/**
	 * Fixture for User_Present_UnitTest::it_should_validate_status_msgs()
	 *
	 * @return array[]
	 */
	public function fixture_status_msgs() {
		return array(
			array( Status::E20R_ERROR_USER_NOT_FOUND, 1, '', '' ),
		);
	}

	/**
	 * Testing instantiation with error(s) thrown
	 *
	 * @param mixed $var_class The instance of the Variables() class we're using for the test
	 * @param mixed $log_class The instance of the Error_Log() class we're using for the test
	 * @param mixed $wp_err_class The instance of the WP_Error() class we're using for the test
	 * @param mixed $expected_exception The expected exception class name
	 * @param string $expected_message The expected exception message
	 *
	 * @test
	 *
	 * @dataProvider fixture_class_instantiation_members
	 * @throws InvalidInstantiation
	 */
	public function it_should_instantiate_with_exception( $var_class, $log_class, $wp_err_class, $expected_exception, $expected_message ) {
		$this->expectException( $expected_exception );
		$this->expectExceptionMessageMatches( $expected_message );

		Functions\stubs(
			array(
				'wp_upload_dir'   => array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads',
				),
				'esc_attr__'      => null,
				'esc_url_raw'     => null,
				'trailingslashit' => null,
			)
		);

		new User_Present( $var_class, $log_class, $wp_err_class );
	}

	/**
	 * Fixture for the User_Present_UnitTest::it_should_instantiate_with_exception()
	 *
	 * @return array[]
	 * @throws \Exception
	 */
	public function fixture_class_instantiation_members() {
		return array(
			// variables, error_log, wp_error, exception, message regex
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			array( new Variables(), false, new WP_Error(), InvalidInstantiation::class, '/.*Expecting "Error_Log"$/' ),
			array( 'Variables', new Error_Log(), new WP_Error(), InvalidInstantiation::class, '/.*Expecting "Variables"$/' ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			array( new Variables(), new Error_Log(), false, InvalidInstantiation::class, '/.*Expecting "WP_Error"$/' ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			array( false, false, false, InvalidInstantiation::class, '/.*Expecting "Error_Log"$/' ),
		);
	}
}
