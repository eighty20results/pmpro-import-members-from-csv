<?php
/*
 *  Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

namespace E20R\Test\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class ErrorLog_UnitTest extends \Codeception\Test\Unit {

	use MockeryPHPUnitIntegration;

	private $log_file_path = '/var/log/www/wp-content/uploads/e20r_im_errors.log';

	/**
	 * Codeception setUp() method
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->loadTestSources();
	}

	/**
	 * Codeception tearDown() method
	 */
	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load all needed source files for the unit test
	 */
	public function loadTestSources(): void {
		require_once __DIR__ . '/../../../inc/autoload.php';
		require_once __DIR__ . '/../../../src/class-error-log.php';
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

	public function test_log_errors() {

	}

	public function test_debug() {

	}

	public function test_add_error_msg() {

	}

	public function test_display_admin_message() {

	}
}
