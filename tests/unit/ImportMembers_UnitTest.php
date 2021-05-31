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
use E20R\Import_Members\Import_Members;
use E20R\Test\Unit\Test_In_A_Tweet\TFIAT;

// Functions to import from other namespaces
use function E20R\Test\Unit\Fixtures\plugin_row_meta_data;
use function PHPUnit\Framework\assertEquals;
use function Brain\Monkey\Functions\stubEscapeFunctions;

class ImportMembers_UnitTest extends TFIAT {

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

		Functions\when( 'get_transient' )
			->justReturn( '/var/www/html/wp-content/uploads/e20r_import/example_file.csv' );

		Functions\when( 'wp_upload_dir' )
			->justReturn(
				array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads',
				)
			);
		Functions\expect( 'get_option' )
			->with( 'e20r_link_for_sponsor' )
			->once()
			->andReturn( 'https://www.paypal.com/cgi-bin/webscr' );

		Functions\expect( 'update_option' )
			->andReturn( true )
			->with( 'e20r_link_for_sponsor' )
			->once();
	}

	/**
	 * Load all needed source files for the unit test
	 */
	public function loadTestSources(): void {
		require_once __DIR__ . '/fixtures/plugin_row_meta_data.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-import-members.php';

		require_once __DIR__ . BASE_SRC_PATH . '/src/class-error-log.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-variables.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-csv.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-page.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-ajax.php';
		require_once __DIR__ . BASE_SRC_PATH . '/class.pmpro-import-members.php';
		require_once __DIR__ . BASE_SRC_PATH . '/inc/autoload.php';
	}

	/**
	 * Define function stubs for the unit test
	 */
	public function loadStubs() : void {
		Functions\stubs(
			array(
				'plugin_dir_path'            => function() {
					return '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv';
				},
				'esc_url'                    => function() {
					return 'https://localhost:7537';
				},
				'esc_url_raw'                => function() {
					return 'https://localhost:7537';
				},
				'get_transient'              => function() {
					return '/var/www/html/wp-content/uploads/e20r_import/example_file.csv';
				},
				'wp_upload_dir'              => function() {
					return array(
						'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					);
				},
				'register_deactivation_hook' => '__return_true',
			)
		);
	}
}

// Instantiate the Unit test class we defined
$bm = new ImportMembers_UnitTest();

/**
 * Tests the plugin_row_meta() method in Import_Members()
 * @dataProvider \E20R\Test\Unit\Fixtures\plugin_row_meta_data()
 */
$bm->it(
	'should test that we generate the expected plugin metadata',
	function () {

		$fixture_list = plugin_row_meta_data();

		foreach ( $fixture_list as $fixture ) {
			list( $row_meta_list, $file_name, $expected_result ) = $fixture;
			$class    = Import_Members::get_instance();
			$row_list = $class->plugin_row_meta( $row_meta_list, $file_name );
			$result   = \count( $row_list );
			assertEquals( $expected_result, $result );
		}
	}
);