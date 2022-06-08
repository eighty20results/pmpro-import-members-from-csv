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
namespace E20R\Tests\Unit;

if ( ! defined( 'E20R_UNITTEST_ROW_COUNT' ) ) {
	define( 'E20R_UNITTEST_ROW_COUNT', 0 );
}
use Brain\Monkey;
use Brain\Monkey\Functions;
use Codeception\Test\Unit;
use E20R\Import_Members\Data;
use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\PMPro\PMPro;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Process\Ajax;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Process\Page;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Variables;
use Mockery;
use WP_Mock;
use MemberOrder;
use E20R\Import_Members\Import;


use function Brain\Monkey\Functions\stubs;

class Import_UnitTest extends Unit {


	/**
	 * Mocked Error_Log() class
	 *
	 * @var null|Error_Log
	 */
	private $mocked_errorlog = null;

	/**
	 * Mocked Variables() class
	 *
	 * @var null|Variables
	 */
	private $mocked_variables = null;

	/**
	 * Mocked PMPro() class
	 *
	 * @var null|PMPro
	 */
	private $mocked_pmpro = null;

	/**
	 * Mocked PMPro() class
	 *
	 * @var null|Data
	 */
	private $mocked_data = null;

	/**
	 * Mocked Import_User() class
	 *
	 * @var null|Import_User
	 */
	private $mocked_import_user = null;

	/**
	 * Mocked Import_Member() class
	 *
	 * @var null|Import_Member
	 */
	private $mocked_import_member = null;

	/**
	 * Mocked CSV() class
	 *
	 * @var null|CSV
	 */
	private $mocked_csv = null;

	/**
	 * Mocked Email_Templates() class
	 *
	 * @var null|Email_Templates
	 */
	private $mocked_email_templates = null;

	/**
	 * Mocked Validate_Data() class
	 *
	 * @var null|Validate_Data
	 */
	private $mocked_validate_data = null;

	/**
	 * Mocked Page() class
	 *
	 * @var null|Page
	 */
	private $mocked_page = null;

	/**
	 * Mocked Ajax() class
	 *
	 * @var null|Ajax
	 */
	private $mocked_ajax = null;

	/**
	 * Mocked PMPro MemberOrder()
	 *
	 * @var null|MemberOrder
	 */
	private $mock_order = null;

	/**
	 * Codeception setUp method
	 *
	 * @return void
	 */
	public function setUp() : void {
		parent::setUp();
		WP_Mock::setUp();
		Monkey\setUp();
		$this->load_mocks();
		$this->load_stubs();
	}

	/**
	 * Codeception tearDown() method
	 *
	 * @return void
	 */
	public function tearDown() : void {
		WP_Mock::tearDown();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 */
	public function load_mocks() : void {

		if ( ! defined( 'E20R_IMPORT_PLUGIN_FILE' ) ) {
			define( 'E20R_IMPORT_PLUGIN_FILE', dirname( __FILE__ ) . '/../../../class.pmpro-import-members.php' );
		}

		$this->mocked_errorlog = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function ( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Mocked log: {$msg}" );
				},
			)
		);

		$this->mocked_variables = $this->makeEmpty(
			Variables::class
		);

		$this->mocked_pmpro = $this->makeEmpty(
			PMPro::class
		);

		$this->mocked_data = $this->makeEmpty(
			Data::class
		);

		$this->mocked_import_user = $this->makeEmpty(
			Import_User::class
		);

		$this->mocked_import_member = $this->makeEmpty(
			Import_Member::class
		);

		$this->mocked_csv = $this->makeEmpty(
			CSV::class
		);

		$this->mocked_email_templates = $this->makeEmpty(
			Email_Templates::class
		);

		$this->mocked_validate_data = $this->makeEmpty(
			Validate_Data::class
		);

		$this->mocked_page = $this->makeEmpty(
			Page::class
		);

