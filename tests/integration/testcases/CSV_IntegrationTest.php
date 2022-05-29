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

use Codeception\TestCase\WPTestCase;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Variables;
use E20R\Tests\Integration\Fixtures\Manage_Test_Data;

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
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp() : void {
		$this->test_data = new Manage_Test_Data();

		parent::setUp();
		$this->load_mocks();

		$this->errorlog->debug( 'Make sure the expected DB tables exist' );
		$this->test_data->tables_exist();

		// Insert membership level data
		$this->test_data->set_line();
		$this->test_data->insert_level_data();
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
	 * Happy path for CSV::process() method
	 *
	 * @return void
	 *
	 * @dataProvider fixture_process_test_data
	 *
	 * @test
	 */
	public function it_should_successfully_process_csv_file() {

	}

	/**
	 * Fixture for the it_should_successfully_process_csv_file()
	 * @return array[]
	 */
	public function fixture_process_test_data() {
		return array(
			array(),
		);
	}
}
