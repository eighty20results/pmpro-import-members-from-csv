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
use E20R\Import_Members\Data;
use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\PMPro\PMPro;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Process\Ajax;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\Create_Password;
use E20R\Import_Members\Process\Page;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Variables;
use Brain\Monkey;
use MemberOrder;

// Functions to Import from other namespaces
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WP_Mock;

class Create_Password_UnitTest extends Unit {

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
		$this->load_stubs();
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
	 */
	public function load_mocks() : void {

		$this->mocked_errorlog = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Mocked log: {$msg}" );
				},
			)
		);

		$mocked_variables = $this->makeEmpty(
			Variables::class
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

		$this->mocked_import = $this->constructEmpty(
			Import::class,
			array( $mocked_variables, $mocked_pmpro, $mocked_data, $mocked_import_user, $mocked_import_member, $mocked_csv, $mocked_email_templates, $mocked_validate_data, $mocked_page, $mocked_ajax, $this->mocked_errorlog ),
			array(
				'get' => function( $key ) use ( $mocked_variables ) {
					if ( 'variables' === $key ) {
						return $mocked_variables;
					}
					if ( 'error_log' === $key ) {
						return $this->mocked_errorlog;
					}

					return null;
				},
			)
		);
	}

	/**
	 * Load all function mocks we need (with namespaces)
	 */
	public function load_stubs() : void {

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
		Functions\when( 'plugin_dir_path' )
			->justReturn( '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv' );

		Functions\when( 'get_option' )
			->justReturn( 'https://www.paypal.com/cgi-bin/webscr' );

		Functions\when( 'update_option' )
			->justReturn( true );
	}

	/**
	 * Test whether the correct path is returned for the Import file specified
	 *
	 * @param array $user_record The user record (array) to import
	 * @param bool  $update_user Whether to update a user's data (record) or not
	 * @param bool  $expected_result The expected result after running the Create_Password::validate() method for the password
	 *
	 * @dataProvider fixture_user_password_validation
	 * @test
	 */
	public function it_should_validate_the_created_password( $user_record, $update_user, $user, $expected_result ) {
		$validate = new Create_Password( $this->mocked_variables, $this->mocked_errorlog );
		$result   = $validate->validate( $user_record, $update_user, $user );
		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Fixture for the CSV::verify_import_file_path() unit test
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
