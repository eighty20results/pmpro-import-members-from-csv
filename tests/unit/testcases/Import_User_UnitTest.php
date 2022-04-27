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

namespace E20R\Tests\Unit;

use Codeception\Test\Unit;
use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Data;
use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\PMPro\PMPro;
use E20R\Import_Members\Modules\Users\Create_Password;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Modules\Users\User_Update;
use E20R\Import_Members\Process\Ajax;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Process\Page;
use E20R\Import_Members\Validate\Column_Values\Users_Validation;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Variables;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;

use WP_Error;
use WP_Mock;

use stdClass;
use MemberOrder;
use WP_User;
use function Brain\Monkey\Functions\stubs;

class Import_User_UnitTest extends Unit {
	use MockeryPHPUnitIntegration;

	/**
	 * Mocked Error_Log() class
	 *
	 * @var null|Error_Log
	 */
	private $mocked_errorlog = null;

	/**
	 * Mocked Variables() class
	 *
	 * @var null|Variables
	 */
	private $mocked_variables = null;

	/**
	 * Mocked Import() class
	 *
	 * @var null|Import
	 */
	private $mocked_import = null;

	/**
	 * Mocked PMPro MemberOrder()
	 *
	 * @var null|MemberOrder
	 */
	private $mock_order = null;

