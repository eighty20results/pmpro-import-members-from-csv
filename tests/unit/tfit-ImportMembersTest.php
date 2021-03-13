<?php

require_once __DIR__ . '/../inc/PluginTestCase.php';
require_once __DIR__ . '/../inc/fixtures.php';

use Brain\Monkey\Functions;
use E20R\Import_Members\Import_Members;
use E20R\Test\Fixtures;
use PHPUnit\Framework;

/**
 * Unit test for the plugin_row_meta member class
 *
 * @param string[] $row_meta_list
 * @param int      $expected_results
 *
 * @test
 */
bm_it(
	'should test whether we load the right number of pieces of plugin metadata',
	function () {
		Functions\stubs(
			array(
				'\\plugin_dir_path' => '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv',
				'\\esc_attr__',
			)
		);

		$fixture_list = Fixtures\fixture_plugin_row_meta_data();

		foreach ( $fixture_list as $fixture ) {
			list( $row_meta_list, $file_name, $expected_results ) = $fixture;
			$class    = Import_Members::get_instance();
			$row_list = $class->plugin_row_meta( $row_meta_list, $file_name );

			$result = \count( $row_list );
			Framework\assertEquals( $expected_results, $result );
		}
	}
);