		$this->mocked_ajax = $this->makeEmpty(
			Ajax::class
		);
	}

	/**
	 * Load all function mocks we need (with namespaces)
	 */
	public function load_stubs() : void {

		stubs(
			array(
				'get_transient'              => '/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
				'wp_upload_dir'              => function() {
					return array(
						'baseurl' => 'https://localhost:7537/wp-content/uploads/',
						'basedir' => '/var/www/html/wp-content/uploads',
					);
				},
				'register_deactivation_hook' => '__return_true',
				'get_option'                 => 'https://www.paypal.com/cgi-bin/webscr',
				'update_option'              => true,
			)
		);
	}


	/**
	 * Test the Import::is_pmpro_active() method
	 *
	 * @param string[] $active_plugin_list
	 * @param string[]|null $mocked_plugin_list
	 * @param bool $expected_result
	 *
	 * @dataProvider fixture_active_plugin_list
	 * @test
	 */
	public function it_should_verify_status_of_pmpro_plugin( $active_plugin_list, $mocked_plugin_list, $expected_result ) {

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

		$result = Import::is_pmpro_active( $mocked_plugin_list );
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

	/**
	 * It should test that the Import::plugin_row_meta() function is called
	 *
	 * @param string $file_name The file name for the plugin being processed by the (fake) filter
	 * @param array $default_row_meta The received array of plugin metadata
	 * @param array $expected The expected result after adding (or not) array entries for this plugin
	 *
	 * @dataProvider fixture_page_row_meta
	 * @test
	 */
	public function it_should_verify_plugin_page_row_meta( $file_name, $default_row_meta, $expected, $expected_count ) {

		Functions\when( 'plugin_dir_url' )
			->justReturn( 'https://localhost.local:7537/wp-content/plugins/pmpro-import-members-from-csv/' );
		Functions\when( 'plugin_dir_path' )
			->justReturn( '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv/' );
		Functions\when( 'esc_attr__' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );

		$import = new Import( $this->mocked_variables, $this->mocked_pmpro, $this->mocked_data, $this->mocked_import_user, $this->mocked_import_member, $this->mocked_csv, $this->mocked_email_templates, $this->mocked_validate_data, $this->mocked_page, $this->mocked_ajax, $this->mocked_errorlog );
		$result = $import->plugin_row_meta( $default_row_meta, $file_name );

		$result_count = ( count( $default_row_meta ) + 6 );

		// Make sure the array is what we expect it to be
		self::assertSame( $expected, $result );

		// These only make sense if the Import::plugin_row_meta() added values to the array
		if ( 1 === preg_match( '/class\.pmpro-import-members\.php/', $file_name ) ) {
			self::assertSame( $result_count, $expected_count );

			// Make sure we have the expected key entries in the array
			self::assertArrayHasKey( 'donate', $result );
			self::assertArrayHasKey( 'documentation', $result );
			self::assertArrayHasKey( 'filters', $result );
			self::assertArrayHasKey( 'actions', $result );
			self::assertArrayHasKey( 'help', $result );

			// Make sure the expected URLs are defined for the links
			self::assertStringContainsString( 'https://www.paypal.me/eighty20results', $result['donate'] );
			self::assertStringContainsString( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/', $result['documentation'] );
			self::assertStringContainsString( 'docs/FILTERS.md', $result['filters'] );
			self::assertStringContainsString( 'docs/ACTIONS.md', $result['actions'] );
			self::assertStringContainsString( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv', $result['help'] );
			self::assertStringContainsString( 'https://github.com/eighty20results/pmpro-import-members-from-csv/issues', $result['issues'] );
		}
	}

	/**
	 * Fixture for the it_should_verify_plugin_page_row_meta() test method
	 *
	 * NOTE: If using the fixture_dummy_entries() and the fixture_default_plugin_row_meta() fixtures, the ordering when combining the two
	 * actually matter!
	 *
	 * @return array[]
	 */
	public function fixture_page_row_meta() {
		return array(
			array( 'class.pmpro-import-members.php', array(), $this->fixture_default_plugin_row_meta(), 6 ),
			array( 'not-the-correct-plugin.php', array(), array(), 0 ), // Not the expected plugin
			array( 'pmpro-import-members-from-csv/class.pmpro-import-members.php', array(), $this->fixture_default_plugin_row_meta(), 6 ),
			array( 'class.pmpro-import-members.php', $this->fixture_dummy_entries( 1 ), $this->fixture_dummy_entries( 1 ) + $this->fixture_default_plugin_row_meta(), 7 ),
			array( 'class.pmpro-import-members.php', $this->fixture_dummy_entries( 10 ), $this->fixture_dummy_entries( 10 ) + $this->fixture_default_plugin_row_meta(), 16 ),
		);
	}

	/**
	 * Generates a number of dummy entries for the page_row_meta() method to receive
	 *
	 * @param int $count The number of entries to create
	 *
	 * @return string[]
	 */
	private function fixture_dummy_entries( $count = 1 ) {
		$return = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$return[ "dummy{$i}" ] = sprintf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				"https://example.com/dummy_{$i}/",
				'This is a dummy entry',
				"Dummy #{$i}"
			);
		}
		return $return;
	}

	/**
	 * The default row metadata used for the Import Members from CSV plugin entry. Should always be included!
	 *
	 * @return string[]
	 */
	private function fixture_default_plugin_row_meta() {
		return array(
			'donate'        => sprintf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				'https://www.paypal.me/eighty20results',
				'Donate to support updates, maintenance and tech support for this plugin',
				'Donate'
			),
			'documentation' => sprintf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				'https://wordpress.org/plugins/pmpro-import-members-from-csv/',
				'View the documentation',
				'Docs'
			),
			'filters'       => sprintf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				'https://localhost.local:7537/wp-content/plugins/pmpro-import-members-from-csv/docs/FILTERS.md',
				'View the Filter documentation',
				'Filters'
			),
			'actions'       => sprintf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				'https://localhost.local:7537/wp-content/plugins/pmpro-import-members-from-csv/docs/ACTIONS.md',
				'View the Actions documentation',
				'Actions'
			),
			'help'          => sprintf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				'https://wordpress.org/support/plugin/pmpro-import-members-from-csv',
				'Visit the support forum',
				'Support'
			),
			'issues'        => sprintf(
				'<a href="%1$s" title="%2$s" target="_blank">%3$s</a>',
				'https://github.com/eighty20results/pmpro-import-members-from-csv/issues',
				'Report bugs for this plugin',
				'Report Bugs'
			),
		);
	}
}
