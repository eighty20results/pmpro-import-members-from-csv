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

use E20R\Exceptions\CannotUpdateDB;
use E20R\Exceptions\UserIDAlreadyExists;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\Users\Generate_Password;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Validate\Date_Format;
use E20R\Import_Members\Validate\Time;
use E20R\Import_Members\Variables;

use Codeception\Test\Unit;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;

use WP_Error;
use WP_Mock;

use MemberOrder;
use WP_User;

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
	 * Mocked Time() validator class
	 *
	 * @var null|Time $mocked_time_validator
	 */
	private $mocked_time_validator = null;

	/**
	 * Mocked Date_Format() validator class
	 *
	 * @var null|Date_Format $mocked_date_validator
	 */
	private $mocked_date_validator = null;

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
	 * Skipped test of the happy-path when importing a user who doesn't exist in the DB already
	 *
	 * @test
	 */
	public function it_should_create_new_user() {
		$this->markTestSkipped( 'Creating new users should be tested in Import_User_IntegrationTest.php' );
	}

	/**
	 * Skipped test of the happy-path when attempting to update a user
	 *
	 * @test
	 */
	public function it_should_update_user() {
		$this->markTestSkipped( 'Updating user data should be tested in Import_User_IntegrationTest.php' );
	}

	/**
	 * Skipped test validating the insert_or_update_disabled_hashing_user() method
	 *
	 * @return void
	 * @test
	 */
	public function it_should_create_new_user_with_pre_hashed_password() {
		$this->markTestSkipped( 'Updating/Adding users using pre-hashed passwords should be tested in Import_User_IntegrationTest.php' );
	}

	/**
	 * Test the Import_User::update_user_id() method to validate it will throw an expected exception
	 *
	 * @param WP_User|null $user User object
	 * @param array $data Data being fake imported
	 * @param string $exception The exception we expect the function to throw
	 *
	 * @return void
	 *
	 * @dataProvider fixture_fail_update_user_id
	 * @test
	 */
	public function it_should_throw_exception( $user, $user_return, $data, $exception ) {
		Functions\expect( 'get_user_by' )->once()->andReturn( $user_return );

		$m_variables = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $param ) {
					if ( 'update_id' === $param ) {
						return true;
					}
					return null;
				},
			)
		);

		$mocked_time_validator   = $this->makeEmpty( Time::class );
		$mocked_date_validator   = $this->makeEmpty( Date_Format::class );
		$mocked_passwd_validator = $this->makeEmpty( Generate_Password::class );
		$mocked_variables        = $this->makeEmpty( Variables::class );

		$mocked_user_present_validator = $this->constructEmpty(
			User_Present::class,
			array( $mocked_variables, $this->mocked_errorlog, $this->mocked_wp_error ),
			array(
				'validate'   => isset( $import_data['ID'] ) && ! empty( $import_data['ID'] ),
				'status_msg' => null,
			)
		);

		$this->expectException( $exception );
		Functions\stubs(
			array(
				'esc_attr__' => null,
			)
		);
		$import = $this->constructEmptyExcept(
			Import_User::class,
			'update_user_id',
			array( $m_variables, $this->mocked_errorlog, $mocked_user_present_validator, $mocked_passwd_validator, $mocked_time_validator, $mocked_date_validator )
		);
		$import->update_user_id( $user, $data );
	}

	/**
	 * Fixture for the Import_User_UnitTest::it_should_throw_exception() tests
	 *
	 * @return array[]
	 * @throws \Exception
	 */
	public function fixture_fail_update_user_id() {
		$user             = $this->makeEmpty( WP_User::class );
		$user->ID         = 10;
		$user->user_email = 'tester@example.org';

		return array(
			array(
				$user,
				$user,
				array(
					'ID'         => 100,
					'user_email' => 'tester@example.org',
				),
				UserIDAlreadyExists::class,
			),
			array(
				$user,
				false,
				array(
					'ID'         => 100,
					'user_email' => 'tester@example.org',
				),
				CannotUpdateDB::class,
			),
		);
	}

	/**
	 * Test that the deprecation messages are triggered (when deprecation is enabled)
	 *
	 * @param $pmpro_action
	 * @param $import_users_action
	 * @param $expected_message
	 *
	 * @return void
	 * @throws \Exception
	 *
	 * @test
	 * @dataProvider fixture_deprecated_actions
	 */
	public function it_should_print_messages( $pmpro_action, $iu_action, $expected_message ) {

		global $e20r_test_result_msg;
		Functions\stubs(
			array(
				'esc_attr__' => null,
			)
		);
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

		Functions\when( 'doing_action' )
			->alias(
				function( $action_name ) use ( $pmpro_action, $iu_action ) {
					switch ( $action_name ) {
						case 'pmp_im_pre_member_import':
							return $pmpro_action;

						case 'is_iu_pre_user_import':
							return $iu_action;

						default:
							return false;
					}
				}
			);
		$m_present = $this->makeEmpty( User_Present::class );
		$m_passwd  = $this->makeEmpty( Generate_Password::class );
		$m_vars    = $this->makeEmpty( Variables::class );
		$m_time    = $this->makeEmpty( Time::class );
		$m_date    = $this->makeEmpty( Date_Format::class );

		$import_user = $this->constructEmptyExcept(
			Import_User::class,
			'deprecated_action',
			array( $m_vars, $mock_errlog, $m_present, $m_passwd, $m_time, $m_date )
		);
		$import_user->deprecated_action( array(), array() );

		if ( true === $pmpro_action ) {
			self::assertStringContainsString( 'pmp_im_pre_member_import', $e20r_test_result_msg );
		}
		if ( true === $iu_action ) {
			self::assertStringContainsString( 'is_iu_pre_user_import', $e20r_test_result_msg );
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
			array( true, false, 'The "pmp_im_pre_member_import" action has been deprecated and will be removed. Please hook your action handler(s) to the "e20r_before_user_import" action instead.' ),
			array( false, true, 'The "is_iu_pre_user_import" action has been deprecated and will be removed. Please hook your action handler(s) to the "e20r_before_user_import" action instead.' ),
			array( false, false, null ),
		);
	}
}
