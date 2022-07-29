<?php
/**
 * Copyright (c) 2022. - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package E20R\Tests\Integration\Import_Users_IntegrationTest
 */

namespace E20R\Tests\Integration;

use E20R\Exceptions\CannotUpdateDB;
use E20R\Exceptions\InvalidProperty;
use E20R\Exceptions\UserIDAlreadyExists;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\Generate_Password;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Status;
use E20R\Import_Members\Validate\Date_Format;
use E20R\Import_Members\Validate\Time;
use E20R\Import_Members\Variables;

use E20R\Tests\Fixtures\Factory\E20R_IntegrationTest_Generator_Sequence;
use E20R\Tests\Fixtures\Factory\E20R_TestCase;
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WP_Mock;
use org\bovigo\vfs\vfsStream;

use MemberOrder;

use WP_Error;
use WP_User;
use wpdb;

use Brain\Monkey;

class Import_User_IntegrationTest extends E20R_TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Default Error_Log() class
	 *
	 * @var null|Error_Log
	 */
	private $errorlog = null;

	/**
	 * Variables() class
	 *
	 * @var null|Variables
	 */
	private $variables = null;

	/**
	 * Default Import_User() class
	 *
	 * @var null|Import_User
	 */
	private $import = null;

	/**
	 * Default PMPro MemberOrder()
	 *
	 * @var null|MemberOrder
	 */
	private $order = null;

	/**
	 * Default Error class for WordPress
	 *
	 * @var null|WP_Error
	 *
	 */
	private $wp_error = null;

	/**
	 * Instance of the User_Present class
	 *
	 * @var null|User_Present
	 */
	private $presence = null;

	/**
	 * Instance of the Generate_Password() validation class
	 *
	 * @var null|Generate_Password $generate_passwd
	 *
	 */
	private $generate_passwd = null;

	/**
	 * Instance of the time format validation class Time()
	 *
	 * @var null|Time $time
	 */
	private $time = null;

	/**
	 * Instance of the date format validation class Date_Format()
	 *
	 * @var null|Date_Format $date
	 */
	private $date = null;

	/**
	 * Mocked file system to use for testing purposes
	 *
	 * @var null|vfsStream
	 */
	private $file_system = null;

	/**
	 * Directory structure for the mocked file system
	 *
	 * @var \array[][][][][][]
	 */
	private $structure;

	/**
	 * Path to CSV file we'll use during CSV integration tests
	 *
	 * @var string
	 */
	private static $path = 'vfs://mocked/var/www/html/wp-content/uploads/e20r_imports/csv_test_file.csv';

	/**
	 * Array of CSV test data to use
	 *
	 * @var array
	 */
	private $csv_data;

	/**
	 * The user ID we'll use
	 *
	 * @var int
	 */
	private $id_to_create = 0;
	/**
	 * List of generated WordPress users
	 *
	 * @var WP_User[]
	 */
	private $new_users = array();

	/**
	 * The path to the file containing the user data
	 *
	 * @var null|string $fixture_user_csv
	 */
	private $fixture_user_csv = null;

	/**
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		WP_Mock::setUp();

		$this->load_mocks();
		$this->fixture_user_csv = __DIR__ . '/../../_data/csv_files/fixture_user_data.csv';

		// Insert membership level data
		$this->factory()->pmprolevels->create_many( 2 );

		$this->structure   = array(
			'var' => array(
				'www' => array(
					'html' => array(
						'wp-content' => array(
							'uploads' => array(
								'e20r_imports' => array(),
							),
						),
					),
				),
			),
		);
		$this->file_system = vfsStream::setup( 'mocked', null, $this->structure );
	}

	/**
	 * Codeception tear down method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		Monkey\tearDown();
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 */
	public function load_mocks(): void {

		$this->errorlog        = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->wp_error        = new WP_Error();
		$this->variables       = new Variables( $this->errorlog );
		$this->presence        = new User_Present( $this->variables, $this->errorlog, $this->wp_error, $this->password );
		$this->generate_passwd = new Generate_Password( $this->variables, $this->generate_passwd );
		$this->date            = new Date_Format( $this->variables, $this->errorlog );
		$this->time            = new Time( $this->variables, $this->errorlog );
		$this->import          = new Import_User( $this->variables, $this->errorlog, $this->presence, $this->generate_passwd, $this->time, $this->date );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs(): void {
	}

	// FIXME: Add test for an existing user record with a new password and check the $e20r_import_warn status

	/**
	 * Test of the happy-path when importing a user who doesn't exist in the DB already
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param bool $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int $users_to_create Number of user records to create
	 * @param int $expected_id Result we expect to see from the test execution
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @dataProvider fixture_user_import
	 * @test
	 */
	public function it_should_add_new_user( $allow_update, $disable_hashing, $users_to_create, $expected_id, $update_id, $roles, $supplied_password, $expected_email, $expected_login ) {
		// Just execute the import test
		$csv_headers = $this->fixture_csv_header();

		$this->fixture_csv_data( $expected_id, $roles, $supplied_password, $update_id, $csv_headers );

		$this->run_import_function(
			$allow_update,
			$disable_hashing,
			$expected_id,
			$expected_email,
			$expected_login
		);
	}

	/**
	 * Fixture generator for the it_should_create_new_user() test method
	 *
	 * @return array
	 */
	public function fixture_user_import() {
		return array(
			// allow_update, disable_hashing, users_to_create, expected_id, update_id, roles, supplied_password, expected_email, expected_login
			array( false, false, 1, 2, null, 'subscriber', null, 'user_2@example.org', 'user_2' ),
			array( false, false, 1, 3, null, 'subscriber', null, 'user_3@example.org', 'user_3' ),
			array( false, false, 1, 4, null, 'subscriber;admin', 'password', 'user_4@example.org', 'user_4' ),
			array( false, false, 1, 5, null, 'subscriber', 'password', 'user_5@example.org', 'user_5' ),
			array( false, true, 1, 6, null, 'subscriber', '$P$B.hqQoTosqb3O.AUwRiIu5qU6y/xnJ1', 'user_6@example.org', 'user_6' ),
			array( false, false, 1, 7, null, 'subscriber; admin', 'password', 'user_7@example.org', 'user_7' ),
			array( false, false, 1, 8, null, ' subscriber;admin', 'password', 'user_8@example.org', 'user_8' ),
			array( false, false, 1, 9, null, 'subscriber;admin ', 'password', 'user_9@example.org', 'user_9' ),
			array( false, false, 1, 10, null, 'subscriber ;admin ', 'password', 'user_10@example.org', 'user_10' ),
			array( false, false, 1, 11, null, 'subscriber ; admin ', 'password', 'user_11@example.org', 'user_11' ),

		);
	}

	/**
	 * Test import of users but don't allow updating them
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param int $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int $users_to_create Fake users to create for this test to function
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @dataProvider fixture_users_to_add_and_update
	 * @test
	 */
	public function it_should_add_new_and_disallow_updates( $allow_update, $disable_hashing, $users_to_create, $expected_id, $expected_email, $expected_login ) {

		if ( false !== $allow_update ) {
			$this->fail( 'Should not allow updating existing user data' );
		}

		$this->fixture_create_user_data( $users_to_create, null, null, null, ( 0 === $expected_id ) );

		$this->run_import_function(
			$allow_update,
			$disable_hashing,
			$expected_id,
			$expected_email,
			$expected_login
		);
	}

	/**
	 * Fixture for multiple users to add, including repeating users so data will get updated
	 *
	 * @return array|array[]
	 */
	public function fixture_users_to_add_and_update() {
		return array(
			// allow_update, disable_hashing, users_to_create, expected_id, expected_email, expected_login
			array( false, false, 1, 0, false, false ), // User already exists, and we can't update them so Import_User::maybe_add_or_update() should return null
			array( false, false, 1, 13, 'user_3@example.org', 'user_3' ), // User doesn't exist so being added
			array( false, false, 1, 14, 'user_5@example.org', 'user_5' ), // User doesn't exist so being added
			array( false, false, 1, 0, false, false ), // User already exists, and we can't update them so Import_User::maybe_add_or_update() should return null
			array( false, false, 1, 16, 'user_3@example.org', 'user_3' ), // User doesn't exist so being added
			array( false, false, 1, 17, 'user_5@example.org', 'user_5' ), // User doesn't exist so being added
		);
	}

	/**
	 * Test of the happy-path when importing a user who exist in the DB already _and_ we want to update them
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param bool $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int $users_to_create Fake users to create for this test to function
	 * @param int $update_id The user ID we want to update the user record to now use
	 * @param string $role The role info to use when updating the user
	 * @param string $supplied_password The password to use during import (if applicable)
	 *
	 * @dataProvider fixture_update_user_import
	 * @test
	 */
	public function it_should_update_user( $allow_update, $disable_hashing, $users_to_create, $update_id, $role, $supplied_password, $expected_id, $expected_login, $expected_mail ) {

		if ( true !== $allow_update ) {
			$this->fail( 'Should allow updating existing user data' );
		}

		$this->fixture_create_user_data( $users_to_create, $update_id, $role, $supplied_password, true );

		$this->run_import_function(
			$allow_update,
			$disable_hashing,
			$expected_id,
			$expected_mail,
			$expected_login
		);
	}

	/**
	 * Fixture generator for the it_should_update_user() test method
	 *
	 * @return array
	 */
	public function fixture_update_user_import() {
		return array(
			// allow_update, disable_hashing, users_to_create, update_id, role, supplied_password, expected_id, expected_login, expected_mail
			array( true, false, 1, 10, null, null, 10, 'user_1', 'user_1@example.org' ),
			array( true, false, 1, 1000, null, null, 1000, 'user_1', 'user_1@example.org' ),
			array( true, true, 1, null, null, null, 1001, 'user_1', 'user_1@example.org' ),
			array( true, false, 1, null, 'subscriber;administrator', null, 1002, 'user_1', 'user_1@example.org' ),
			array( true, true, 1, null, 'subscriber', '$P$B.hqQoTosqb3O.AUwRiIu5qU6y/xnJ1', 1003, 'user_1', 'user_1@example.org' ),
			array( true, true, 1, null, 'subscriber', '$P$B.hqQoTosqb3O.AUwRiIu5qU6y/xnJ1', 1004, 'user_1', 'user_1@example.org' ),
		);
	}

	/**
	 * Insert test data (User) in WP Users table
	 *
	 * @param int $users_to_create Number of fake user records to create/mock
	 * @param int|false $update_id User ID to use when adding user(s)
	 * @param string|null $roles The specified roles to use during the import operation
	 * @param string|null $supplied_password The password to use for the mocked CSV file line
	 * @return bool|int[]
	 */
	public function fixture_create_user_data( $users_to_create = 1, $update_id = false, $roles = null, $supplied_password = null, $add_existing_users = false ) {
		$new_user_ids = array();

		$user_args = array(
			'user_login' => new E20R_IntegrationTest_Generator_Sequence( 'user_%s', 1 ),
			'user_email' => new E20R_IntegrationTest_Generator_Sequence( 'user_%s@example.org', 1 ),
			'user_pass'  => $supplied_password ?? 'password',
		);

		if ( true === $add_existing_users ) {
			for ( $i = 1; $i <= $users_to_create; $i ++ ) {
				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
				$user_args['user_login']::$incr = $i;

				$user_id = wp_create_user( $user_args['user_login'], $user_args['user_pass'], $user_args['user_email'] );

				if ( is_wp_error( $user_id ) ) {
					$this->errorlog->debug( 'Error adding existing user to update: ' . $user_id->get_error_message() );
					return false;
				}
				$new_user_ids[] = $user_id;
			}
		}
		$csv_headers = $this->fixture_csv_header();

		for ( $i = 1; $i <= $users_to_create; $i++ ) {
			$this->fixture_csv_data( $user_args['user_login']->get_incr(), $roles, $supplied_password, $update_id, $csv_headers );
		}

		// Add info to CSV file to be imported
		return $new_user_ids;
	}

	/**
	 * Build a line for a CSV file to add (header and data being returned)
	 *
	 * @param int $user_id List of the factory created user IDs
	 * @param string|null $roles The role(s) to assign (empty, single role name, or a semicolon separated list)
	 * @param string|null $supplied_password The password to use
	 * @param int $updated_id The new ID the CSV should set for the user (if applicable)
	 * @param string[] $headers The CSV headers we're mocking
	 */
	private function fixture_csv_data( $user_id, $roles = null, $supplied_password = null, $updated_id = null, $headers = array() ) {
		$data = $this->fixture_mock_data();

		foreach ( $headers as $key => $header ) {
			$value = $data[ $key ];
			switch ( $header ) {
				case 'user_pass':
					$value = $supplied_password ?? 'password';
					break;
				case 'user_login':
					$value = "user_{$user_id}";
					break;
				case 'user_email':
					$value = "user_{$user_id}@example.org";
					break;
				case 'last_name':
				case 'pmpro_blastname':
				case 'pmpro_slastname':
					$value = "Tester #{$user_id}";
					break;
				case 'first_name':
				case 'pmpro_bfirstname':
				case 'pmpro_sfirstname':
					$value = 'Joe';
					break;
				case 'display_name':
					$value = sprintf( 'Joe Testuser #%1$s', $user_id );
					break;
				case 'role':
					if ( null !== $roles ) {
						$value = $roles;
					} else {
						$value = 'subscriber';
					}
					break;
				case 'ID':
					if ( false !== $updated_id ) {
						$value = $updated_id;
					} else {
						$value = '';
					}
					break;
			}
			$data[ $header ] = $value;
		}
		$this->csv_data = $data;
	}

	/**
	 * CSV file headers we allow/use when testing
	 *
	 * @return string[]
	 */
	private function fixture_csv_header() {
		return array(
			'ID',
			'user_login',
			'user_email',
			'user_pass',
			'first_name',
			'last_name',
			'display_name',
			'role',
			'custom_usermeta_1',
			'custom_usermeta_2',
			'membership_id',
			'membership_status',
			'membership_startdate',
			'membership_enddate',
			'membership_initial_payment',
			'membership_billing_amount',
			'membership_cycle_number',
			'membership_cycle_period',
			'membership_billing_limit',
			'membership_gateway',
			'membership_subscription_transaction_id',
			'membership_payment_transaction_id',
			'membership_code_id',
			'membership_affiliate_id',
			'membership_trial_amount',
			'pmpro_bfirstname',
			'pmpro_blastname',
			'pmpro_baddress1',
			'pmpro_baddress2',
			'pmpro_bcity',
			'pmpro_bzipcode',
			'pmpro_bstate',
			'pmpro_bcountry',
			'pmpro_bemail',
			'pmpro_bphone',
			'pmprosm_sponsor',
			'pmprosm_seats',
		);
	}

	/**
	 * Return the replaceable CSV body content (file content)
	 *
	 * @return array
	 */
	private function fixture_mock_data() {
		return array(
			null,
			null,
			null,
			'',
			null,
			null,
			null,
			null,
			'',
			'',
			'2',
			'active',
			'10-10-2021 13:00:31',
			'10-10-2022 13:00:30',
			'30.00',
			'',
			'',
			'',
			'',
			'stripe',
			'',
			'',
			'',
			'',
			'',
			'Joe',
			null,
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
		);
	}

	/**
	 * Shared execution for testing Import_User::import() across scenarios
	 *
	 * @param bool   $allow_update We can/can not update existing user(s)
	 * @param int    $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int    $expected_id The ID value we expect to the resulting WP_User class to contain
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @return void
	 */
	private function run_import_function( $allow_update, $disable_hashing, $expected_id = null, $expected_email = null, $expected_login = null ) {

		try {
			$this->variables->set( 'update_id', $allow_update );
			$this->variables->set( 'update_users', $allow_update );
			$this->variables->set( 'password_hashing_disabled', $disable_hashing );
		} catch ( InvalidProperty $e ) {
			$this->fail( 'Should not trigger the InvalidProperty exception' );
		}

		try {
			$result = $this->import->maybe_add_or_update( $this->csv_data, $this->csv_data, array_keys( $this->csv_data ) );
		} catch ( InvalidProperty $e ) {
			$this->fail( 'Should not trigger the InvalidProperty exception' );
		}

		$real_user = new WP_User( (int) $result );
		self::assertSame( $expected_id, $real_user->ID, 'Wrong WP_User->ID value' );
		self::assertSame( $expected_email, $real_user->user_email, 'Wrong WP_User->user_email value' );
		self::assertSame( $expected_login, $real_user->user_login, 'Wrong WP_User->user_login value' );
		if ( null !== $result ) {
			self::assertContains( 'subscriber', $real_user->roles, 'WP_User->roles should include "subscriber"!' );
		}

		// Verify that the password is as expected
		if ( ! empty( $this->csv_data['user_pass'] ) && null !== $result ) {
			if ( true === $disable_hashing ) {
				self::assertSame(
					$this->csv_data['user_pass'],
					$real_user->user_pass,
					'Error: Unexpected password when test used a pre-hashed password'
				);
			} else {
				self::assertTrue(
					wp_check_password( $this->csv_data['user_pass'], $real_user->user_pass, $real_user->ID ),
					"Error: Unexpected password when test included plaintext password. '{$this->csv_data['user_pass']}' -> '{$real_user->user_pass}'"
				);
			}
		}
	}

	/**
	 * Test for the load_actions() function
	 *
	 * @throws \Exception
	 *
	 * @test
	 */
	public function it_should_load_filter_handlers() {

		$mocked_user_present_validator = $this->makeEmpty( User_Present::class );
		$import_user                   = new Import_User(
			$this->variables,
			$this->errorlog,
			$mocked_user_present_validator
		);

		$import_user->load_actions();
		self::assertNotFalse( has_filter( 'e20r_import_usermeta' ) );
		self::assertNotFalse( has_filter( 'e20r_import_wp_user_data' ) );
	}

	/**
	 * Attempt to import, but "fail" (bail or exit) and return null
	 *
	 * @param string[] $user_data The user data being imported
	 * @param string[] $meta The user metadata being imported
	 * @param string[] $import_headers The column header names being imported
	 * @param bool|string $update_id Whether to return true or false from Variables::get( 'update_id' )
	 * @param bool $allow_update Whether we allow updates of the user(s)
	 * @param bool $user_exists User exists (or not)
	 * @param bool $disable_hashing Set the password in the user-data to be pre-hashed or not
	 * @param bool $break_globals Do we reset the $e20r_import_* global variables (not arrays)
	 * @param int|null $user_line_no Line to read when mocking a WP_User instance to return by Import_User::find_user() method
	 * @param \Exception|null $update_id_exception The exception to return when calling Import_User::update_user_id()
	 * @param string $error_key The key we're looking for in the $e20r_import_err array
	 *
	 * @return void
	 * @throws \E20R\Exceptions\CannotUpdateDB Thrown if the underlying DB cannot be updated (if updating user's ID)
	 *
	 * @test
	 * @dataProvider fixture_returns_null_when_imported
	 */
	public function it_should_not_import_and_return_null( $user_data, $meta, $import_headers, $update_id, $allow_update, $user_exists, $disable_hashing, $break_globals, $user_line_no, $update_id_exception, $error_key ) {

		global $e20r_import_err;
		global $e20r_import_warn;
		global $active_line_number;

		$active_line_number = 1000;
		$m_wp_user          = false;

		if ( $break_globals ) {
			$e20r_import_warn = null;
			$e20r_import_err  = null;
		}

		if ( null !== $user_line_no ) {
			$m_wp_user = $this->fixture_create_wp_user( $user_line_no );
		}

		$m_variables = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $name ) use ( $allow_update, $update_id, $disable_hashing ) {
					if ( 'update_users' === $name ) {
						return $allow_update;
					}
					if ( 'display_errors' === $name ) {
						return array();
					}

					if ( 'update_id' === $name ) {
						if ( 'throw' === $update_id ) {
							throw new InvalidProperty();
						}
						return $update_id;

					}

					if ( 'site_id' === $name ) {
						return 0;
					}

					if ( 'password_hashing_disabled' === $name ) {
						return $disable_hashing;
					}
					return null;
				},
			)
		);

		$m_user_present = $this->constructEmptyExcept(
			User_Present::class,
			'status_msg',
			array( $m_variables, $this->errorlog, $this->wp_error ),
			array(
				'validate' => function( $data, $can_update ) use ( $user_exists ) {
					$this->errorlog->debug( 'Value for user_exists is: ' . ( $user_exists ? 'True' : 'False' ) );
					return $user_exists;
				},
			)
		);

		$m_generate_passwd = $this->constructEmptyExcept(
			Generate_Password::class,
			'status_msg',
			array( $m_variables, $this->errorlog, $this->wp_error ),
			array(
				'validate' => false,
			)
		);

		$import = $this->constructEmptyExcept(
			Import_User::class,
			'maybe_add_or_update',
			array( $m_variables, $this->errorlog, $m_user_present, $m_generate_passwd, $this->time, $this->date ),
			array(
				'find_user'                              => function( $data ) use ( $user_exists, $m_wp_user ) {
					if ( false === $user_exists ) {
						return false;
					}
					return $m_wp_user;
				},
				'update_user_id'                         => function( $wp_user, $u_data ) use ( $update_id_exception ) {
					if ( null !== $update_id_exception ) {
						throw $update_id_exception;
					}
				},
				'insert_or_update_disabled_hashing_user' => function() {
					$error = $this->wp_error;
					$error->add( 1000, 'Error while updating or inserting new user' );
					return $error;
				},
			)
		);

		try {
			$result = $import->maybe_add_or_update( $user_data, $meta, $import_headers );
		} catch ( InvalidProperty $e ) {
			$this->fail( 'Should not trigger the InvalidProperty exception' );
		}
		self::assertNull( $result );
		self::assertArrayHasKey( $error_key, ( $e20r_import_warn + $e20r_import_err ) );
	}

	/**
	 * Fixture for the it_should_not_import_and_return_null() method
	 *
	 * @return array[]
	 */
	public function fixture_returns_null_when_imported() {
		return array(
			//     user_data, meta, headers, update_id, user_update, user_exists, disable_hashing, break_globals, user_line_no, update_id_exception, error_key
			array( array(), array(), array(), 'throw', false, false, false, true, null, null, 'startup_error_1000' ), // Exit on first bail-out (wrong variables() property) (L: 178)
			array( array(), array(), array(), false, false, false, false, true, null, null, 'warn_userdata_1000' ), // Exit on second bail-out (empty user_data) (L: 188)
			array(
				// Return null because we have a user but can't update it (L: 209)
				array(
					'ID'         => 1,
					'user_email' => 'user_exists@example.org',
					'user_login' => 'user_exists',
				),
				array(),
				array(),
				false,
				false,
				Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED,
				false,
				true,
				null,
				null,
				'warn_cannot_update_1000',
			),
			array(
				// Exit because user was found, can update ID ( 'update_id' == true) and found user's ID doesn't match import data's ID ( 5 vs 65536) throwing UserIDAlreadyExist exception (L: 243)
				array(
					'ID'         => 5,
					'user_email' => 'user_exists@example.org',
					'user_login' => 'user_exists',
				),
				array(),
				array(),
				true,
				false,
				true, // user_exists
				false,
				true,
				2,
				new UserIDAlreadyExists(),
				'existing_user_1000',
			),
			array(
				// Exit because user was found, can update ID ( 'update_id' == true) and found user's ID doesn't match import data's ID ( 5 vs 1023) throwing CannotUpdateDB exception (L: 243)
				array(
					'ID'         => 6,
					'user_email' => 'user_exists@example.org',
					'user_login' => 'user_exists',
				),
				array(),
				array(),
				true,
				false,
				true,
				false,
				true,
				1,
				new CannotUpdateDB(),
				'user_db_upd_1000',
			),
			array(
				// Exit because user was found, can update ID ( 'update_id' == false) and found user's ID doesn't match import data's ID ( 6 vs 1023) throwing CannotUpdateDB exception (L: 262)
				array(
					'ID'         => 6,
					'user_email' => 'user_exists@example.org',
					'user_login' => 'user_exists',
				),
				array(),
				array(),
				false,
				false,
				true,
				false,
				true,
				2,
				null,
				'warn_id_mismatch_1000',
			),
			array(
				/// Exit because we found the user from the ID in the CSV file, we can't , -- TODO -- (L: 316)
				array(
					'ID'         => 65536,
					'user_email' => 'fake_user@example.com',
					'user_login' => 'fake_user',
				),
				array(),
				array(),
				false,
				false,
				true,
				false,
				true,
				1,
				null,
				'warn_not_imported_1000',
			),
			//     user_data, meta, headers, update_id, user_update, user_exists, disable_hashing, break_globals, user_line_no, update_id_exception, error_key
			array(
				// Exit because wp_insert_user, wp_update_user or Import_User::insert_or_update_disabled_hashing_user returns a WP_Error object (L: 323)
				array(
					'ID'         => 4,
					'user_email' => 'user_exists@example.org',
					'user_login' => 'user_exists',
				),
				array(),
				array(),
				true,
				false,
				true,
				true,
				true,
				1,
				null,
				'error_importing_1000',
			),
		);
	}

	/**
	 * Generate a mocked WP_User object with data from the integrations/data/fixture_user_data.csv file
	 *
	 * @param int $line_no The line to read from the CSV file
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|WP_User
	 * @throws \Exception
	 */
	private function fixture_create_wp_user( $line_no ) {
		$data                        = new Manage_Test_Data();
		list( $headers, $user_data ) = $data->read_line_from_csv( $line_no, $this->fixture_user_csv );
		$m_user                      = new WP_User();

		foreach ( $user_data as $property => $value ) {
			$m_user->{$property} = $value;
		}

		return $m_user;
	}

	/**
	 * Test that the deprecation messages are triggered (when deprecation is enabled)
	 *
	 * @param string $action_name The name of the deprecated action we're testing
	 * @param string|null $expected_message The expected error/deprecation message
	 *
	 * @return void
	 * @throws \Exception
	 *
	 * @test
	 * @dataProvider fixture_deprecated_actions
	 */
	public function it_should_print_deprecated_messages( $action_name, $expected_message ) {

		global $e20r_test_result_msg;
		$e20r_test_result_msg = null;

		$mock_errlog = $this->makeEmpty(
			Error_Log::class,
			array(
				'add_error_msg' => function( $message, $severity ) {
					global $e20r_test_result_msg;
					$e20r_test_result_msg = $message;

					self::assertSame( 'warning', $severity );
				},
				'debug'         => function( $message ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'Mocked: ' . $message );
				},
			)
		);

		$m_present = $this->makeEmpty( User_Present::class );
		$m_passwd  = $this->makeEmpty( Generate_Password::class );
		$m_vars    = $this->makeEmpty( Variables::class );
		$m_time    = $this->makeEmpty( Time::class );
		$m_date    = $this->makeEmpty( Date_Format::class );

		$import_user = new Import_User( $m_vars, $mock_errlog, $m_present, $m_passwd, $m_time, $m_date );
		// Configure so we can test and then trigger the actions
		add_action( $action_name, array( $import_user, 'deprecated_action' ), -1, 2 );
		do_action( $action_name, array(), array() );

		if ( in_array( $action_name, array( 'pmp_im_pre_member_import', 'is_iu_pre_user_import' ), true ) ) {
			self::assertStringContainsString( $action_name, $e20r_test_result_msg );
		}
		self::assertSame( $e20r_test_result_msg, $expected_message );
	}

	/**
	 * Fixture for the Import_User_UnitTest::it_should_print_messages() tests
	 *
	 * @return array[]
	 */
	public function fixture_deprecated_actions() {
		return array(
			array( 'pmp_im_pre_member_import', esc_attr__( 'The "pmp_im_pre_member_import" action has been deprecated and will be removed. Please hook your action handler(s) to the "e20r_before_user_import" action instead.' ) ),
			array( 'is_iu_pre_user_import', esc_attr__( 'The "is_iu_pre_user_import" action has been deprecated and will be removed. Please hook your action handler(s) to the "e20r_before_user_import" action instead.' ) ),
			array( 'any_other_action_name', null ),
		);
	}

	/**
	 * @param string $setting_name Name of the setting that will trigger the exception
	 * @param int $line_no Faked line number to use
	 *
	 * @return void
	 * @throws InvalidProperty
	 * @throws \E20R\Exceptions\CannotUpdateDB
	 *
	 * @test
	 * @dataProvider fixture_trigger_invalid_property
	 */
	public function it_triggers_invalid_property_exception_and_returns_null( $setting_name, $line_no ) {

		global $e20r_import_err;
		global $active_line_number;

		$active_line_number = $line_no;

		$m_variables = Mockery::mock( Variables::class )->makePartial();
		$m_variables->expects()
			->get( $setting_name )
			->once()
			->andThrow( InvalidProperty::class, "Error: The requested parameter '{$setting_name}' does not exist!" );

		$import = $this->constructEmptyExcept(
			Import_User::class,
			'maybe_add_or_update',
			array( $m_variables, $this->errorlog, $this->presence, $this->generate_passwd, $this->time, $this->date )
		);
		$result = $import->maybe_add_or_update( array(), array(), array() );

		self::assertNull( $result );
		self::assertArrayHasKey( "startup_error_{$line_no}", $e20r_import_err );
		self::assertSame(
			"Error: The requested parameter '{$setting_name}' does not exist!",
			$e20r_import_err[ "startup_error_{$line_no}" ]->get_error_message()
		);
	}

	/**
	 * Fixture for it_triggers_invalid_property_exception_and_returns_null()
	 *
	 * @return \string[][]
	 */
	public function fixture_trigger_invalid_property() {
		return array(
			array( 'display_errors', 1 ),
			array( 'update_users', 1023 ),
			array( 'update_id', 65535 ),
			array( 'site_id', 1000 ),
		);
	}
}