	/**
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp() : void {
		parent::setUp();
		WP_Mock::setUp();
		Monkey\setUp();
		$this->load_mocks();
	}

	/**
	 * Codeception tearDown() method
	 *
	 * @return void
	 */
	public function tearDown() : void {
		WP_Mock::tearDown();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 * @throws \Exception
	 */
	public function load_mocks() : void {

		try {
			$this->mocked_errorlog = $this->makeEmpty(
				Error_Log::class,
				array(
					'debug' => function( $msg ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo 'Mocked log: ' . $msg;
					},
				)
			);
		} catch ( \Exception $e ) {
			$this->fail( 'Exception: ' . $e->getMessage() );
		}

		$mocked_variables = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $param ) {
					if ( 'site_id' === $param ) {
						return 0;
					}

					if ( '' === $param ) {

					}
				},
			)
		);

		$mocked_pmpro = $this->makeEmpty(
			PMPro::class
		);

		$mocked_data = $this->makeEmpty(
			Data::class
		);

		$mocked_import_user = $this->makeEmpty(
			Import_User::class
		);

		$mocked_import_member = $this->makeEmpty(
			Import_Member::class
		);

		$mocked_csv = $this->makeEmpty(
			CSV::class
		);

		$mocked_email_templates = $this->makeEmpty(
			Email_Templates::class
		);

		$mocked_validate_data = $this->makeEmpty(
			Validate_Data::class
		);

		$mocked_page = $this->makeEmpty(
			Page::class
		);

		$mocked_ajax = $this->makeEmpty(
			Ajax::class
		);

		//      $this->mocked_import = $this->constructEmpty(
		//          Import::class,
		//          array( $mocked_variables, $mocked_pmpro, $mocked_data, $mocked_import_user, $mocked_import_member, $mocked_csv, $mocked_email_templates, $mocked_validate_data, $mocked_page, $mocked_ajax, $this->mocked_errorlog ),
		//          array(
		//              'get' => function( $key ) use ( $mocked_variables ) {
		//                  if ( 'variables' === $key ) {
		//                      return $mocked_variables;
		//                  }
		//                  if ( 'error_log' === $key ) {
		//                      return $this->mocked_errorlog;
		//                  }
		//
		//                  return null;
		//              },
		//          )
		//      );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {

		/*stubs(
			array(
				'translate'                  => null,
				'esc_attr__'                 => null,
				'esc_attr_x'                 => null,
				'esc_html_e'                 => null,
				'esc_attr_e'                 => null,
				'get_transient'              => '/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
				'esc_url'                    => null,
				'esc_url_raw'                => null,
				'wp_upload_dir'              => function() {
					return array(
						'baseurl' => 'https://localhost:7537/wp-content/uploads/',
						'basedir' => '/var/www/html/wp-content/uploads',
					);
				},
				'register_deactivation_hook' => '__return_true',
				'get_option'                 => 'https://www.paypal.com/cgi-bin/webscr',
				'update_option'              => true,
				'plugin_dir_path'            => '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv',
			)
		);*/
	}

	/**
	 * Test of the happy-path when importing a user who doesn't exist in the DB already
	 *
	 * @param int $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int $expected Result we expect to see from the test execution
	 *
	 * @dataProvider fixture_create_user_import
	 * @test
	 */
	public function it_should_create_new_user( $allow_update, $user_line, $meta_line, $expected ) {

		$mocked_variables = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $param ) use ( $allow_update ) {
					if ( 'site_id' === $param ) {
						return 0;
					}

					if ( 'update_users' === $param ) {
						return $allow_update;
					}

					if ( 'password_hashing_disabled' === $param ) {
						return false;
					}
				},
			)
		);

		$mocked_user_present_validator = $this->makeEmptyExcept(
			User_Present::class,
			'status_msg',
			array(
				'validate' => false,
			)
		);

		$mocked_upd_validator = $this->makeEmpty(
			User_Update::class,
			array(
				'validate' => true,
			)
		);

		$mocked_passwd_validator = $this->makeEmpty(
			Create_Password::class,
			array(
				'validate' => true,
			)
		);

		Functions\expect( 'wp_upload_dir' )->andReturnUsing(
			function() {
				return array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads',
				);
			}
		);
		Functions\when( 'wp_insert_user' )->justReturn( 1001 );
		Functions\stubs(
			array(
				'get_option'           => 'https://www.paypal.com/cgi-bin/webscr',
				'update_option'        => true,
				'plugin_dir_path'      => '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv',
				'is_email'             => true,
				'wp_generate_password' => 'dummy_password_string',
				'sanitize_user'        => null,
				'username_exists'      => false, // To ensure we create a new user, not update and existing one
				'sanitize_title'       => null,
				'email_exists'         => false, // To ensure we create a new user, not update and existing one
			)
		);

		$mocked_wp_error = $this->makeEmpty( WP_Error::class );

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

		Functions\when( 'get_user_by' )->alias(
			function( $type, $value ) use ( $import_data ) {

				$id   = null;
				$name = null;

				if ( 'ID' !== $type ) {
					$this->fail( 'We should only call get_user_by() with an ID parameter for this test' );
				}

				if ( ! empty( $import_data['ID'] ) ) {
					$this->fail( 'Should not have an ID of an existing user when calling get_user_by() during this test!' );
				}

				return $this->constructEmpty(
					WP_Error::class,
					array( 'Returning user not found error object as expected' )
				);

			}
		);

		Functions\when( 'wp_update_user' )
			->justReturn(
				function( $userdata ) {
					$this->fail( 'Should never call wp_update_user()' );
				}
			);

		$this->load_stubs();

		$import_user = new Import_User( $mocked_variables, $this->mocked_errorlog, $mocked_upd_validator, $mocked_user_present_validator, $mocked_passwd_validator );
		try {
			$result = $import_user->import( $import_data, $import_meta, ( $data_headers + $meta_headers ), $mocked_wp_error );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		self::assertSame( $expected, $result );
	}

	/**
	 * Fixture generator for the it_should_create_new_user() test method
	 *
	 * @return array
	 */
	public function fixture_create_user_import() {
		return array(
			array(
				false,
				0,
				null,
				array(
					'user_login'   => 'test_user_1',
					'user_email'   => 'test_user_1@example.com',
					'user_pass'    => 'dummy_password_string',
					'first_name'   => 'Thomas',
					'last_name'    => 'Example',
					'display_name' => 'Thomas Example',
					'role'         => 'subscriber',
				),
			),
		);
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
