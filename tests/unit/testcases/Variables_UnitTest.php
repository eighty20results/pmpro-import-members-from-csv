<?php
/**
 * Copyright (c) 2018-2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

use Brain\Monkey;
use Brain\Monkey\Functions;
use Codeception\Test\Unit;
use Mockery;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Error_Log;
use Exception;

class Variables_UnitTest extends Unit {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Codeception setUp() method
	 */
	public function setUp() : void {  //phpcs:ignore
		parent::setUp();
		Monkey\setUp();
		$this->loadMocks();
	}

	/**
	 * Codeception tearDown() method
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
	}

	/**
	 * Test the happy path for the is_configured method
	 *
	 * @param array    $request_variables List of request variables needed for the Variables() class
	 * @param array    $file_info The $_FILES variable content to use during the test
	 * @param string[] $error_msgs The error messages array we're testing
	 * @param string[] $sponsor_links Array of links to use for the PMPro Sponsored members information
	 *
	 * @dataProvider fixture_is_configured
	 *
	 * @test
	 */
	public function it_should_test_that_variables_are_configured( $request_variables, $file_info, $error_msgs, $sponsor_links ) {

		$errlog_mock = $this->getMockBuilder( Error_Log::class )
							->onlyMethods( array( 'debug', 'add_error_msg' ) )
							->getMock();

		$errlog_mock->method( 'debug' )
					->willReturn( null );
		$errlog_mock->method( 'add_error_msg' )
					->willReturn( null );

		try {
			Functions\expect( 'get_option' )
				->with( Mockery::contains( 'e20r_im_error_msg' ) )
				->andReturn( $error_msgs );
		} catch ( \Exception $e ) {
			$this->fail( 'Error: ' . $e->getMessage() );
		}

		try {
			Functions\expect( 'get_option' )
				->with( Mockery::contains( 'e20r_link_for_sponsor' ) )
				->andReturn( $sponsor_links );
		} catch ( \Exception $e ) {
			$this->fail( 'Error: ' . $e->getMessage() );
		}

		try {
			Functions\expect( 'update_option' )
				->with( Mockery::contains( 'e20r_im_error_msg' ) )
				->andReturn( true );
		} catch ( \Exception $e ) {
			$this->fail( 'Error: ' . $e->getMessage() );
		}

		try {
			Functions\expect( 'get_transient' )
				->with( Mockery::contains( 'e20r_import_filename' ) )
				->andReturn( $file_info[0]['members_csv']['name'] );
		} catch ( Exception $e ) {
			$this->fail( 'Error: ' . $e->getMessage() );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_REQUEST  = $_REQUEST + $request_variables;
		$_FILES    = $_FILES + $file_info[0];
		$expected  = ! empty( $file_info[1] );
		$variables = new Variables();

		$result = $variables->is_configured();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Fixture: File name fixture for unit tests
	 * TODO: List length must equal the # items returned from fixture_request_settings()
	 *
	 * @return array[]
	 */
	private function fixture_import_file_names() {
		return array(
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/home/user1/csv_files/test-error-imports.csv',
					),
				),
				'test-error-imports.csv',
			),
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
					),
				),
				'example_file.csv',
			),
			array(
				array(
					'members_csv' => array(
						'tmp_name' => '',
						'name'     => null,
					),
				),
				null,
			),
		);
	}

	/**
	 * Fixture for the Variables::is_configured method
	 *
	 * @return array
	 */
	public function fixture_is_configured() : array {

		$fixture        = array();
		$file_info      = $this->fixture_import_file_names();
		$error_msg_list = $this->fixture_error_msgs();
		$sponsor_links  = $this->fixture_sponsor_links();

		foreach ( $this->fixture_request_settings() as $key_id => $request ) {
			$fixture[] = array(
				$request,
				$file_info[ $key_id ],
				$error_msg_list[ $key_id ] ?? array(),
				$sponsor_links[ $key_id ] ?? array(),
			);
		}

		return $fixture;
	}

	/**
	 * Fixture: List of sponsor link options
	 * TODO: List length must equal the # items returned from fixture_request_settings()
	 *
	 * @return \array[][]
	 */
	private function fixture_sponsor_links() {
		return array(
			array( array() ),
		);
	}

	/**
	 * Fixture: List of error messages
	 * TODO: List length must equal the # items returned from fixture_request_settings()
	 *
	 * @return \array[][]
	 */
	private function fixture_error_msgs() {
		return array(
			array( array() ),
		);
	}

	/**
	 * Fixture for the CSV::get_import_file_path() unit test
	 *
	 * Expected info for fixture:
	 *      string full path, (string) $expected_file_name
	 *
	 * @return array[]
	 */
	private function fixture_request_settings() {
		return array(
			// $_REQUEST
			array(
				'update_users'                => 1,
				'background_import'           => 1,
				'deactivate_old_memberships'  => 0,
				'create_order'                => 0,
				'password_nag'                => 0,
				'password_hashing_disabled'   => 0,
				'new_user_notification'       => 1,
				'suppress_pwdmsg'             => 1,
				'admin_new_user_notification' => 0, // WP's standard Admin notification
				'send_welcome_email'          => 0, // User notification w/custom template
				'new_member_notification'     => 0, // WP's standard User notification
				'per_partial'                 => 1, // Whether to batch this (and background it)
				'site_id'                     => 1, // The WordPress Site ID (default is 1)
			),
			array(
				'update_users'                => 1,
				'background_import'           => 1,
				'deactivate_old_memberships'  => 0,
				'create_order'                => 0,
				'password_nag'                => 0,
				'password_hashing_disabled'   => 0,
				'new_user_notification'       => 1,
				'suppress_pwdmsg'             => 1,
				'admin_new_user_notification' => 0, // WP's standard Admin notification
				'send_welcome_email'          => 0, // User notification w/custom template
				'new_member_notification'     => 0, // WP's standard User notification
				'per_partial'                 => 1, // Whether to batch this (and background it)
				'site_id'                     => 1, // The WordPress Site ID (default is 1)
			),
			array(
				'update_users'                => 1,
				'background_import'           => 1,
				'deactivate_old_memberships'  => 0,
				'create_order'                => 0,
				'password_nag'                => 0,
				'password_hashing_disabled'   => 0,
				'new_user_notification'       => 1,
				'suppress_pwdmsg'             => 1,
				'admin_new_user_notification' => 0, // WP's standard Admin notification
				'send_welcome_email'          => 0, // User notification w/custom template
				'new_member_notification'     => 0, // WP's standard User notification
				'per_partial'                 => 1, // Whether to batch this (and background it)
				'site_id'                     => 1, // The WordPress Site ID (default is 1)
			),
		);
	}
}
