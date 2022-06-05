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
		$this->test_data = new Manage_Test_Data( null, $this->errorlog );
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
	 * @param int[] $import_results Results from the mocked Import_User::import() method
	 * @return void
	 *
	 * @dataProvider fixture_process_test_data
	 *
	 * @test
	 */
	public function it_should_successfully_process_csv_file( $file_name, $file_args, $line_number, $resulting_uid, $headers, $content ) {

		$m_user_import = self::makeEmpty(
			Import_User::class,
			array(
				'import' => $resulting_uid,
			)
		);

		if ( ! function_exists( 'get_option' ) ) {
			expect( 'get_option' )
				->with( array( "e20rcsv_{$file_name}", null ) )
				->atLeast()
				->once()
				->andReturn( $line_number );
		} else {
			$this->fail( 'get_option() is defined before this test is executed!' );
		}

		// FIXME: Need to make sure we mock the full set of SplFileObject() methods we use, in the right order
		$input_file = Mockery::mock( SplFileObject::class, array( 'php://memory' ) );
		$input_file->shouldReceive( 'setCsvControl' )
			->with( E20R_IM_CSV_DELIMITER, E20R_IM_CSV_ENCLOSURE, E20R_IM_CSV_ESCAPE )
			->once()
			->shouldReceive( 'setFlags' )
			->with( SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY )
			->once()
			->shouldReceive( 'eof' )
			->with()
			->andReturn( false )
			->once()
			->shouldReceive( 'fgetcsv' )
			->with()
			->andReturn( $headers )
			->shouldReceive( 'eof' )
			->with()
			->andReturn( false )
			->shouldReceive( 'key' )
			->with()
			->andReturn( 1 )
			->once()
			->shouldReceive( 'fgetcsv' )
			->with()
			->andReturn( $headers )
			->shouldReceive( 'eof' )
			->with()
			->andReturn( true );

		try {
			$this->csv = new CSV( $this->variables, $this->errorlog );
			$result    = $this->csv->process( $file_name, $file_args, $m_user_import, $input_file );
		} catch ( InvalidSettingsKey $e ) {
			$this->fail( 'Should not receive: ' . $e->getMessage() );
		}
	}

	/**
	 * Fixture for the it_should_successfully_process_csv_file()
	 * @return array[]
	 */
	public function fixture_process_test_data() {
		return array(
			// file_name, file_args, line_number, resulting_uid, headers, content
			array( 'test_file_1.csv', array(), 1, 2, array(), array() ),
		);
	}
}