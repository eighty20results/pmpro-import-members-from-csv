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

require_once __DIR__ . '/../../inc/autoload.php';
require_once __DIR__ . '/Test_Framework_In_A_Tweet/TFIAT.php';

require_once __DIR__ . '/fixtures/request-settings.php';
require_once __DIR__ . '/fixtures/import-file-names.php';

use Brain\Monkey\Functions;
use Codeception\TestCase;
use AspectMock\Test as test;
use E20R\Import_Members\Data;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Error_Log;
use E20R\Test\Unit\Test_In_A_Tweet\TFIAT;

use Exception;
use function E20R\Test\Unit\Fixtures\import_file_names;
use function E20R\Test\Unit\Fixtures\request_settings;

class Variables_UnitTest extends TFIAT {

	/**
	 * Mock for the Error_Log class
	 *
	 * @var null|Error_Log $this->error_log_mock_mock
	 */
	private $error_log_mock = null;

	/**
	 * Mock for the CSV class
	 *
	 * @var null|CSV
	 */
	private $csv_mock = null;

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

	public function loadFixtures(): void {
		// TODO: Implement loadFixtures() method.
	}

	public function loadTestSources(): void {
		// TODO: Implement loadTestSources() method.
	}

	/**
	 * Test the happy pathf for the is_configured method
	 * @dataProvider fixture_is_configured
	 */
	public function test_is_configured( $request_variables, $file_info ) {

		$tmp_file_name = $file_info[0]['members_csv']['tmp_name'];
		$file_name     = $file_info[0]['members_csv']['name'];

		$this->error_log_mock = \Mockery::mock( \E20R\Import_Members\Error_Log::class )
										->makePartial();
		$this->error_log_mock
			->shouldReceive( 'debug' )
			->with( 'Instantiating the Variables class' );
//			->once();
//
//		$this->error_log_mock
//			->shouldReceive( 'debug' )
//			->with( "Will redirect since we're processing in the background" )
//			->once();
//
//		$this->error_log_mock
//			->shouldReceive( 'debug' )
//			->with( "Will redirect since we're processing in the background" )
//			->once();
//
//		$this->error_log_mock
//			->shouldReceive( 'debug' )
//			->with( "Before processing FILES array:  vs {$tmp_file_name}" )
//			->once();

		$this->error_log_mock
			->shouldReceive( 'add_error_msg' )
//			->once()
			->with( 'CSV file not selected. Nothing to import!', 'error' );

		$this->data_mock = \Mockery::mock( \E20R\Import_Members\Data::class )
			->makePartial();
		//
		//      $this->error_log_mock->debug( 'Instantiating the Variables class' );
		//      $this->error_log_mock->debug( 'The settings have been instantiated already' );
		//      $this->error_log_mock->debug( "Will redirect since we're processing in the background" );
		//      $this->error_log_mock->debug( 'Nothing to do without an import file!' );
		//      $this->error_log_mock->debug( "Before processing FILES array:  vs {$tmp_file_name}" );
		//      $this->error_log_mock->debug( "Will redirect since we're processing in the background" );
		//      $this->error_log_mock->add_error_msg(
		//          __( 'CSV file not selected. Nothing to import!', 'pmpro-import-members-from-csv' ),
		//          'error'
		//      );
		//
		//      if ( 1 === $request_variables['update_users'] ) {
		//          $this->error_log_mock->debug( 'Settings users update to: True' )->willReturn( false );
		//      } else {
		//          $this->error_log_mock->debug( 'Settings users update to: False' )->willReturn( false );
		//      }
		//      if ( 1 === $request_variables['suppress_pwdmsg'] ) {
		//          $this->error_log_mock->debug( 'Do we suppress the changed password email? Yes' )->willReturn( false );
		//      } else {
		//          $this->error_log_mock->debug( 'Do we suppress the changed password email? No' )->willReturn( false );
		//      }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_REQUEST = $_REQUEST + $request_variables;
		$_FILES   = $_FILES + $file_info[0];
		$expected = ! empty( $file_info[1] );

		$this->it(
			'should verify that a file name is configured',
			function() use ( $expected, $file_name ) {
				$variables = new Variables();

				$this->csv_mock = \Mockery::mock( \E20R\Import_Members\Import\CSV::class )->makePartial();

				$this->csv_mock
					->shouldReceive( '__construct' )
					->with( $variables )
					->once();

				$result = $variables->is_configured();
				$this->assertEquals( $expected, $result );
			}
		);
	}

	/**
	 * Fixture for the Variables::is_configured method
	 * @return array
	 */
	public function fixture_is_configured() : array {

		$fixture   = array();
		$file_info = import_file_names();

		foreach ( request_settings() as $key_id => $request ) {
			$fixture[] = array( $request, $file_info[ $key_id ] );
		}

		return $fixture;
	}
}
