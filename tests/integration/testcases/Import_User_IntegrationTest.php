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

use Codeception\TestCase\WPTestCase;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Variables;

use E20R\Tests\Fixtures\Factory\E20R_IntegrationTest_Generator_Sequence;
use E20R\Tests\Fixtures\Factory\E20R_TestCase;
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;
use E20R\Tests\Fixtures\Factory\E20R_IntegrationTest_Factory_For_PMProLevels;

use org\bovigo\vfs\vfsStream;
use WP_Error;
use MemberOrder;
use WP_User;

use function fixture_read_from_user_csv;
use function fixture_read_from_meta_csv;

class Import_User_IntegrationTest extends E20R_TestCase {

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
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->test_data = new Manage_Test_Data();

		parent::setUp();
		$this->load_mocks();

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
	 * Load all class mocks we need (with namespaces)
	 */
	public function load_mocks(): void {

		$this->errorlog  = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->variables = new Variables( $this->errorlog );
		$this->import    = new Import_User( $this->variables, $this->errorlog );
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
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		try {
			$result = $this->import->maybe_add_or_update( $this->csv_data, $this->csv_data, array_keys( $this->csv_data ) );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
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
}
