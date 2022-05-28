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
 * @package E20R\Tests\Unit\Email_Templates_UnitTest
 */

namespace E20R\Tests\Integration;

use Codeception\TestCase\WPTestCase;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Variables;

use E20R\Tests\Integration\Fixtures\Manage_Test_Data;

use WP_Error;
use MemberOrder;
use WP_User;

use function fixture_read_from_user_csv;
use function fixture_read_from_meta_csv;

class Import_User_AddOrSkip_IntegrationTest extends WPTestCase {

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
	 * Default Import() class
	 *
	 * @var null|Import
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
		$this->test_data = new Manage_Test_Data();

		parent::setUp();
		$this->load_mocks();
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 */
	public function load_mocks() : void {

		$this->errorlog  = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->variables = new Variables( $this->errorlog );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {
	}

	/**
	 * Test import of users but don't allow updating them
	 *
	 * @param bool   $allow_update We can/can not update existing user(s)
	 * @param bool   $clear_user  Whether we should attempt to delete any existing user record before importing
	 * @param int    $disable_hashing Update 'password_hashing_disabled' setting to this value
	 * @param int    $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int    $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int    $expected_id The ID value we expect to the resulting WP_User class to contain
	 * @param string $expected_email The email address we expect the resulting WP_User class to contain
	 * @param string $expected_login The login name we expect the resulting WP_User class to contain
	 *
	 * @dataProvider fixture_users_to_add_and_update
	 * @test
	 */
	public function it_should_add_new_users_and_skip_existing_ones( $allow_update, $clear_user, $disable_hashing, $user_line, $meta_line, $expected_id, $expected_email, $expected_login ) {
		$meta_headers = array();
		$user_headers = array();
		$import_data  = array();
		$import_meta  = array();

		if ( false !== $allow_update ) {
			$this->fail( 'This test should not set the variable for updating existing user data to true' );
		}

		$this->errorlog->debug( 'Make sure the expected DB tables exist' );
		$this->test_data->tables_exist();

		$this->errorlog->debug( 'Adding user records we need for tests' );
		// Insert all membership level data
		$this->test_data->set_line();
		$this->test_data->insert_level_data();
		$this->test_data->set_line( $user_line );
		$this->test_data->insert_user_records( $user_line );
		$this->test_data->insert_member_data();

		// Read from one of the test file(s)
		if ( null !== $user_line ) {
			list( $user_headers, $import_data ) = fixture_read_from_user_csv( $user_line );
		}

		if ( null !== $meta_line ) {
			list( $meta_headers, $import_meta ) = fixture_read_from_meta_csv( $meta_line );
		}

		try {
			$this->variables->set( 'update_users', $allow_update );
			$this->variables->set( 'password_hashing_disabled', $disable_hashing );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		$import_user = new Import_User( $this->variables, $this->errorlog );

		try {
			$result = $import_user->import( $import_data, $import_meta, ( $user_headers + $meta_headers ) );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		// Make sure the import operation didn't return NULL value so we can get a real user object
		$real_user = new WP_User( $result );

		self::assertSame( $expected_id, $real_user->ID, 'Wrong WP_User->ID value' );
		self::assertSame( $expected_email, $real_user->user_email, 'Wrong WP_User->user_email value' );
		self::assertSame( $expected_login, $real_user->user_login, 'Wrong WP_User->user_login value' );
	}

	/**
	 * Fixture for multiple users to add, including repeating users so data will get updated
	 *
	 * @return array|array[]
	 */
	public function fixture_users_to_add_and_update() {
		return array(
			// allow_update, clear_user, disable_hashing, user_line, meta_line, expected_id, expected_email, expected_login
			array( false, true, false, 0, 0, 0, false, false ), // User already exists, and we can't update them so Import_User::import() should return null
			array( false, true, false, 2, 2, 4, 'olga@owndomain.com', 'olga@owndomain.com' ), // User doesn't exist so being added
			array( false, true, false, 3, 3, 6, 'peter@owndomain.com', 'peter@owndomain.com' ), // User doesn't exist so being added
			array( false, true, false, 4, 4, 0, false, false ), // User already exists, and we can't update them so Import_User::import() should return null
		);
	}
}
