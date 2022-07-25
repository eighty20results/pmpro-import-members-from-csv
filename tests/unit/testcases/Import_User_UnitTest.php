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
use E20R\Exceptions\InvalidProperty;
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
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;

use WP_Error;
use WP_Mock;

use MemberOrder;
use WP_User;
use wpdb;
use function Brain\Monkey\Functions\expect;

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
	public function setUp() : void {
		parent::setUp();
		Monkey\setUp();

		$this->fixture_user_csv = __DIR__ . '/../../_data/csv_files/fixture_user_data.csv';
		$this->load_mocks();
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
						error_log( 'Mocked: ' . $msg );

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

		Functions\expect( 'get_userdata' )->once()->andReturn( $user_return );

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
	 * Tests the Import_User::import_usermeta() method
	 *
	 * @param array $user_meta The supplied user metadata fields with values
	 * @param array $expected The expected key/value pairs being returned from import_usermeta()
	 *
	 * @return void
	 * @throws \Exception
	 *
	 * @test
	 * @dataProvider fixture_user_meta_and_fields
	 */
	public function it_should_append_imported_to_processed_array_keys( $user_meta, $expected ) {
		$m_present = $this->makeEmpty( User_Present::class );
		$m_passwd  = $this->makeEmpty( Generate_Password::class );
		$m_vars    = $this->makeEmpty(
			Variables::class,
			array(
				'get' => $expected,
			)
		);
		$m_time    = $this->makeEmpty( Time::class );
		$m_date    = $this->makeEmpty( Date_Format::class );

		$import_user   = new Import_User( $m_vars, $this->mocked_errorlog, $m_present, $m_passwd, $m_time, $m_date );
		$returned_meta = $import_user->import_usermeta( $user_meta, array(), array() );

		self::assertIsArray( $returned_meta );

		if ( is_array( $user_meta ) ) {
			foreach ( $user_meta as $meta_key => $value ) {
				$has_both_keys =
					in_array( "imported_{$meta_key}", array_keys( $returned_meta ), true ) &&
					in_array( $meta_key, array_keys( $returned_meta ), true );
				self::assertTrue( $has_both_keys );
			}
		}
	}

	/**
	 * User meta field array fixture
	 *
	 * @return array
	 */
	public function fixture_user_meta_and_fields() {
		$pmpro_fields = array(
			'membership_id'                          => null,
			'membership_code_id'                     => null,
			'membership_discount_code'               => null,
			'membership_initial_payment'             => null,
			'membership_billing_amount'              => null,
			'membership_cycle_number'                => null,
			'membership_cycle_period'                => null,
			'membership_billing_limit'               => null,
			'membership_trial_amount'                => null,
			'membership_trial_limit'                 => null,
			'membership_status'                      => null,
			'membership_startdate'                   => null,
			'membership_enddate'                     => null,
			'membership_subscription_transaction_id' => null,
			'membership_payment_transaction_id'      => null,
			'membership_gateway'                     => null,
			'membership_affiliate_id'                => null,
			'membership_timestamp'                   => null,
		);
		$buddypress   = array(
			'bp_profile' => null,
			'bp_group'   => null,
		);

		return array(
			array( $pmpro_fields + $buddypress, $pmpro_fields + $buddypress ),
			array( $buddypress, $buddypress ),
			array( $pmpro_fields, $pmpro_fields ),
			array( null, array() ),
			array( false, array() ),
			array( 'something weird', array() ),
			array( array(), array() ),
			array( [], array() ), // phpcs:ignore Generic.Arrays.DisallowShortArraySyntax.Found
		);
	}

	/**
	 * InvalidProperty exception thrown and caught if (for some weird reason) the 'fields' property is missing
	 * from the Variables() class. The supplied user_meta array should not be processed and there should
	 * NOT be any keys with an 'imported_' prefix in the returned array
	 *
	 * @return void
	 * @throws \Exception
	 *
	 * @test
	 * @dataProvider fixture_user_meta_and_fields
	 */
	public function it_should_throw_invalid_property_exception( $user_meta ) {
		$m_present = $this->makeEmpty( User_Present::class );
		$m_passwd  = $this->makeEmpty( Generate_Password::class );
		$m_vars    = $this->getMockBuilder( Variables::class )->getMock();
		$m_vars->method( 'get' )
			->willThrowException(
				new InvalidProperty(
					'Error: The requested parameter \'fields\' does not exist!'
				)
			);
		$m_time        = $this->makeEmpty( Time::class );
		$m_date        = $this->makeEmpty( Date_Format::class );
		$import_user   = new Import_User( $m_vars, $this->mocked_errorlog, $m_present, $m_passwd, $m_time, $m_date );
		$returned_meta = $import_user->import_usermeta( $user_meta, array(), array() );
		self::assertIsArray( $returned_meta );
		foreach ( $returned_meta as $key => $value ) {
			self::assertArrayNotHasKey( "imported_{$key}", $returned_meta );
		}
	}

	/**
	 * Tests Import_User::update_user_id()
	 *
	 * @param int $line_no The line number we use from the fixture_user_csv file
	 * @param bool $update_id Whether to allow updating the User's ID in the database
	 * @param bool $wpdb_update_returns The value we want $wpdb->update() to return
	 * @param string $user_table Table name we're pretending to use
	 * @param array $csv_user_data Data we're pretending to import
	 * @param string|null $exception_thrown The exception we're expecting to see thrown by update_user_id()
	 *
	 * @return void
	 * @throws InvalidProperty
	 * @throws \E20R\Exceptions\CannotUpdateDB
	 * @throws \E20R\Exceptions\UserIDAlreadyExists
	 *
	 * @test
	 * @dataProvider fixture_update_user_id_tests
	 */
	public function it_doesnt_update_the_db_since_we_found_the_existing_user( $line_no, $update_id, $wpdb_update_returns, $user_table, $csv_user_data, $exception_thrown ) {
		global $wpdb;
		global $active_line_number;

		$active_line_number = $line_no;
		$user               = $this->fixture_create_wp_user( $line_no );

		$new_wpdb = $this->makeEmpty(
			wpdb::class,
			array(
				'update' => $wpdb_update_returns,
			)
		);

		$new_wpdb->pmpro_membership_levels = 'wp_pmpro_membership_levels';
		$new_wpdb->users                   = $user_table;

		$m_present = $this->makeEmpty( User_Present::class );
		$m_passwd  = $this->makeEmpty( Generate_Password::class );
		$m_vars    = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $param ) use ( $update_id ) {
					if ( '' === $param ) {
						return $update_id;
					}
					return null;
				},
			)
		);
		$m_time    = $this->makeEmpty( Time::class );
		$m_date    = $this->makeEmpty( Date_Format::class );

		if ( null !== $exception_thrown ) {
			$this->expectException( $exception_thrown );
		}

		expect( 'get_userdata' )
			->once()
			->with( $csv_user_data['ID'] )
			->andReturn( $user );
		expect( 'get_user_by' )
			->once()
			->with( 'ID', $csv_user_data['ID'] )
			->andReturn( $user );

		$import_user = new Import_User( $m_vars, $this->mocked_errorlog, $m_present, $m_passwd, $m_time, $m_date );

		// Switch to mocked WPDB object and then run test
		$orig_wpdb = $wpdb;
		$wpdb      = $new_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$result    = $import_user->update_user_id( $user, $csv_user_data );
		$wpdb      = $orig_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		self::assertSame( $user->ID, $result->ID );
		self::assertInstanceOf( WP_User::class, $result );
	}

	/**
	 * Fixture to validate behavior when supplied user object and ID being imported (from CSV data) is the same
	 *
	 * @return array[]
	 */
	public function fixture_update_user_id_tests() {
		return array(
			// line no, allow update of User's IDin DB, wpdb->update returns, user table name, import data array, $thrown exception
			array( 0, false, true, 'wp_users', array( 'ID' => 1023 ), null ),
			array( 1, false, true, 'wp_users', array( 'ID' => 65536 ), null ),
			array( 2, true, true, 'wptest_users', array( 'ID' => 1111 ), null ),
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
	public function fixture_create_wp_user( $line_no ) {
		$data                        = new Manage_Test_Data();
		list( $headers, $user_data ) = $data->read_line_from_csv( $line_no, $this->fixture_user_csv );
		$m_user                      = new WP_User();

		foreach ( $user_data as $property => $value ) {
			$m_user->{$property} = $value;
		}

		return $m_user;
	}
}
