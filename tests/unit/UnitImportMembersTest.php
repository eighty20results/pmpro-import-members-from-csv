<?php
namespace E20R\Test\Unit;

require_once __DIR__ . '/includes/BM_Base.php';
require_once __DIR__ . '/fixtures/fixtures.php';

use Brain\Monkey\Functions;
use E20R\Import_Members\Import_Members;
use PHPUnit\Framework;
use E20R\Test\Unit\Includes\BM_Base;

// Functions to import from other namespaces
use function E20R\Test\Unit\Fixtures\plugin_row_meta_data;


class UnitImportMembersTest extends BM_Base {
	
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
		
		Functions\when('get_transient' )
			->justReturn( '/var/www/html/wp-content/uploads/e20r_import/example_file.csv' );
		
		Functions\when( 'wp_upload_dir' )
			->justReturn(
				array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads'
				)
			);
		Functions\expect( 'get_option' )
#			->with( 'e20r_link_for_sponsor' )
#			->once()
			->andReturn( 'https://www.paypal.com/cgi-bin/webscr' );
		
		Functions\expect( 'update_option' )
			->andReturn( true );
#			->with( 'e20r_link_for_sponsor' );
#			->once();
	}
	
	/**
	 * Load all needed source files for the unit test
	 */
	public function loadTestSources(): void {
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-error-log.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/class-variables.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-csv.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-page.php';
		require_once __DIR__ . BASE_SRC_PATH . '/src/import/class-ajax.php';
		require_once __DIR__ . BASE_SRC_PATH . '/class.pmpro-import-members.php';
	}
	
	/**
	 * Define function stubs for the unit test
	 */
	public function loadStubs() : void {
		Functions\stubs(
			[
				'esc_attr',
				'esc_html',
				'esc_textarea',
				'__',
				'_x',
				'esc_html__',
				'esc_html_x',
				'esc_attr_x',
				'plugin_dir_path' => function() { return '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv'; },
				'esc_url'  => function() { return 'https://localhost:7537'; },
				'esc_url_raw' => function() { return 'https://localhost:7537'; },
				'get_transient' => function() { return '/var/www/html/wp-content/uploads/e20r_import/example_file.csv'; },
				'wp_upload_dir' => function() { return array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
				);
				},
				'register_deactivation_hook' => '__return_true'
			]
		);
	}
}

// Instantiate the Unit test class we defined
$bm = new UnitImportMembersTest();

/**
 * Tests the plugin_row_meta() method in Import_Members()
 */
$bm->it(
	'should test that we generate the expected plugin metadata',
	function () {
		
		$fixture_list = plugin_row_meta_data();
		
		foreach ( $fixture_list as $fixture ) {
			# echo "Fixture: " . print_r( $fixture, true );
			list( $row_meta_list, $file_name, $expected_result ) = $fixture;
			$class    = Import_Members::get_instance();
			$row_list = $class->plugin_row_meta( $row_meta_list, $file_name );
			
			$result = \count( $row_list );
			echo "Result: {$result} for row list: " . print_r( $row_list, true  );
			Framework\assertEquals( $expected_result, $result );
		}
	}
);
// $bm->tearDown();