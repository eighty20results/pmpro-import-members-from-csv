<?php
/**
 * Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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
 */
namespace E20R\Test\Unit;

use Brain\Monkey\Functions;
use Codeception\Test\Unit;
use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\Create_Password;
use E20R\Import_Members\Variables;
use Brain\Monkey;
use Exception;
use Mockery;

// Functions to import from other namespaces
use function PHPUnit\Framework\assertEquals;

class Create_Password_Test extends Unit {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Codeception _before() method
	 */
	public function setUp() : void {  //phpcs:ignore
		parent::setUp();
		Monkey\setUp();
		$this->loadMocks();
		$this->loadTestSources();
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

		Functions\when( 'wp_upload_dir' )
			->justReturn(
				array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads',
				)
			);
		Functions\when( 'get_option' )
			->justReturn( 'https://www.paypal.com/cgi-bin/webscr' );

		Functions\when( 'update_option' )
			->justReturn( true );
	}

	/**
	 * Load all needed source files for the unit test
	 */
	public function loadTestSources(): void {
		require_once __DIR__ . '/../../../src/modules/Users/class-create-password.php';
	}

	/**
	 * Test whether the correct path is returned for the import file specified
	 *
	 * @dataProvider fixture_user_password_validation
	 */
	public function test_validate( $user_record, $update_user, $user, $expected_result ) {
		$result = Create_Password::validate( $user_record, $update_user, $user );
		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Fixture for the CSV::get_import_file_path() unit test
	 *
	 * Expected info for fixture:
	 *      string full path, (string) $expected_file_name
	 *
	 * @return array[]
	 */
	public function fixture_user_password_validation() : array {

		$user             = new \stdClass();
		$user->ID         = 1;
		$user->user_login = 'something';

		return array(
			// user_record, update_user, WP_User, expected_result
			array( array( 'user_login' => 'something' ), true, null, true ),
			array( array( 'user_pass' => 'something' ), true, $user, true ),
			array( array( 'user_login' => null ), false, null, true ),
			array( array( 'user_pass' => '' ), false, null, true ),
			array( array( 'user_pass' => '' ), true, null, true ),
			array( array( 'user_pass' => '' ), false, $user, false ),
			array( array( 'user_pass' => 'something' ), false, $user, false ),
			array( array( 'user_firstname' => 'Peter' ), true, $user, false ),
		);
	}

}
