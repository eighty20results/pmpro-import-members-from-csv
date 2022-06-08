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

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Data;
use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\PMPro\PMPro;
use E20R\Import_Members\Modules\Users\Generate_Password;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Process\Ajax;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Process\Page;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Variables;

use Codeception\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters as BMFilters;

use WP_Error;
use WP_Mock;

use stdClass;
use MemberOrder;
use WP_User;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Actions\did;

use function fixture_read_from_user_csv;
use function fixture_read_from_meta_csv;

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
	 * Mocked Error class for WordPress
	 *
	 * @var null|WP_Error
	 *
	 */
	private $mocked_wp_error = null;

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

		if ( ! defined( 'E20R_IMPORT_PLUGIN_FILE' ) ) {
			define( 'E20R_IMPORT_PLUGIN_FILE', dirname( __FILE__ ) . '/../../../class.pmpro-import-members.php' );
		}

		if ( ! defined( 'E20R_IM_CSV_DELIMITER' ) ) {
			define( 'E20R_IM_CSV_DELIMITER', ',' );
		}
		if ( ! defined( 'E20R_IM_CSV_ESCAPE' ) ) {
			define( 'E20R_IM_CSV_ESCAPE', '\\' );
		}
		if ( ! defined( 'E20R_IM_CSV_ENCLOSURE' ) ) {
			define( 'E20R_IM_CSV_ENCLOSURE', '"' );
		}

		try {
			$this->mocked_errorlog = $this->makeEmpty(
				Error_Log::class,
				array(
					'debug' => function( $msg ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( 'Mocked debug: ' . $msg );

					},
				)
			);
		} catch ( \Exception $e ) {
			$this->fail( 'Exception: ' . $e->getMessage() );
		}

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

		$this->mocked_wp_error = $this->makeEmptyExcept(
			WP_Error::class,
			'add'
		);
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

		Functions\expect( 'wp_upload_dir' )->andReturnUsing(
			function() {
				return array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads',
				);
			}
		);
	}

	/**
	 * Test of the happy-path when importing a user who doesn't exist in the DB already
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param int  $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int  $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int  $expected Result we expect to see from the test execution
	 *
	 * @dataProvider fixture_create_user_import
	 * @test
	 */
	public function it_should_create_new_user( $allow_update, $user_line, $meta_line, $expected ) {

		$mocked_variables        = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $param ) use ( $allow_update ) {
					if ( 'site_id' === $param ) {
						return 0;
					}

					if ( 'update_users' === $param ) {
						return $allow_update;
					}

					if ( 'update_id' === $param ) {
						return $allow_update;
					}

					if ( 'password_hashing_disabled' === $param ) {
						return false;
					}

					return false;
				},
			)
		);
		$mocked_passwd_validator = $this->makeEmpty(
			Generate_Password::class,
			array(
				'validate' => true,
			)
		);

		Functions\expect( 'wp_insert_user' )
			->andReturnUsing(
				function( $data ) use ( $expected ) {
					return $expected;
				}
			)
			->once();

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

		$mocked_user_present_validator = $this->makeEmptyExcept(
			User_Present::class,
			'status_msg',
			array(
				'validate' => isset( $import_data['ID'] ) && ! empty( $import_data['ID'] ),
			)
		);

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
		Actions\expectDone( 'e20r_before_user_import' );
		Actions\expectDone( 'pmp_im_pre_member_import' );
		Actions\expectDone( 'is_iu_pre_user_import' );

		Actions\expectDone( 'e20r_after_user_import' );
		Actions\expectDone( 'pmp_im_post_member_import' );
		Actions\expectDone( 'is_iu_post_user_import' );

		Functions\expect( 'username_exists' )
			->andReturn( isset( $import_data['user_login'] ) );

		Functions\expect( 'email_exists' )
			->andReturn( isset( $import_data['user_email'] ) );

		$import_user = $this->constructEmptyExcept(
			Import_User::class,
			'import',
			array( $mocked_variables, $this->mocked_errorlog, $mocked_user_present_validator, $mocked_passwd_validator ),
			array(
				'insert_or_update_disabled_hashing_user' => function( $user_data ) {
					$this->fail( 'Should not have called insert_or_update_disabled_hashing_user() method during this test!' );
				},
				'find_user'                              => function( $user_data ) use ( $expected, $import_data ) {
					$this->mocked_errorlog->debug( 'Running mocked find_user() method' );

					$user_id = 2;
					if ( ! empty( $import_data['ID'] ) ) {
						$this->mocked_errorlog->debug( 'Setting user ID to the supplied value' );
						$user_id = $import_data['ID'];
					}

					$m_user = $this->constructEmpty(
						WP_User::class,
						array( $user_id )
					);
					$m_user->__set( 'ID', $user_id );
					$m_user->ID = $user_id;
					$m_user->__set( 'user_email', $import_data['user_email'] );
					$m_user->__set( 'user_login', $import_data['user_login'] );
					$m_user->__set( 'user_pass', $import_data['user_pass'] );
					$m_user->__set( 'first_name', $import_data['first_name'] );
					$m_user->__set( 'last_name', $import_data['last_name'] );
					$m_user->__set( 'display_name', $import_data['display_name'] );
					if ( ! empty( $import_data['role'] ) ) {
						$m_user->add_role( $import_data['role'] );
					}

					return $m_user;
				},
			)
		);

		try {
			$result = $import_user->maybe_add_or_update( $import_data, $import_meta, ( $data_headers + $meta_headers ), $this->mocked_wp_error );
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
			// allow_update, user_line, meta_line, expected
			array(
				false,
				0,
				0,
				1001,
			),
			array(
				false,
				2,
				2,
				1002,
			),
		);
	}

	/**
	 * Test of the happy-path when importing a user who exist in the DB already _and_ we want to update them
	 *
	 * @param bool $allow_update We can/can not update existing user(s)
	 * @param int  $user_line The user data to add during the import operation (line # in the inc/csv_files/user_data.csv file)
	 * @param int  $meta_line The metadata for the user being imported (line # in the inc/csv_files/meta_data.csv file)
	 * @param int  $expected Result we expect to see from the test execution
	 *
	 * @dataProvider fixture_update_user_import
	 * @test
	 */
	public function it_should_update_user( $allow_update, $user_line, $meta_line, $expected ) {
		$mocked_variables        = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $param ) use ( $allow_update ) {
					if ( 'site_id' === $param ) {
						return 0;
					}

					if ( 'update_users' === $param ) {
						return $allow_update;
					}

					if ( 'update_id' === $param ) {
						return $allow_update;
					}

					if ( 'password_hashing_disabled' === $param ) {
						return false;
					}

					return false;
				},
			)
		);
		$mocked_passwd_validator = $this->makeEmpty(
			Generate_Password::class,
			array(
				'validate' => true,
			)
		);

		Functions\expect( 'wp_update_user' )
			->andReturnUsing(
				function( $data ) use ( $expected ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
					return $expected;
				}
			);

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

		$mocked_user_present_validator = $this->makeEmptyExcept(
			User_Present::class,
			'status_msg',
			array(
				'validate' => true,
			)
		);

		Functions\when( 'get_user_by' )->alias(
			function( $field, $data ) use ( $import_data ) {
				$this->mocked_errorlog->debug( "Using: {$field}" );
				$m_user = $this->constructEmpty(
					WP_User::class,
					array( $import_data['ID'] )
				);
				$m_user->__set( 'ID', $import_data['ID'] );
				$m_user->ID = $import_data['ID'];
				$m_user->__set( 'user_email', $import_data['user_email'] );
				$m_user->__set( 'user_login', $import_data['user_login'] );
				$m_user->__set( 'user_pass', $import_data['user_pass'] );
				$m_user->__set( 'first_name', $import_data['first_name'] );
				$m_user->__set( 'last_name', $import_data['last_name'] );
				$m_user->__set( 'display_name', $import_data['display_name'] );
				if ( ! empty( $import_data['role'] ) ) {
					$m_user->add_role( $import_data['role'] );
				}
				return $m_user;
			}
		);

		Functions\when( 'wp_insert_user' )
			->justReturn(
				function( $userdata ) {
					$this->fail( 'Should never call wp_insert_user()' );
				}
			);

		$this->load_stubs();
		Actions\expectDone( 'e20r_before_user_import' )->once();
		Actions\expectDone( 'pmp_im_pre_member_import' )->once();
		Actions\expectDone( 'is_iu_pre_user_import' )->once();

		Actions\expectDone( 'e20r_after_user_import' )->once();
		Actions\expectDone( 'pmp_im_post_member_import' )->once();
		Actions\expectDone( 'is_iu_post_user_import' )->once();

		Functions\expect( 'username_exists' )
			->andReturn( isset( $import_data['user_login'] ) );

		Functions\expect( 'email_exists' )
			->andReturn( isset( $import_data['user_email'] ) );

		$import_user = $this->constructEmptyExcept(
			Import_User::class,
			'import',
			array( $mocked_variables, $this->mocked_errorlog, $mocked_user_present_validator, $mocked_passwd_validator ),
			array(
				'insert_or_update_disabled_hashing_user' => function( $user_data ) {
					$this->fail( 'Should not have called insert_or_update_disabled_hashing_user() method during this test!' );
				},
				'find_user'                              => function( $user_data ) use ( $expected, $import_data ) {
					$this->mocked_errorlog->debug( 'Running mocked find_user() method' );

					$user_id = 2;
					if ( ! empty( $import_data['ID'] ) ) {
						$this->mocked_errorlog->debug( 'Setting user ID to the supplied value' );
						$user_id = $import_data['ID'];
					}

					$m_user = $this->constructEmpty(
						WP_User::class,
						array( $user_id )
					);
					$m_user->__set( 'ID', $user_id );
					$m_user->ID = $user_id;
					$m_user->__set( 'user_email', $import_data['user_email'] );
					$m_user->__set( 'user_login', $import_data['user_login'] );
					$m_user->__set( 'user_pass', $import_data['user_pass'] );
					$m_user->__set( 'first_name', $import_data['first_name'] );
					$m_user->__set( 'last_name', $import_data['last_name'] );
					$m_user->__set( 'display_name', $import_data['display_name'] );
					if ( ! empty( $import_data['role'] ) ) {
						$m_user->add_role( $import_data['role'] );
					}
					return $m_user;
				},
			)
		);

		try {
			$result = $import_user->maybe_add_or_update( $import_data, $import_meta, ( $data_headers + $meta_headers ), $this->mocked_wp_error );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not trigger the InvalidSettingsKey exception' );
		}

		self::assertSame( $expected, $result );
	}

	/**
	 * Fixture generator for the it_should_update_user() test method
	 *
	 * @return array
	 */
	public function fixture_update_user_import() {
		return array(
			array(
				true,
				1,
				1,
				1002,
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

	/**
	 * Unit test validates the insert_or_update_disabled_hashing_user() method (Skipped on purpose)
	 *
	 * @return void
	 * @test
	 */
	public function it_should_create_new_user_with_pre_hashed_password() {
		$this->markTestSkipped( 'Must use the integration test suite for the Import_User::insert_or_update_disabled_hashing_user() method!' );
	}

	/**
	 * Unit test for the load_actions() function
	 *
	 * @throws \Exception
	 *
	 * @test
	 */
	public function it_should_load_filter_handlers() {
		$mocked_variables              = $this->makeEmpty( Variables::class );
		$mocked_passwd_validator       = $this->makeEmpty( Generate_Password::class );
		$mocked_user_present_validator = $this->makeEmpty( User_Present::class );

		$import_user = $this->constructEmptyExcept(
			Import_User::class,
			'load_hooks',
			array( $mocked_variables, $this->mocked_errorlog, $mocked_user_present_validator, $mocked_passwd_validator )
		);

		BMFilters\expectAdded( 'e20r_import_usermeta' )
			->with( Mockery::contains( array( $import_user, 'import_usermeta' ) ) )
			->once();
		BMFilters\expectAdded( 'e20r_import_wp_user_data' )
			->with( Mockery::contains( array( $import_user, 'maybe_add_or_update' ) ) )
			->once();

		$import_user->load_actions();
	}
}
