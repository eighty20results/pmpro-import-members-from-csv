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

use E20R\Tests\Fixtures\Factory\E20R_TestCase;
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;
use E20R\Tests\Fixtures\Factory\E20R_IntegrationTest_Factory_For_PMProLevels;

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
	 * Load WP and PMPro tables with test data
	 *
	 * @var Manage_Test_Data|null
	 */
	private $test_data = null;

	/**
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp() : void {
		parent::setUp();

		$this->test_data = new Manage_Test_Data();

		parent::setUp();
		$this->load_mocks();

		// Insert membership level data
		$this->e20r_factory()->pmprolevels->create_many( 2 );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 */
	public function load_mocks() : void {

		$this->errorlog  = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->variables = new Variables( $this->errorlog );
		$this->import    = new Import_User( $this->variables, $this->errorlog );
		$this->test_data = new Manage_Test_Data( $this, null, $this->errorlog );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {
	}

	// FIXME: Add test for an existing user record with a new password and check the $e20r_import_warn status

	/**
	 * Test of the happy-path when importing a user who doesn't exist in the DB already
	 *
	 * @param bool   $allow_update We can/can not update existing user(s)
	 * @param bool   $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int    $csv_line_to_read The CSV file data being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int    $expected_id Result we expect to see from the test execution
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @dataProvider fixture_add_user_import
	 * @test
	 */
	public function it_should_add_new_user( $allow_update, $disable_hashing, $csv_line_to_read, $expected_id, $expected_email, $expected_login ) {
		// Just execute the import test
		$this->run_import_function(
			$allow_update,
			$disable_hashing,
			$csv_line_to_read,
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
	public function fixture_add_user_import() {
		return array(
			// allow_update, disable_hashing, csv_line_to_read, expected_id, expected_email, expected_login
			array( false, false, 0, 2, 'user_1@example.org', 'user_1' ),
			array( false, false, 2, 3, 'user_3@example.org', 'user_3' ),
			array( false, false, 5, 4, 'user_6@example.org', 'user_6' ),
			array( false, true, 6, 5, 'user_7@example.org', 'user_7' ),

		);
	}

	/**
	 * Test import of users but don't allow updating them
	 *
	 * @param bool   $allow_update We can/can not update existing user(s)
	 * @param int    $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int    $users_to_create Fake users to create for this test to function
	 * @param int    $csv_line_to_read The CSV file data being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @dataProvider fixture_users_to_add_and_update
	 * @test
	 */
	public function it_should_add_new_and_disallow_updates( $allow_update, $disable_hashing, $users_to_create, $csv_line_to_read, $expected_email, $expected_login ) {

		if ( false !== $allow_update ) {
			$this->fail( 'Should not allow updating existing user data' );
		}

		if ( false === $expected_login && false === $expected_email ) {
			$args = array();
			foreach ( range( 1, $users_to_create ) as $id ) {
				$args[] = array(
					'user_login' => "user_{$id}",
					'user_email' => "user_{$id}@example.org",
					'ID'         => $id,
				);
			}
			$created_user_ids = $this->factory()->user->create_many( $users_to_create, $args );
		}
		$expected_id = end( $created_user_ids );

		$this->run_import_function(
			$allow_update,
			$disable_hashing,
			$csv_line_to_read,
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
			// allow_update, disable_hashing, users_to_create, csv_line_to_read, expected_email, expected_login
			array( false, false, 1, 0, false, false ), // User already exists, and we can't update them so Import_User::maybe_add_or_update() should return null
			array( false, false, 2, 2, 'user_3@example.org', 'user_3' ), // User doesn't exist so being added
			array( false, false, 3, 3, 'user_4@example.org', 'user_4' ), // User doesn't exist so being added
			array( false, false, 4, 4, false, false ), // User already exists, and we can't update them so Import_User::maybe_add_or_update() should return null
			array( false, false, 7, 7, 'user_8@example.org', 'user_8' ), // User doesn't exist so being added
			array( false, false, 8, 8, 'user_9@example.org', 'user_9' ), // User doesn't exist so being added
		);
	}

	/**
	 * Test of the happy-path when importing a user who exist in the DB already _and_ we want to update them
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param bool $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int  $users_to_create Fake users to create for this test to function
	 * @param int  $csv_line_to_read The CSV file data being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @dataProvider fixture_update_user_import
	 * @test
	 */
	public function it_should_update_user( $allow_update, $disable_hashing, $users_to_create, $csv_line_to_read, $expected_email, $expected_login ) {

		if ( true !== $allow_update ) {
			$this->fail( 'Should allow updating existing user data' );
		}

		// FIXME: Not setting the expected user_login and user_email values!
		$created_user_ids = $this->insert_test_user_data( $users_to_create );
		$expected_id      = end( $created_user_ids );
		$expected_email   = "user_{$expected_id}@example.org";
		$expected_login   = "user_{$expected_id}";
		$this->errorlog->debug( "ID that should be updated during import: {$expected_id} for {$expected_login} and {$expected_email}" );

		$this->run_import_function(
			$allow_update,
			$disable_hashing,
			$csv_line_to_read,
			$expected_id,
			$expected_email,
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
			// allow_update, disable_hashing, users_to_create, csv_line_to_read, expected_email, expected_login
			// FIXME: Not setting the expected user_login and user_email values!
			array( true, false, 1, 0, 'user_1@example.org', 'user_1' ),
			array( true, false, 1, 1, 'user_2@example.org', 'user_2' ),
			array( true, false, 1, 2, 'user_3@example.org', 'user_3' ),
			array( true, false, 1, 3, 'user_4@example.org', 'user_4' ),
			array( true, false, 1, 4, 'user_5@example.org', 'user_5' ), // Un-hashed password
			array( true, true, 1, 5, 'user_6@example.org', 'user_6' ),
			array( true, false, 1, 6, 'user_7@example.org', 'user_7' ),
			array( true, false, 1, 7, 'user_8@example.org', 'user_8' ),
		);
	}

	/**
	 * Insert test data (User) in WP Users table
	 *
	 * @param int $users_to_create Number of fake user records to create/mock
	 *
	 * @return bool|int[]
	 */
	public function insert_test_user_data( $users_to_create = 1 ) {
		// FIXME: Not setting the expected user_login and user_email values!
		$args = array();
		foreach ( range( 1, $users_to_create ) as $id ) {
			$args[] = array(
				'user_login' => "user_{$id}",
				'user_email' => "user_{$id}@example.org",
				'ID'         => $id,
			);
		}

		$existing_user_ids = $this->e20r_factory()->user->create_many( $users_to_create, $args );
		if ( is_wp_error( $existing_user_ids ) ) {
			$this->errorlog->debug( 'Error adding existing user to update: ' . $existing_user_ids->get_error_message() );
			return false;
		}

		return $existing_user_ids;
	}
	/**
	 * Shared execution for testing Import_User::import() across scenarios
	 *
	 * @param bool   $allow_update We can/can not update existing user(s)
	 * @param int    $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int    $csv_line_to_read The data to read and import (line # in the inc/csv_files/user_data.csv file)
	 * @param int    $expected_id The ID value we expect to the resulting WP_User class to contain
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @return void
	 */
	private function run_import_function( $allow_update, $disable_hashing, $csv_line_to_read = null, $expected_id = null, $expected_email = null, $expected_login = null ) {

		$headers     = array();
		$import_data = array();

		if ( null !== $csv_line_to_read ) {
			list( $headers, $import_data ) = $this->test_data->read_line_from_csv( $csv_line_to_read, dirname( E20R_IMPORT_PLUGIN_FILE ) . '/tests/integration/inc/test_data_to_load.csv' );
		}

		try {
			$this->variables->set( 'update_id', $allow_update );
			$this->variables->set( 'update_users', $allow_update );
			$this->variables->set( 'password_hashing_disabled', $disable_hashing );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		try {
			$result = $this->import->maybe_add_or_update( $import_data, $import_data, $headers );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		$real_user = new WP_User( (int) $result );
		self::assertSame( $expected_id, $real_user->ID, 'Wrong WP_User->ID value' );
		self::assertSame( $expected_email, $real_user->user_email, 'Wrong WP_User->user_email value' );
		self::assertSame( $expected_login, $real_user->user_login, 'Wrong WP_User->user_login value' );

		// Verify that the password is as expected
		if ( ! empty( $import_data['user_pass'] ) ) {
			if ( true === $disable_hashing ) {
				self::assertSame(
					$import_data['user_pass'],
					$real_user->user_pass,
					'Error: Unexpected password when test used a pre-hashed password'
				);
			} else {
				self::assertTrue(
					wp_check_password( $import_data['user_pass'], $real_user->user_pass, $real_user->ID ),
					"Error: Unexpected password when test included plaintext password. '{$import_data['user_pass']}' -> '{$real_user->user_pass}'"
				);
			}
		}
	}
}
