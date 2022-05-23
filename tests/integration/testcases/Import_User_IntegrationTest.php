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

use WP_Error;
use stdClass;
use MemberOrder;
use WP_User;

use function fixture_read_from_user_csv;
use function fixture_read_from_meta_csv;

class Import_User_IntegrationTest extends WPTestCase {

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
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp() : void {
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
	 * Test of the happy-path when importing a user who doesn't exist in the DB already
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param bool $clear_user  Whether we should attempt to delete any existing user record before importing
	 * @param int  $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int  $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int  $expected Result we expect to see from the test execution
	 *
	 * @dataProvider fixture_create_user_import
	 * @test
	 */
	public function it_should_create_new_user( $allow_update, $clear_user, $user_line, $meta_line, $expected ) {

		$meta_headers = array();
		$data_headers = array();
		$import_data  = array();
		$import_meta  = array();

		// Read from one of the test file(s)
		if ( null !== $user_line ) {
			list( $data_headers, $import_data ) = fixture_read_from_user_csv( $user_line );
		}

		if ( null !== $meta_line ) {
			list( $meta_headers, $import_meta ) = fixture_read_from_meta_csv( $meta_line );
		}

		$this->variables->set( 'update_users', $allow_update );
		$this->fixture_maybe_delete_user( $clear_user );

		$import_user = new Import_User( $this->variables, $this->errorlog );

		try {
			$result = $import_user->import( $import_data, $import_meta, ( $data_headers + $meta_headers ) );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		self::assertSame( $expected, $result );
		$real_user = new WP_User( $result );
		self::assertSame( $expected, $real_user->ID );
		self::assertSame( $import_data['user_email'], $real_user->user_email );
		self::assertSame( $import_data['user_login'], $real_user->user_login );
	}

	/**
	 * Fixture generator for the it_should_create_new_user() test method
	 *
	 * @return array
	 */
	public function fixture_create_user_import() {
		return array(
			// allow_update, clear_user, user_line, meta_line, expected
			array( false, false, 0, 0, 2 ),
			array( false, false, 2, 2, 3 ),
		);
	}

	/**
	 * Maybe delete pre-existing users who may have been added by another test
	 *
	 * @param bool       $clear_user Whether to delete a pre-existing user record (or not)
	 * @param array|null $import_data The wp_users data to identify and delete the user (if we're supposed to delete them)
	 *
	 * @return void
	 */
	private function fixture_maybe_delete_user( $clear_user = false, $import_data = null ) {
		// Let us test with/without deleting existing users
		if ( true === $clear_user && null !== $import_data ) {
			$user_to_delete = null;
			$id_fields      = array(
				'ID'         => 'ID',
				'user_login' => 'login',
				'user_email' => 'email',
			);

			foreach ( $id_fields as $field_name => $user_by_field ) {
				$this->errorlog->debug( "Attempting to locate user from {$field_name}" );
				if ( isset( $user_data[ $field_name ] ) && ! empty( $user_data[ $field_name ] ) ) {
					$this->errorlog->debug( "Import has data in {$field_name} column" );
					$user = get_user_by( $user_by_field, $user_data[ $field_name ] );
					if ( ! empty( $user->ID ) ) {
						$this->errorlog->debug( 'Found user to delete: ' . $user->ID );
						break;
					}
				}
			}

			if ( ! empty( $user_to_delete ) ) {
				$this->errorlog->debug( "Removing a pre-existing user with ID {$user_to_delete->get('ID')} from the WP database" );
				wp_delete_user( $user_to_delete->ID );
			}
		}
	}
	/**
	 * Test of the happy-path when importing a user who exist in the DB already _and_ we want to update them
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param bool $clear_user  Whether we should attempt to delete any existing user record before importing
	 * @param int  $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int  $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int  $expected Result we expect to see from the test execution
	 *
	 * @dataProvider fixture_update_user_import
	 * @test
	 */
	public function it_should_update_user( $allow_update, $clear_user, $user_line, $meta_line, $expected ) {

		$meta_headers = array();
		$data_headers = array();
		$import_data  = array();
		$import_meta  = array();

		// Read from one of the test file(s)
		if ( null !== $user_line ) {
			list( $data_headers, $import_data ) = fixture_read_from_user_csv( $user_line );
		}

		if ( null !== $meta_line ) {
			list( $meta_headers, $import_meta ) = fixture_read_from_meta_csv( $meta_line );
		}

		try {
			$this->variables->set( 'update_id', $allow_update );
			$this->variables->set( 'update_users', $allow_update );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		$this->fixture_maybe_delete_user( $clear_user, $import_data );
		$this->fixture_create_user_to_update( $import_data );

		$import_user = new Import_User( $this->variables, $this->errorlog );

		try {
			$result = $import_user->import( $import_data, $import_meta, ( $data_headers + $meta_headers ) );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		self::assertSame( $expected, $result );
		$real_user = new WP_User( $result );
		self::assertSame( $expected, $real_user->ID );
		self::assertSame( $import_data['user_email'], $real_user->user_email );
		self::assertSame( $import_data['user_login'], $real_user->user_login );
	}

	/**
	 * Fixture generator for the it_should_update_user() test method
	 *
	 * @return array
	 */
	public function fixture_update_user_import() {
		return array(
			// allow_update, clear_user, user_line, meta_line, expected
			array( true, false, 0, 0, 4 ),
			array( true, false, 1, 1, 1002 ),
			array( true, false, 2, 2, 1003 ),
			array( true, false, 3, 3, 1004 ),
		);
	}

	/**
	 * Create a user record we can update
	 *
	 * @param array $import_data The WP_User data to use
	 *
	 * @return int
	 */
	private function fixture_create_user_to_update( $import_data ) {

		$user_id      = 0;
		$my_user_data = $import_data;

		if ( ! empty( $my_user_data['ID'] ) ) {
			$user_id = $my_user_data['ID'];
			unset( $my_user_data['ID'] );
		}
		$user_id = wp_insert_user( $my_user_data );

		if ( ! is_wp_error( $user_id ) ) {
			$this->errorlog->debug( "Created user to update. Has ID: {$user_id}" );
			return $user_id;
		}

		$this->fail( 'Error attempting to create test user to update!' );
	}

	/**
	 * Create mocked WP_User objects
	 *
	 * @param int $count The number of mocked WP_User objects to create and return
	 *
	 * @return array
	 * @throws \Exception Thrown if we're unable to construct the mock WP_User object
	 */
	private function create_mocked_wp_users( $count = 1 ) {
		$user_list = array();
		$base_id   = 1000;

		for ( $i = 0; $i < $count; $i++ ) {
			$id = ( $base_id + $i );

			if ( class_exists( '\WP_User' ) ) {
				$user = $this->construct(
					WP_User::class,
					array( $id )
				);
			} else {
				$user     = new stdClass();
				$user->ID = $id;
			}

			$user->user_firstname   = 'Test';
			$user->first_name       = $user->user_firstname;
			$user->user_lastname    = "User # {$id}";
			$user->last_name        = $user->user_lastname;
			$user->user_description = "{$user->user_firstname} {$user->user_lastname}";
			$user->display_name     = "{$user->user_firstname} {$user->user_lastname}";
			$user->user_email       = "test_{$id}@localhost.local";

			if ( $count > 1 ) {
				$user_list[] = $user;
			} else {
				$user_list = $user;
			}
		}

		return $user_list;
	}
}
