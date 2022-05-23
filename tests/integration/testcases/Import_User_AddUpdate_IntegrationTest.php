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
use stdClass;
use MemberOrder;
use WP_User;

use function fixture_read_from_user_csv;
use function fixture_read_from_meta_csv;

class Import_User_AddUpdate_IntegrationTest extends WPTestCase {

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
	 * Codeception tearDown() method
	 *
	 * @return void
	 */
	public function tearDown() : void {
		parent::tearDown();
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
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param bool $clear_user  Whether we should attempt to delete any existing user record before importing
	 * @param int  $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int  $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int  $expected Result we expect to see from the test execution
	 *
	 * @dataProvider fixture_users_to_add_and_update
	 * @test
	 */
	public function it_should_add_new_users_and_skip_existing_ones( $allow_update, $clear_user, $user_line, $meta_line, $expected ) {
		$meta_headers = array();
		$data_headers = array();
		$import_data  = array();
		$import_meta  = array();

		$this->errorlog->debug( 'Make sure the expected DB tables exist' );
		$this->test_data->tables_exist();

		$this->errorlog->debug( 'Adding user records we need for tests' );
		// Insert all membership level data
		$this->test_data->set_line();
		$this->test_data->insert_level_data();
		$this->test_data->set_line( $user_line );
		$this->test_data->insert_user_records();
		$this->test_data->insert_member_data();

		// Read from one of the test file(s)
		if ( null !== $user_line ) {
			list( $data_headers, $import_data ) = fixture_read_from_user_csv( $user_line );
		}

		if ( null !== $meta_line ) {
			list( $meta_headers, $import_meta ) = fixture_read_from_meta_csv( $meta_line );
		}

		try {
			$this->variables->set( 'update_users', $allow_update );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		$import_user = new Import_User( $this->variables, $this->errorlog );

		try {
			$result = $import_user->import( $import_data, $import_meta, ( $data_headers + $meta_headers ) );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		self::assertSame( $expected, $result );
		$real_user = new WP_User( $result );
		error_log( "User info for ID {$result} " . print_r( $real_user, true ) );
		self::assertSame( $expected, $real_user->ID );
		self::assertSame( $import_data['user_email'], $real_user->user_email );
		self::assertSame( $import_data['user_login'], $real_user->user_login );
	}

	public function dummy_get_users() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->users}" );
		error_log( print_r( $results, true ) );
	}

	/**
	 * Fixture for multiple users to add, including repeating users so data will get updated
	 *
	 * @return array|array[]
	 */
	public function fixture_users_to_add_and_update() {
		return array(
			// allow_update, clear_user, user_line, meta_line, expected
			array( false, true, 0, 0, 2 ),
			array( false, true, 2, 2, 3 ),
			array( false, true, 3, 3, 4 ),
			// NOTE: line #4 is actually the same identifying data as for line #0
			array( false, false, 4, 4, null ),
		);
	}
}
