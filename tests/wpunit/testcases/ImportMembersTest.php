<?php
/*
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

namespace E20R\Test;

use E20R\Import_Members\Import_Members;
use Codeception\Test\Unit;
use Brain\Monkey\Functions;


class ImportMembersTest extends Unit {
	
	public function tearDown(): void {
		parent::tearDown(); // TODO: Change the autogenerated stub
		\Mockery::close();
	}
	
	public function test_remove_IUCSV_support() {
	
	}
	
	public function test_get_instance() {
	
	}
	
	public function test_load_hooks() {
	
	}
	
	
	public function testAdmin_enqueue_scripts() {
	
	}
	
	/**
	 * Unit test for the Import_Members::is_pmpro_active() member function
	 *
	 * @param bool $expected
	 * @param bool $dont_mock
	 * @param string[] $plugin_list
	 *
	 * @dataProvider fixture_plugin_data
	 */
	public function test_is_pmpro_active( $expected, $dont_mock, $plugin_list = null ) {
		
		if ( false === $dont_mock ) {
			Functions\expect( 'get_site_option' )
				->with( 'active_plugins' )
				->andReturn( $plugin_list );
		}
		
		$result = Import_Members::is_pmpro_active();
		self::assertEquals( $expected, $result );
	}
	
	/**
	 * The fixture for the test_is_pmpro_active function
	 *
	 * @return array[]
	 */
	public function fixture_plugin_data() {
		return array(
			// $expected, $dont_mock, $plugin_list
			array( true, true, null ),
			array( false, false, array(
				'00-e20r-utilities/class-loader.php',
				'pmpro-daily-something/pmpro-daily-something.php',
				'woocommerce/woocommerce.php'
				)
			),
		);
	}
	
	public function test_load_i18n() {
	
	}
	
	/**
	 * Deactivation unit test (ensure the correct delete_option invocations are run)
	 */
	public function test_deactivation() {
		
		Functions\expect('delete_option' )
			->once()
			->with( 'e20r_import_has_donated' );
		
		Functions\expect('delete_option' )
			->once()
			->with( 'e20r_link_for_sponsor' );
		
		Import_Members::deactivation();
	}
}
