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
 * @package E20R\Tests\Integration\CSV_IntegrationTest
 */

namespace E20R\Tests\Integration;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Codeception\TestCase\WPTestCase;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Variables;
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;
use Mockery;
use org\bovigo\vfs\vfsStream;
use SplFileObject;
use WP_Error;
use WP_Mock;
use WP_Mock\Functions;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;
use function Symfony\Component\String\s;

class CSV_IntegrationTest extends WPTestCase {
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

		$this->errorlog->debug( 'Make sure the expected DB tables exist' );
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

		$this->errorlog  = self::makeEmpty(
			Error_Log::class,
			array(
				'debug'      => function( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Mocked -> {$msg}" );
				},
				'log_errors' => function( $errors, $log_file_path, $log_file_url ) {
					foreach ( $errors as $key => $error ) {
						$msg = sprintf(
							'%1$d => %2$s (dest: %3$s or %4$s)',
							$key,
							$error->get_error_message(),
							$log_file_path,
							$log_file_url
						);
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( $msg );
					}
				},
			)
		);
		$this->variables = new Variables( $this->errorlog );
		$this->test_data = new Manage_Test_Data( $this, null, $this->errorlog );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {
	}

	/**
	 * Test for CSV::verify_import_file_path()
	 *
	 * @param string $file_name File name we're passing to the method being tested
	 * @param bool   $file_exists The return value for the mocked 'file_exists()' function
	 * @param bool   $from_settings Whether to use the Variables() class (settings) to retrieve the file name
	 * @param bool   $from_transient Whether to use get_transient() to retrieve the filename
	 * @param string|null $request_value The value to assign to the $_REQUEST['filename'] global variable
	 * @param string|false $expected_filename The expected return value from the test
	 *
	 * @return void
	 *
	 * @dataProvider fixture_upload_settings
	 * @covers CSV::verify_import_file_path()
	 *
	 * @test
	 */
	public function it_should_validate_filename_settings( $file_name, $file_exists, $from_settings, $from_transient, $request_value, $expected_filename ) {

		if ( ! empty( $from_transient ) ) {
			$this->errorlog->debug( "Setting 'e20r_import_filename' transient to {$from_transient}" );
			set_transient( 'e20r_import_filename', $from_transient );
			$file_name = $from_transient;
		}

		if ( null !== $from_settings ) {
			$this->errorlog->debug( "Set 'filename' in Variables() to '{$from_settings}'" );
			$this->variables->set( 'filename', $from_settings );
			$file_name = $from_settings;
		}

		if ( null !== $request_value ) {
			$this->errorlog->debug( "Set \$_REQUEST['filename'] to {$request_value}" );
			$_REQUEST['filename'] = $request_value;
			$file_name            = $request_value;
		}

		if ( true === $file_exists ) {
			$this->errorlog->debug( "Adding {$file_name} as the file to the mocked file system" );
			$this->structure['var']['www']['html']['wp-content']['uploads']['e20r_imports'] = array( $file_name => 'dummy,csv,header,data' );
			$this->file_system = vfsStream::setup( 'mocked', null, $this->structure );
		}

		$this->errorlog->debug( "file_exists('$file_name') should return: " . ( $file_exists ? 'True' : 'False' ) );

		try {
			$this->csv = new CSV( $this->variables, $this->errorlog );

			// Using the vfsStream() class to mock the file system, so the directory path is a little different from that of a real server
			$result = $this->csv->verify_import_file_path( $file_name, 'vfs://mocked/var/www/html/wp-content/uploads/e20r_imports' );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not receive: ' . $e->getMessage() );
		}

		self::assertSame( $expected_filename, $result, "Expected '{$expected_filename}' but received '{$result}'" );
	}

	/**
	 * Fixture for the it_should_validate_filename_settings() test function
	 *
	 * @return array[]
	 */
	public function fixture_upload_settings() {
		$upload_dir = 'vfs://mocked/var/www/html/wp-content/uploads/e20r_imports';
		return array(
			// filename, file_exists, from_settings, from_transient, request_value, expected_filename
			array( 'file_name_1.csv', true, null, null, null, "{$upload_dir}/file_name_1.csv" ), // #0
			array( 'file_name_1.csv', false, null, null, null, false ), // #1
			array( null, true, 'file_name_2.csv', null, null, "{$upload_dir}/file_name_2.csv" ), // #2
			array( null, false, 'file_name_2.csv', null, null, false ), // #3
			array( null, true, null, 'file_name_3.csv', null, "{$upload_dir}/file_name_3.csv" ), // #4
			array( null, false, null, 'file_name_3.csv', null, false ), // #5
			array( null, true, null, null, 'file_name_4.csv', "{$upload_dir}/file_name_4.csv" ), // #6 - fails!
			array( null, false, null, null, 'file_name_4.csv', false ), // #7
		);
	}

