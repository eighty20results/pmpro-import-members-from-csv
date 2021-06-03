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

require_once __DIR__ . '/Test_Framework_In_A_Tweet/TFIAT.php';

use Brain\Monkey\Functions;
use Codeception\Stub;
use E20R\Import_Members\Import_Members;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Error_Log;
use E20R\Test\Unit\Test_In_A_Tweet\TFIAT;
use Mockery;

// Functions to import from other namespaces
use function E20R\Test\Unit\Fixtures\import_file_names;
use function E20R\Test\Unit\Fixtures\request_settings;
use function PHPUnit\Framework\assertEquals;
use function Brain\Monkey\Functions\stubEscapeFunctions;

class Variables_UnitTest extends TFIAT {

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

//		Functions\when( 'gmdate' )
//			->justReturn( function( $format, $time ) {
//				return date( $format, time() );
//			});
	}

	/**
	 * Load all needed source files for the unit test
	 */
	public function loadTestSources(): void {
		require_once __DIR__ . BASE_SRC_PATH . '/inc/autoload.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-error-log.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-csv.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-variables.php';
	}

	/**
	 * Load fixtures for the unit test
	 */
	public function loadFixtures(): void {
		require_once __DIR__ . '/fixtures/import-file-names.php';
		require_once __DIR__ . '/fixtures/request-settings.php';
	}
}

// Instantiate the Unit test class we defined
$bm = new Variables_UnitTest();

/**
 * Tests the plugin_row_meta() method in Import_Members()
 * @dataProvider \E20R\Test\Unit\Fixtures\import_file_names
 */
$bm->it(
	'should verify that the settings defined are from the $_REQUEST variable',
	function () use ( $bm ) {

		$error_log = Stub::construct( new Error_Log() ); // phpcs:ignore
		$error_log->method( 'debug' )
			->willReturn( null );

		$file_fixture     = import_file_names();
		$request_fixtures = request_settings();

		foreach ( $file_fixture as $fixture_id => $file_name_info ) {
			$csv = Stub::construct(
				new CSV(),
				array(
					'pre_process_file' => $file_name_info[1],
				)
			);

			list( $file_array, $expected_file_name) = $file_name_info;

			$csv->expects( $this->any )
			    ->method( 'pre_process_file' )
			    ->with( $file_array['member_csv']['name'] )
			    ->willReturn( $expected_file_name );

			$_FILES = $_FILES + $file_array; // phpcs:ignore
			echo 'File data: ' . print_r( $_FILES, true ); // phpcs:ignore
			$_REQUEST        = $_REQUEST + $request_fixtures[ $fixture_id ]; // phpcs:ignore
			$expected_result = $request_fixtures[ $fixture_id ];
			echo "Request data (including for index ${fixture_id}): " . print_r( $_REQUEST, true ); // phpcs:ignore
			$variables = new Variables();
			$result    = $variables->get_request_settings();

			assertEquals( $expected_result, $result );
		}
	}
);