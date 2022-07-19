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
use wpdb;


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
	 * @param string $expected_err_category Error category for the message
	 * @param string $expected_warn_category Warning category for the message
	 *
	 * @return void
	 *
	 * @test
	 *
	 * @dataProvider fixture_status_msgs
	 * @throws InvalidInstantiation Should not be thrown in this test, test failure should result if it is thrown
	 */
	public function it_should_validate_status_msgs( $status, $line_number, $error_msgs, $warn_msgs, $expected_msg, $expected_err_category, $expected_warn_category ) {
		global $e20r_import_err;
		global $e20r_import_warn;
		global $active_line_number;

		$e20r_import_err    = $error_msgs;
		$e20r_import_warn   = $warn_msgs;
		$active_line_number = $line_number;

		Functions\stubs(
			array(
				'esc_attr__' => null,
			)
		);

		$m_errs = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					error_log( "Mock: {$msg}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				},
			)
		);
		$m_vars = $this->constructEmpty( Variables::class, array( $m_errs ) );
		$wperr  = new WP_Error();

		$class = new User_Present( $m_vars, $m_errs, $wperr );
		$class->status_msg( $status, false );

		if ( null !== $expected_err_category ) {
			self::assertArrayHasKey( $expected_err_category, $e20r_import_err );
			self::assertIsObject( $e20r_import_err[ $expected_err_category ] );
			self::assertInstanceOf( WP_Error::class, $e20r_import_err[ $expected_err_category ] );
		}

		if ( null !== $expected_warn_category ) {
			self::assertArrayHasKey( $expected_warn_category, $e20r_import_warn );
			self::assertIsObject( $e20r_import_warn[ $expected_warn_category ] );
			self::assertInstanceOf( WP_Error::class, $e20r_import_warn[ $expected_warn_category ] );
		}

		if ( null === $expected_err_category && null === $expected_warn_category ) {
			self::assertIsArray( $e20r_import_warn );
			self::assertIsArray( $e20r_import_err );
			self::assertEmpty( $e20r_import_err );
			self::assertEmpty( $e20r_import_warn );
		}
	}

	/**
	 * Fixture for User_Present_UnitTest::it_should_validate_status_msgs()
	 *
	 * @return array[]
	 */
	public function fixture_status_msgs() {
		$custom_warnings = array(
			'warn_invalid_email_1' => new WP_Error(),
			'warn_invalid_email_*' => new WP_Error(),
			'cannot_update_4'      => new WP_Error(),
		);

		$custom_errors = array(
			'error_invalid_user_id_1' => new WP_Error(),
			'error_invalid_user_id_*' => new WP_Error(),
		);

		return array(
			// status, line_number, error_msgs, warn_msgs, expected_msg, expected_err_category, expected_warn_category ) {
			array( Status::E20R_ERROR_USER_NOT_FOUND, 1, array(), array(), "Error: Expected to find user from information in record, but didn't succeed! (line: 1)", 'user_not_found_1', null ),
			array( Status::E20R_ERROR_USER_NOT_FOUND, 2, null, null, "Error: Expected to find user from information in record, but didn't succeed! (line: 2)", 'user_not_found_2', null ),
			array( Status::E20R_USER_IDENTIFIER_MISSING, 3, array(), array(), 'Error: Neither the ID, user_login nor user_email field exists in the CSV record being processed! (line 3)', 'no_identifying_info_3', null ),
			array( Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED, 4, array(), array(), 'The import data specifies an existing user but the plugin settings disallow updating their record (line: 4)', null, 'cannot_update_4' ),
			array( Status::E20R_ERROR_ID_NOT_NUMBER, 5, array(), array(), 'Supplied information in ID column is not a number! (line 5)', 'error_invalid_user_id_5', null ),
			array( Status::E20R_ERROR_NO_EMAIL, 6, array(), array(), 'Invalid email address in CSV record. (line 6)', null, 'warn_invalid_email_6' ),
			array( Status::E20R_ERROR_NO_EMAIL_OR_LOGIN, 7, array(), array(), 'Neither "user_email" nor "user_login" column found, or the "user_email" and "user_login" column(s) was/were included, the user exists, and the "Update user record" option was NOT selected. (line: 7).', null, 'warn_invalid_email_login_7' ),
			array( Status::E20R_ERROR_NO_EMAIL, 6, array(), $custom_warnings, 'Invalid email in row 6 (Not imported).', null, 'warn_invalid_email_6' ),
			array( Status::E20R_ERROR_ID_NOT_NUMBER, 5, $custom_errors, array(), 'Supplied information in ID column on line 5 is not a number', 'error_invalid_user_id_5', null ),
			array( 0, 5, null, null, 'Not a valid message', null, null ),
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

	/**
	 * Test the User_Present::load_ignored_module_errors() method (shouldn't do anything to the supplied list)
	 *
	 * @param array $supplied_modules List of modules to ignore errors for
	 *
	 * @return void
	 * @throws InvalidInstantiation
	 *
	 * @test
	 * @dataProvider fixture_ignored_modules
	 */
	public function it_should_return_same_ignored_list( $supplied_modules ) {
		$m_errs = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					error_log( "Mock: {$msg}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				},
			)
		);
		$m_vars = $this->constructEmpty( Variables::class, array( $m_errs ) );
		$wperr  = new WP_Error();

		$class  = new User_Present( $m_vars, $m_errs, $wperr );
		$result = $class->load_ignored_module_errors( $supplied_modules );
		if ( is_countable( $result ) ) {
			self::assertCount( count( $supplied_modules ), $result );
		}
		self::assertSame( $supplied_modules, $result );
	}

	/**
	 * Fixture for the User_Present_UnitTest::it_should_return_same_ignored_list() test
	 *
	 * @return array
	 */
	public function fixture_ignored_modules() {
		return array(
			array( null ),
			array( array() ),
			array( array( 'one', 'two', 'three', 'four' ) ),
			array(
				array(
					'one' => array(),
					'two' => array(),
				),
			),
			array( 'string' ),
		);
	}

	/**
	 * Test the User_Presence::data_can_be_imported() method
	 *
	 * @param mixed $column_exists Does the specified column exist in the CSV file
	 * @param mixed $user_exists Does the specified user exist in the CSV file
	 * @param mixed $update_allowed Can we update data for the user in the WP database
	 * @param bool|int $expected The expected result from the test execution
	 *
	 * @return void
	 * @throws InvalidInstantiation
	 *
	 * @test
	 * @dataProvider fixture_importability_statuses
	 */
	public function it_should_validate_supportability_of_data_import( $column_exists, $user_exists, $update_allowed, $expected ) {

		$m_errs = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					error_log( "Mock: {$msg}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				},
			)
		);
		$m_vars = $this->constructEmpty( Variables::class, array( $m_errs ) );
		$wperr  = new WP_Error();

		$class  = new User_Present( $m_vars, $m_errs, $wperr );
		$result = $class->data_can_be_imported( $column_exists, $user_exists, $update_allowed );

		self::assertNotEmpty( $result );
		self::assertEquals( $expected, $result );
	}

	/**
	 * Fixture for the User_Present_UnitTest::it_should_validate_supportability_of_data_import() test
	 *
	 * @return array
	 */
	public function fixture_importability_statuses() {
		return array(
			// column_exists, user_exists, update_allowed, value returned
			array( true, true, true, true ),
			array( true, true, false, Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED ),
			array( true, false, true, Status::E20R_ERROR_USER_NOT_FOUND ),
			array( false, true, true, Status::E20R_USER_IDENTIFIER_MISSING ),
			array( null, true, true, true ),
			array( true, null, true, true ),
			array( true, true, null, true ),
			array( true, '', true, true ),
			array( '', true, true, true ),
			array( true, true, '', true ),
		);
	}

	/**
	 * Test of the User_Present::db_user_exists() method
	 *
	 * @param array|string $column_data The CSV column we're processing with data
	 * @param array|string $value_to_find The value(s) we're searching for
	 * @param string $table_name The tblae name to search
	 * @param int $counted_users Number of users counted (returned from mocked wpdb::get_var())
	 * @param string $expected_sql The SQL statement we expect db_user_exists() will generate
	 * @param boolean $expected_result The expected value returned from db_user_exists()
	 *
	 * @return void
	 * @throws InvalidInstantiation
	 *
	 * @test
	 * @dataProvider fixture_test_sql_for_user
	 */
	public function it_should_generate_valid_user_table_SQL( $column_data, $value_to_find, $table_name, $counted_users, $expected_sql, $expected_result ) {
		Functions\stubs(
			array(
				'esc_sql' => null,
			)
		);

		$m_errs = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					error_log( "Mock: {$msg}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				},
			)
		);
		$m_vars = $this->constructEmpty( Variables::class, array( $m_errs ) );
		$wperr  = $this->makeEmpty( WP_Error::class );
		$m_wpdb = $this->makeEmpty(
			wpdb::class,
			array(
				'get_var' => function( $sql ) use ( $expected_sql, $table_name, $counted_users ) {
					self::assertSame( $expected_sql, $sql );
					return $counted_users;
				},
			)
		);

		$m_wpdb->users = $table_name;

		$class  = new User_Present( $m_vars, $m_errs, $wperr );
		$result = $class->db_user_exists( $column_data, $value_to_find, $m_wpdb );
		self::assertIsBool( $result );
		self::assertSame( $expected_result, $result );
	}

	/**
	 * Fixture for the User_Present_UnitTest::it_should_generate_valid_user_table_SQL() test
	 *
	 * @return array[]
	 */
	public function fixture_test_sql_for_user() {
		return array(
			// column_data, value to find, table name, counted users, expected sql, expected outcome
			array( 'user_email', 'tester@example.org', 'wptest_users', 0, "SELECT COUNT(ID) FROM wptest_users WHERE user_email = 'tester@example.org'", false ),
			array( 'user_email', 'tester@example.org', 'wptest_users', 1, "SELECT COUNT(ID) FROM wptest_users WHERE user_email = 'tester@example.org'", true ),
			array( array( 'user_email', 'user_login' ), array( 'tester@example_org', 'tester' ), 'wp_users', 1, "SELECT COUNT(ID) FROM wp_users WHERE user_email = 'tester@example_org' AND user_login = 'tester'", true ),
			array( array( 'user_email', 'user_login' ), array( 'tester@example_org', 'tester' ), 'wp_users', 0, "SELECT COUNT(ID) FROM wp_users WHERE user_email = 'tester@example_org' AND user_login = 'tester'", false ),
		);
	}
}