	/**
	 * Happy path for CSV::process() method
	 *
	 * @param string $file_name The name of the file we're processing
	 * @param array $file_args File arguments we support/allow
	 * @param int $line_number The "active_line_number" global value we need to use
	 * @param int $resulting_uid The UID to return from the mocked Import_User::import() method
	 * @param array $csv_content Array of CSV field values that match the headers
	 * @param array $expected The expected result retuned from CSV::process()
	 *
	 * @return void
	 *
	 * @dataProvider fixture_process_test_data
	 *
	 * @covers       CSV::process
	 * @test
	 * @throws \Exception
	 */
	public function it_should_successfully_process_csv_file( $file_name, $file_args, $line_number, $resulting_uid, $csv_content, $expected ) {

		WP_Mock::alias(
			'get_option',
			function( $option, $default ) use ( $file_name, $line_number ) {
				$value = $default;

				switch ( $option ) {
					case "e20rcsv_{$file_name}":
						$value = $line_number;
						break;
					case 'e20r_import_errors':
						global $e20r_import_err;
						$value = $e20r_import_err;
						break;
					default:
						$this->errorlog->debug( "Unexpected option name: {$option}" );
				}

				return $value;
			}
		);
		WP_Mock::alias(
			'apply_filters',
			function( $filter_name, $filter_value ) use ( $csv_content, $resulting_uid ) {
				if ( 'e20r_import_users_validate_field_data' === $filter_name ) {
					$this->errorlog->debug( 'Filter handler for unique identity field validation' );
					if ( in_array( 'user_login', array_keys( $csv_content ), true ) ) {
						return true;
					}
					if ( in_array( 'user_email', array_keys( $csv_content ), true ) ) {
						return true;
					}
					if ( in_array( 'ID', array_keys( $csv_content ), true ) ) {
						return true;
					}
					return false;
				}

				if ( in_array(
					$filter_name,
					array(
						'is_iu_import_userdata',
						'pmp_im_import_userdata',
						'e20r_import_userdata',
					),
					true
				) ) {
					$this->errorlog->debug( 'Filter handler for user data filtering' );
					return $filter_value;
				}

				if ( in_array(
					$filter_name,
					array(
						'is_iu_import_usermeta',
						'pmp_im_import_usermeta',
						'e20r_import_usermeta',
					),
					true
				) ) {
					return $filter_value;
				}

				if ( 'e20r_import_wp_user_data' === $filter_name ) {
					$this->errorlog->debug( 'Filter handler for adding/updating WP User record(s)' );
					return $resulting_uid;
				}

				return $filter_value;
			}
		);

		$input_file = Mockery::mock( SplFileObject::class, array( 'php://memory' ) );
		$input_file->shouldReceive( 'setCsvControl' )
			->with( E20R_IM_CSV_DELIMITER, E20R_IM_CSV_ENCLOSURE, E20R_IM_CSV_ESCAPE )
			->once()
			->shouldReceive( 'setFlags' )
			->with( SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY )
			->once()
			->shouldReceive( 'eof' )
			->with()
			->andReturn( false, false, true )
			->shouldReceive( 'fgetcsv' )
			->with()
			->andReturn( array_keys( $csv_content ), array_values( $csv_content ) )
			->shouldReceive( 'key' )
			->andReturn( 1 );

		// Executing apply_filters( 'e20r_import_users_validate_field_data' ) filter as a mocked function
		try {
			$this->csv = new CSV( $this->variables, $this->errorlog );
			$result    = $this->csv->process( $file_name, $file_args, $input_file );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not receive: ' . $e->getMessage() );
		}

		self::assertArrayHasKey( 'user_ids', $result );
		self::assertArrayHasKey( 'errors', $result );
		self::assertArrayHasKey( 'warnings', $result );
		foreach ( $result['errors'] as $header => $error_object ) {
			self::assertIsObject( $error_object, 'Not a WP_Error() object!' );
			self::assertObjectHasAttribute( 'errors', $error_object );
			self::assertObjectHasAttribute( 'error_data', $error_object );
			self::assertObjectHasAttribute( 'additional_data', $error_object );
		}
	}

	/**
	 * Fixture for the it_should_successfully_process_csv_file()
	 * @return array[]
	 */
	public function fixture_process_test_data() {
		return array(
			// file_name, file_args, line_number, resulting_uid, content, expected
			array(
				'test_file_1.csv',
				array(),
				1,
				2,
				array(),
				array(
					'user_ids' => array(),
					'errors'   => array( 'header_missing_1' => new WP_Error( 'e20r_im_header', 'Missing header line in the CSV file being imported!' ) ),
					'warnings' => array(),
				),
			),
			array(
				'test_file_1.csv',
				array(),
				1,
				2,
				array(
					'user_email' => 'name.surname@example.com',
					'user_login' => 'name.surname',
				),
				array(
					'user_ids' => array( 2 ),
					'errors'   => array( 'header_missing_1' => new WP_Error( 'e20r_im_header', 'Missing header line in the CSV file being imported!' ) ),
					'warnings' => array(),
				),
			),
		);
	}
}
