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

if ( ! defined( 'E20R_UNITTEST_ROW_COUNT' ) ) {
	define( 'E20R_UNITTEST_ROW_COUNT', 0 );
}
use Brain\Monkey;
use Brain\Monkey\Functions;
use Codeception\Test\Unit;
use Mockery;
use E20R\Import_Members\Data;
use E20R\Import_Members\Import_Members;
use E20R\Test\Unit\Test_In_A_Tweet\TFIAT;

// Functions to import from other namespaces
use function PHPUnit\Framework\assertEquals;
use function Brain\Monkey\Functions\stubEscapeFunctions;

class ImportMembers_UnitTest extends Unit {


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
		require_once __DIR__ . '/../../../inc/autoload.php';
		require_once __DIR__ . '/../../../src/class-import-members.php';
	}

	/**
	 * Define function stubs for the unit test
	 */
	public function loadStubs() : void {
		Functions\stubs(
			array(
				'__'                         => null,
				'_e'                         => null,
				'_ex'                        => null,
				'_x'                         => null,
				'_n'                         => null,
				'_nx'                        => null,
				'translate'                  => null,
				'esc_html__'                 => null,
				'esc_html_x'                 => null,
				'esc_attr__'                 => null,
				'esc_attr_x'                 => null,
				'esc_html_e'                 => null,
				'esc_attr_e'                 => null,
				'get_transient'              => '/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
				'plugin_dir_path'            => function() {
					return '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv';
				},
				'esc_url'                    => function() {
					return 'https://localhost:7537';
				},
				'esc_url_raw'                => function() {
					return 'https://localhost:7537';
				},
				'wp_upload_dir'              => function() {
					return array(
						'baseurl' => 'https://localhost:7537/wp-content/uploads/',
						'basedir' => '/var/www/html/wp-content/uploads',
					);
				},
				'register_deactivation_hook' => '__return_true',
			)
		);
	}

	/**
	 * Test the is_pmpro_active() method
	 *
	 * @param string[] $active_plugin_list
	 * @param string[]|null $mocked_plugin_list
	 * @param bool $expected_result
	 *
	 * @dataProvider fixture_active_plugin_list
	 */
	public function test_is_pmpro_active( $active_plugin_list, $mocked_plugin_list, $expected_result ) {

		if ( empty( $mocked_plugin_list ) ) {
			try {
				Functions\expect( 'get_site_option' )
					->with( Mockery::contains( 'active_plugins' ) )
					->once()
					->andReturn( $active_plugin_list );
			} catch ( \Exception $e ) {
				echo 'Error: ' . $e->getMessage(); // phpcs:ignore
			}
		}

		$result = Import_Members::is_pmpro_active( $mocked_plugin_list );
		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Fixture for the test_is_pmpro_active() unit test
	 *
	 * @return array[]
	 */
	public function fixture_active_plugin_list() {
		return array(
			array(
				// active_plugin_list, mocked_plugin_list, expected_result
				array(
					'00-e20r-utilities/class-loader.php',
					'paid-memberships-pro/paid-memberships-pro.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				null,
				true,
			),
			array(
				// active_plugin_list, mocked_plugin_list, expected_result
				array(
					'00-e20r-utilities/class-loader.php',
					'Paid-Memberships-Pro/paid-memberships-pro.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				null,
				false,
			),
			array(
				// active_plugin_list, mocked_plugin_list, expected_result
				array(
					'00-e20r-utilities/class-loader.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				null,
				false,
			),
			array(
				// active_plugin_list, mocked_plugin_list, expected_result
				array(
					'00-e20r-utilities/class-loader.php',
					'paid-memberships-pro/paid-memberships-pro.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				array(
					'00-e20r-utilities/class-loader.php',
					'paid-memberships-pro/paid-memberships-pro.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				true,
			),
			array(
				// active_plugin_list, mocked_plugin_list, expected_result
				array(
					'00-e20r-utilities/class-loader.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				array(
					'00-e20r-utilities/class-loader.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				false,
			),
			array(
				// active_plugin_list, mocked_plugin_list, expected_result
				array(
					'00-e20r-utilities/class-loader.php',
					'Paid-Memberships-Pro/paid-memberships-pro.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				array(
					'00-e20r-utilities/class-loader.php',
					'Paid-Memberships-Pro/paid-memberships-pro.php',
					'e20r-members-list/class.e20r-members-list.php',
				),
				false,
			),
		);

	}
}
