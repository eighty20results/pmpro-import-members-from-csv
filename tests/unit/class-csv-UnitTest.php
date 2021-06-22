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
use Codeception\Stub\Expected;
use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Error_Log;
use E20R\Test\Unit\Test_In_A_Tweet\TFIAT;
use AspectMock\Test as test;

// Functions to import from other namespaces
use function E20R\Test\Unit\Fixtures\import_file_names;
use function PHPUnit\Framework\assertEquals;
use function Brain\Monkey\Functions\stubEscapeFunctions;

class CSV_UnitTest extends TFIAT {

	/**
	 * Load test fixtures for Unit tests
	 */
	public function loadFixtures(): void {
		require_once __DIR__ . '/fixtures/import-file-names.php';
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
		require_once __DIR__ . BASE_SRC_PATH . '/inc/autoload.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-error-log.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-csv.php';

	}

}

// Instantiate the Unit test class we defined
$bm = new CSV_UnitTest();

/**
 * Unit test for CSV::get_import_file_path() method
 * @unit
 */
$bm->it(
	'should verify that file names are returned correctly',
	function() use ( $bm ) {
		// phpcs:ignore
		$bm->make('E20R\\Import_Members\\Error_Log', array( 'debug' => null ) );

		$fixture_list = import_file_names();

		foreach ( $fixture_list as $fixture ) {
			list( $file_path, $expected_result ) = $fixture;

			$mocked_variables = $bm->makeEmpty(
				'E20R\\Import_Members\\Variables',
				array(
					'get' => $file_path,
				)
			);

			$csv    = new CSV();
			$result = $csv->get_import_file_path( $file_path );

			assertEquals( $expected_result, $result );
		}
	}
);
