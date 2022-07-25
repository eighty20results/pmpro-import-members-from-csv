<?php
/**
 * Copyright (c) 2021 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @pacakge E20R\Tests\Unit\User_Present_UnitTest
 */
namespace E20R\Tests\Unit;

use Codeception\Test\Unit;

use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidProperty;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;

use WP_Error;

use Brain\Monkey;
use Brain\Monkey\Functions;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

// Functions to Import from other namespaces
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;


class User_Present_UnitTest extends Unit {

	use MockeryPHPUnitIntegration;

	/**
	 * Codeception _before() method
	 */
	public function setUp() : void {  //phpcs:ignore
		parent::setUp();
		Monkey\setUp();
		$this->loadMocks();
	}

	/**
	 * Codeception _after() method
	 */
	public function tearDown() : void { //phpcs:ignore
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load all function mocks we need (with namespaces)
	 */
	public function loadMocks() : void {

		Functions\when( 'esc_url' )
			->justReturn( 'https://localhost:7537' );

		Functions\when( 'esc_url_raw' )
			->justReturn( 'https://localhost:7537' );

		Functions\when( 'esc_attr__' )
			->returnArg( 1 );

		Functions\when( 'get_option' )
			->justReturn( 'https://www.paypal.com/cgi-bin/webscr' );

		Functions\when( 'update_option' )
			->justReturn( true );
	}

	/**
	 * Test failure when attempting to return allow_update setting in User_Present::validate()
	 *
	 * @return void
	 * @throws InvalidInstantiation
	 *
	 * @test
	 */
	public function it_should_not_find_update_users_setting() {
		$m_errs     = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug'         => function( $msg ) {
					error_log( "Mock: {$msg}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				},
				'add_error_msg' => function( $msg, $type ) {
					self::assertStringEndsWith( 'Error: The requested parameter \'update_users\' does not exist!', $msg );
				},
			)
		);
		$m_variable = $this->getMockBuilder( Variables::class )->getMock();
		$m_variable->method( 'get' )
				->willThrowException(
					new InvalidProperty(
						'Error: The requested parameter \'update_users\' does not exist!'
					)
				);
		$wperr  = $this->makeEmpty( WP_Error::class );
		$class  = new User_Present( $m_variable, $m_errs, $wperr );
		$result = $class->validate( array() );

		self::assertIsBool( $result );
		self::assertFalse( $result );
	}

	/**
	 * Test of User_Present::validate()
	 *
	 * @param bool $has_id Whether to include the 'ID' key/value in CSV record being used
	 * @param bool $has_email Whether to include the 'user_email' key/value in CSV record being used
	 * @param bool $has_login Whether to include the 'user_login' key/value in CSV record being used
	 * @param bool $is_integer Whether the ID is an integer value
	 * @param bool $user_exists User_Present::db_user_exists() return value
	 * @param bool $data_importable User_Present::data_can_be_imported() return value
	 * @param null|string $invalid_email Whether to use an invalid email address (or the invalid email address itself)
	 * @param bool|int $expected The expected return value for User_Present::validate()
	 *
	 * @return void
	 *
	 * @test
	 * @dataProvider fixture_data_to_return_from_validation
	 */
	public function it_should_return_status_from_record_validation( $has_id, $has_email, $has_login, $is_integer, $user_exists, $data_importable, $invalid_email, $expected ) {
		$record = $this->fixture_default_csv_import_record();
		when( 'wp_insert_user' )
			->justReturn( $record['ID'] );

		when( 'is_email' )
			->alias(
				function( $address ) {
					return filter_var( $address, FILTER_VALIDATE_EMAIL );
				}
			);

		if ( false === $has_id ) {
			unset( $record['ID'] );
		}
		if ( false === $has_email ) {
			unset( $record['user_email'] );
		}
		if ( false === $has_login ) {
			unset( $record['user_login'] );
		}
		if ( true === $has_email && null !== $invalid_email ) {
			$record['user_email'] = $invalid_email;
		}

		$m_errs     = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					error_log( "Mock: {$msg}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				},
			)
		);
		$m_variable = $this->makeEmpty(
			Variables::class,
			array(
				'get' => function( $name ) {
					if ( 'update_users' === $name ) {
						return false;
					}
					return null;
				},
			)
		);
		$wperr      = $this->makeEmpty( WP_Error::class );
		$class      = $this->constructEmptyExcept(
			User_Present::class,
			'validate',
			array( $m_variable, $m_errs, $wperr ),
			array(
				'is_valid_integer'     => $is_integer,
				'data_can_be_imported' => $data_importable,
				'db_user_exists'       => $user_exists,
			)
		);
		$result     = $class->validate( $record );

		self::assertSame(
			$expected,
			$result,
			"User_Present::validate() should have returned false. It didn't! (returned value: '{$result}')"
		);
	}

	/**
	 * Generates a valid, fake, array of data from a mocked CSV record
	 *
	 * @return array
	 */
	public function fixture_default_csv_import_record() {
		return array(
			'ID'         => 1000,
			'user_email' => 'tester@example.org',
			'user_login' => 'tester@example.org',
		);
	}

	/**
	 * Fixture for User_Present_IntegrationTests::it_should_return_status_from_record_validation()
	 *
	 * @return array[]
	 */
	public function fixture_data_to_return_from_validation() {
		return array(
			// has_id, has_email, has_login, is_integer, user_exists, data_importable, invalid email address, return value from validate()
			array( true, true, true, false, false, true, null, false ),
			array( true, true, true, true, false, true, null, true ),
			array( true, false, false, false, false, false, null, false ),
			array( true, false, false, false, false, false, null, false ),
			array( true, false, false, true, false, false, null, false ),
			array( false, false, false, true, false, false, null, false ),
			array( false, true, false, false, false, false, null, false ),
			array( false, true, false, false, false, false, 'tester.example.com', false ),
			array( false, true, true, false, false, false, null, false ),
			array( false, true, true, false, false, Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED, null, Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED ),
		);
	}

}
