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
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Variables;
use Brain\Monkey;
use Exception;
use Mockery;

// Functions to Import from other namespaces
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class CSV_UnitTest extends Unit {

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
	 * Test whether the correct path is returned for the Import file specified
	 *
	 * @param string[] $files_array The contents of the $_FILES[] variable during the test
	 * @param string   $function_arg Argument(s) for the called function
	 * @param mixed    $transient_result The return value for the get_transient() call
	 * @param string   $expected_result The expected URL for the file path this test should have generated
	 *
	 * @dataProvider fixture_import_file_names
	 * @test
	 */
	public function it_should_return_the_import_file_path($files_array, $function_arg, $transient_result, $file_exists, $expected_result ) {

		$_FILES                   = $files_array;
		$_REQUEST['filename']     = $_FILES['members_csv']['name'];
		$_REQUEST['create_order'] = 1;

		$errlog_mock = $this->getMockBuilder( Error_Log::class )
							->onlyMethods( array( 'debug' ) )
							->getMock();
		$var_mock    = $this->getMockBuilder( Variables::class )
							->onlyMethods( array( 'get' ) )
							->getMock();

		try {
			Functions\expect( 'get_transient' )
				->with( Mockery::contains( 'e20r_import_filename' ) )
				->once()
				->andReturn( $transient_result );
		} catch ( Exception $e ) {
			echo 'Error mocking get_transient(): ' . $e->getMessage(); // phpcs:ignore
		}

		if ( empty( $transient_result ) && empty( $function_arg ) ) {
			try {
				Functions\expect( 'sanitize_file_name' )
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					->with( Mockery::contains( $_REQUEST['filename'] ) )
					->once()
					->andReturnFirstArg();
			} catch ( Exception $e ) {
				echo 'Error mocking sanitize_file_name(): ' . $e->getMessage(); // phpcs:ignore
			}
		}

		try {
			Functions\expect( 'file_exists' )
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				->with( Mockery::contains( $_REQUEST['filename'] ) )
				->once()
				->andReturn( $file_exists );
		} catch ( Exception $e ) {
			echo 'Error mocking file_exists(): ' . $e->getMessage(); // phpcs:ignore
		}

		$errlog_mock->method( 'debug' )
					->willReturn( null );

		$var_mock->method( 'get' )
				->with( Mockery::contains( 'filename' ) )
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				->willReturn( basename( $_REQUEST['filename'] ) );

		$csv    = new CSV();
		$result = $csv->get_import_file_path();

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
	public function fixture_import_file_names() : array {
		return array(
			// files_array, function_arg, transient_result, file_exists, expected_result
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/home/user1/csv_files/test-error-imports.csv',
					),
				),
				null,
				'/home/user1/csv_files/test-error-imports.csv',
				true,
				'/var/www/html/wp-content/uploads/e20r_imports/test-error-imports.csv',
			),
			// files_array, function_arg, transient_result, file_exists, expected_result
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
					),
				),
				null,
				'/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
				true,
				'/var/www/html/wp-content/uploads/e20r_imports/example_file.csv',
			),
			//files_array, function_arg, transient_result, file_exists, expected_result
			array(
				array(
					'members_csv' => array(
						'tmp_name' => '',
						'name'     => '/path/',
					),
				),
				null,
				'/path/',
				false,
				'',
			),
			// Assigned a function variable value
			//files_array, function_arg, transient_result, file_exists, expected_result
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/var/www/html/wp-content/uploads/e20r_import/from_function_argument.csv',
					),
				),
				'/var/www/html/wp-content/uploads/e20r_import/from_function_argument.csv',
				'/var/www/html/wp-content/uploads/e20r_import/from_function_argument.csv',
				true,
				'/var/www/html/wp-content/uploads/e20r_imports/from_function_argument.csv',
			),
			// Test sanitize_filename option (i.e. $_REQUEST['filename'] contains information)
			//files_array, function_arg, transient_result, file_exists, expected_result
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/home/user1/csv_files/test-error-imports.csv',
					),
				),
				null,
				null,
				true,
				'/var/www/html/wp-content/uploads/e20r_imports/test-error-imports.csv',
			),
			// File doesn't exist so return
			//files_array, function_arg, transient_result, file_exists, expected_result
			array(
				array(
					'members_csv' => array(
						'tmp_name' => 'jklflkkk.csv',
						'name'     => '/home/user1/csv_files/test-error-imports.csv',
					),
				),
				null,
				null,
				false,
				false,
			),
		);
	}

}
