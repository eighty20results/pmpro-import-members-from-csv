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
 * @package E20R\Tests\Integration\Generate_Password_IntegrationTest
 */

namespace E20R\Tests\Integration;

use Codeception\TestCase\WPTestCase;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\Generate_Password;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Status;
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;
use org\bovigo\vfs\vfsStream;
use WP_Error;
use WP_Mock;
use WP_User;


class Generate_Password_IntegrationTest extends WPTestCase {
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
	 * Instance of the CSV() class we're testing
	 *
	 * @var null|CSV
	 */
	private $csv = null;

	/**
	 * Mocked file system to use for testing purposes
	 *
	 * @var null|vfsStream
	 */
	private $file_system = null;

	/**
	 * Directory structure for the mocked file system
	 *
	 * @var \array[][][][][][]
	 */
	private $structure;

	/**
	 * Test data management instance
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
		parent::setUp();
		\WP_Mock::setUp();

		$this->structure   = array(
			'var' => array(
				'www' => array(
					'html' => array(
						'wp-content' => array(
							'uploads' => array(
								'e20r_imports' => array(),
							),
						),
					),
				),
			),
		);
		$this->file_system = vfsStream::setup( 'mocked', null, $this->structure );

		$this->test_data = new Manage_Test_Data();
		$this->load_mocks();

		$this->test_data->tables_exist();

		// Insert membership level data
		$this->test_data->set_line();
		$this->test_data->insert_level_data();
	}

	/**
	 * Tear Down logic
	 * @return void
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 */
	public function load_mocks() : void {
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {
	}


	/**
	 * Test instantiation of the Generate_Password() class
	 *
	 * @param null|Error_Log $error_log Instance of the Error_Log() class (or null) value
	 * @param null|Variables $variables Instance of the Variables() class (or null) value
	 * @param null|WP_Error $wp_error Instance of the WP_Error() class (or null) value
	 *
	 * @return void
	 * @throws \E20R\Exceptions\InvalidSettingsKey
	 */
	public function it_should_instantiate( $error_log, $variables, $wp_error ) {

		$generate_password = new Generate_Password( $variables, $error_log, $wp_error );
		self::assertInstanceOf( Error_Log::class, $generate_password->get( 'error_log' ) );
		self::assertInstanceOf( Variables::class, $generate_password->get( 'variables' ) );
		self::assertInstanceOf( WP_Error::class, $generate_password->get( 'wp_error' ) );
	}

	/**
	 * Fixture for the it_should_instantiate() method
	 *
	 * @return array
	 */
	public function fixture_instantiation() {
		return array(
			array( new Error_Log(), new Variables(), new WP_Error() ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			array( null, null, null ),
		);
	}

	/**
	 * Test for the status_msg() method
	 *
	 * @param int $status Status message (code) to process
	 * @param bool $allow_updates Whether to allow updates or not
	 * @param int $line_num The mocked line number in the CSV file
	 * @param bool $expected The error (status) message exists or not
	 *
	 * @return void
	 *
	 * @test
	 *
	 * @dataProvider fixture_status_msgs
	 */
	public function it_should_set_status_msg( $status, $allow_updates, $line_num, $expected ) {

		$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$variables = new Variables( $error_log );
		$wp_error  = new WP_Error();

		$generate_password = new Generate_Password( $variables, $error_log, $wp_error );
		$generate_password->status_msg( $status, $allow_updates );

		global $active_line_number;
		global $e20r_import_warn;

		if ( $expected ) {
			self::assertArrayHasKey( "overwriting_password_{$active_line_number}", $e20r_import_warn );
			self::assertSame( $expected, is_a( WP_Error::class, $e20r_import_warn[ "overwriting_password_{$active_line_number}" ] ) );
			self::assertStringContainsStringIgnoringCase( 'Warning: Changing password for an existing user', $e20r_import_warn[ "overwriting_password_{$active_line_number}" ]->getMessage() );
		}

		if ( ! $expected ) {
			self::assertArrayNotHasKey( "overwriting_password_{$active_line_number}", $e20r_import_warn );
		}

	}

	/**
	 * Fixture for the it_should_set_status_msg() method
	 *
	 * @return array[]
	 */
	public function fixture_status_msgs() {
		return array(
			array( Status::E20R_USER_EXISTS_NEW_PASSWORD, false, 1, true ),
			array( Status::E20R_USER_EXISTS_NEW_PASSWORD, true, 100, true ),
			array( Status::E20R_ERROR_NO_EMAIL, false, 2123, false ),
		);
	}

	/**
	 * Test that the validation logic for Generate_Password::validate() results in correct decision wrt to creating a new password for the user or not
	 *
	 * @param array $record The mocked user import record to validate
	 * @param bool $update Do we allow update(s) of users (unused, but should be tested)
	 * @param WP_User|null $wp_user The WP_User record generated (if applicable)
	 * @param bool $expected The expected outcome of the Generate_Password::validate() method invocation
	 *
	 * @return void
	 *
	 * @test
	 * @dataProvider fixture_record_to_validate
	 */
	public function it_should_validate_record_data( $record, $update, $wp_user, $expected ) {
		$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$variables = new Variables( $error_log );
		$wp_error  = new WP_Error();

		$generate_password = new Generate_Password( $variables, $error_log, $wp_error );
		$result            = $generate_password->validate( $record, $update, $wp_user );

		self::assertSame( $expected, $result );
	}

	/**
	 * The mocked import record to use for the test
	 *
	 * @return array[]
	 */
	public function fixture_record_to_validate() {

		$empty_record = array();

		return array(
			array( $this->fixture_record( false, null ), false, null, true ),
			array( $this->fixture_record( true, null ), false, null, true ),
			array( $this->fixture_record( true, 'password' ), false, new WP_User(), false ),
			array( $this->fixture_record( true, '' ), false, new WP_User(), false ),
			array( $this->fixture_record( true, ' ' ), false, new WP_User(), false ),
			array( $empty_record, true, null, false ),
			array( null, true, null, false ),
		);
	}

	/**
	 * Generates fake import record for password generation check
	 *
	 * @param bool $set_it Whether to include the 'user_pass' field
	 * @param mixed $value user_pass value to use
	 *
	 * @return string[]
	 */
	private function fixture_record( $set_it = false, $value = null ) {
		$record = array(
			'user_login' => 'dummy_user_2',
			'user_email' => 'nothing@nowhere.com',
		);

		if ( $set_it ) {
			$record['user_pass'] = $value;
		}

		return $record;
	}
}
