<?php
/**
 * Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

use Brain\Monkey;
use Brain\Monkey\Functions;
use Codeception\Test\Unit;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use stdClass;
use WP_Mock;


use E20R\Import_Members\Email\Template_Data;
use E20R\Import_Members\Error_Log;
use WP_User;
use MemberOrder;

/**
 * Class Imported_Data_Test
 * @package E20R\Test\Unit
 */
class Template_Data_UnitTest extends Unit {

	use MockeryPHPUnitIntegration;

	/**
	 * Mocked Error_Log() class
	 *
	 * @var null|Error_Log
	 */
	private $errlog_mock = null;

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
		$this->loadMocks();
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
	 * Load all function mocks we need (with namespaces)
	 */
	public function loadMocks() : void {

		$this->errlog_mock = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Mocked log: {$msg}" );
				},
			)
		);

		$this->mock_order = $this->makeEmpty(
			MemberOrder::class,
			array(
				'getLastMemberOrder' => $this->fixture_pmpro_order(),
				'setGateway'         => false,
				'getRandomCode'      => $this->random_strings( 10 ),
				'get_test_order'     => $this->fixture_pmpro_order(),
			)
		);

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

	}

	/**
	 * Validates the Template_Data class and it's methods and attributes
	 *
	 * @param array $methods List of methods to look for
	 * @param array $attributes List of class properties to look for
	 * @param array $instantiated_classes List of classes to look for
	 *
	 * @dataProvider fixture_template_data_create
	 * @test
	 */
	public function it_should_validate_template_data_class( $methods, $attributes, $instantiated_classes ) {

		try {
			Functions\expect( 'get_option' )
				->with( Mockery::contains( 'blogname' ) )
				->andReturn( 'E20R Import Members from CSV Test Site' );
		} catch ( \Exception $e ) {
			echo 'Error: ' . $e->getMessage(); // phpcs:ignore
		}

		Functions\when( 'apply_filters' )
			->returnArg( 1 );


		$user      = $this->create_mocked_wp_users( 1 );
		$user_meta = $this->fixture_user_meta();

		Functions\when( 'wp_get_current_user' )
			->justReturn( $user );

		Functions\when( 'get_user_meta' )
			->justReturn( $user );

		$test_order = $this->mock_order->get_test_order( $user );

		$template_data_class = new Template_Data( $test_order, $user, $user_meta, $this->errlog_mock );
		$this->assertInstanceOf( 'E20R\\Import_Members\\Email\\Template_Data', $template_data_class );

		foreach ( $instantiated_classes as $namespaced_class => $class_name ) {
			$this->assertInstanceOf( $namespaced_class, $template_data_class->get( $class_name ) );
		}

		foreach ( $attributes as $attribute ) {
			$this->assertClassHasAttribute( $attribute, 'E20R\\Import_Members\\Email\\Template_Data' );
		}

		foreach ( $methods as $class_method ) {
			$this->assertTrue(
				method_exists( $template_data_class, $class_method ),
				"{$class_method}() method not found in " . get_class( $template_data_class )
			);
		}
	}

	/**
	 * Fixture defining what the Template_Data() class looks like
	 *
	 * @return array[]
	 */
	public function fixture_template_data_create() {

		$methods              = array( 'configure_objects', 'default_site_field_values', 'get', 'load_field_aliases' );
		$attributes           = array( 'user', 'order', 'meta', 'field_aliases', 'site_fields', 'error_log' );
		$instantiated_classes = array(
			'E20R\Import_Members\Error_Log' => 'error_log',
		);

		return array(
			array( $methods, $attributes, $instantiated_classes ),
		);
	}

	/**
	 * Test whether the correct path is returned for the Import file specified
	 * (no user, order or user meta data supplied in constructor)
	 *
	 * @param WP_User $user The mocked user
	 * @param string  $site_name The name of the site
	 * @param string  $wp_login_url The URL to the site's login page
	 * @param string  $levels_link The URL to the site's levels settings page
	 * @param string  $from_email The email address for the user
	 * @param string  $display_name The display_name for the user
	 * @param array   $default_site_field_keys The keys to use/test for
	 * @param mixed   $expected_results The expected test results
	 *
	 * @dataProvider fixture_default_site_field_values
	 * @test
	 */
	public function it_should_set_default_site_field_values( $user, $site_name, $wp_login_url, $levels_link, $from_email, $display_name, $default_site_field_keys, $expected_results ) {

		if ( ! empty( $user ) ) {
			global $current_user;
			// For testing purposes
			$current_user = $user; // phpcs:ignore
		}

		try {
			Functions\expect( 'get_option' )
				->with( Mockery::contains( 'blogname' ) )
				->andReturn( $site_name );
		} catch ( \Exception $e ) {
			echo 'Error: ' . $e->getMessage(); // phpcs:ignore
		}

		Functions\when( 'wp_login_url' )
			->justReturn( $wp_login_url );

		Functions\expect( 'pmpro_url' )
			->with( Mockery::contains( 'levels' ) )
			->andReturn( $levels_link );

		Functions\expect( 'pmpro_getOption' )
			->with( Mockery::contains( 'from_email' ) )
			->andReturn( $from_email );

		Functions\when( 'wp_get_current_user' )
			->justReturn( $user );

		Functions\when( 'get_user_meta' )
			->justReturn( $user );

		// Instantiate our class
		$test_order    = $this->mock_order->get_test_order( $user );
		$imported_data = new Template_Data( $test_order, $user, $this->fixture_user_meta() );
		$result        = $imported_data->default_site_field_values();

		// Make sure the returned data is an array (list)
		$this->assertIsArray( $result );

		// Make sure all of the expected default array keys are present for the site specific values
		foreach ( $default_site_field_keys as $key_name ) {
			$this->assertArrayHasKey( $key_name, $result );
		}

		$this->assertEquals( $result, $expected_results );
	}

	/**
	 * Fixture for the CSV::get_import_file_path() unit test
	 *
	 * Expected info for fixture:
	 *      string full path, (string) $expected_file_name
	 *
	 * @return array[]
	 *
	 * @see https://github.com/Brain-WP/BrainFaker
	 */
	public function fixture_default_site_field_values() {

		$user_list               = $this->create_mocked_wp_users( 5 );
		$default_site_field_keys = array( 'sitename', 'siteemail', 'login_link', 'levels_link', 'name' );
		$fixture                 = array();

		// Generate list of fixures (driven by user info)
		foreach ( $user_list as $user ) {
			$fixture[] = array(
				$user,
				'Test Site',
				'https://mytestwebsite.com/wp-login.php',
				'https://mytestwebsite.com/membership/membership_levels/',
				'admin@mytestwebsite.com',
				$user->display_name,
				$default_site_field_keys,
				array(
					'sitename'    => 'Test Site',
					'siteemail'   => 'admin@mytestwebsite.com',
					'login_link'  => 'https://mytestwebsite.com/wp-login.php',
					'levels_link' => 'https://mytestwebsite.com/membership/membership_levels/',
					'name'        => $user->display_name,
				),
			);
		}

		return $fixture;
	}

	/**
	 * Create mocked WP_User objects
	 *
	 * @param int $count The number of mocked WP_User objects to create and return
	 *
	 * @return array
	 */
	private function create_mocked_wp_users( $count = 1 ) {
		$user_list = array();
		$base_id   = 1000;

		for ( $i = 0; $i < $count; $i++ ) {
			$id = ( $base_id + $i );

			if ( class_exists( '\WP_User' ) ) {
				$user = $this->construct( WP_User::class,
				array( $id )
				);
			} else {
				$user     = new stdClass();
				$user->ID = $id;
			}

			$user->user_firstname   = 'Test';
			$user->first_name       = $user->user_firstname;
			$user->user_lastname    = "User # {$id}";
			$user->last_name        = $user->user_lastname;
			$user->user_description = "{$user->user_firstname} {$user->user_lastname}";
			$user->display_name     = "{$user->user_firstname} {$user->user_lastname}";
			$user->user_email       = "test_{$id}@localhost.local";

			if ( $count > 1 ) {
				$user_list[] = $user;
			} else {
				$user_list = $user;
			}
		}

		return $user_list;
	}

	/**
	 * Generate mock/invalid key/value pairs, representing user metadata
	 * @return array
	 */
	private function fixture_user_meta() {

		$user_meta = array(
			'membership_id'   => 1,
			'pmpro_baddress1' => '1020 1st Street',
			'pmpro_baddress2' => 'Apt 10',
			'pmpro_bcity'     => 'Oslo',
			'pmpro_bstate'    => 'Oslo',
			'pmpro_bzipcode'  => '0581',
			'pmpro_bcountry'  => 'Norway',
		);

		return $user_meta;
	}
	/**
	 * Generate an alphanumeric string
	 *
	 * @param int $length_of_string
	 *
	 * @return string
	 */
	private function random_strings( $length_of_string ) {
		// String of all alphanumeric character
		$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		// Shuffle the $str_result and returns substring
		// of specified length
		return substr(
			str_shuffle( $str_result ),
			0,
			$length_of_string
		);
	}

	/**
	 * Test of the Template_Data() class when user meta has been saved
	 *
	 * @param WP_User     $user User object from PMPro
	 * @param \MemberOrder $order PMPro Member Order object
	 * @param array       $user_meta WP User Metadata
	 * @dataProvider fixture_configure_objects
	 *
	 * @test
	 */
	public function it_should_configure_objects_for_template( $user, $order, $user_meta ) {

		Functions\expect( 'pmpro_getOption' )
			->with( Mockery::contains( 'from_email' ) )
			->andReturn( $user->user_email );

		Functions\expect( 'pmpro_getOption' )
			->with( Mockery::contains( 'gateway' ) )
			->andReturn( 'stripe' );

		Functions\expect( 'pmpro_getOption' )
			->with( Mockery::contains( 'gateway_environment' ) )
			->andReturn( 'test' );

		Functions\when( 'get_user_meta' )
			->justReturn( $user_meta );

		Functions\when( 'get_current_user' )
			->justReturn( $user );

		Functions\when( 'wp_get_current_user' )
			->justReturn( $user );

		$imported_data = new Template_Data( $order, $user, $user_meta, $this->errlog_mock );

		$this->assertEquals( $user->ID, $imported_data->get( 'ID', 'user' ) );
		$this->assertEquals( $user->display_name, $imported_data->get( 'display_name', 'user' ) );
		$this->assertEquals( $order->id, $imported_data->get( 'id', 'order' ) );
		$this->assertEquals( $user_meta['membership_id'], $imported_data->get( 'membership_id', 'meta' ) );
	}

	/**
	 * Fixture to generate User, \MemberOrder and user metadata objects/arrays
	 *
	 * @return array[]
	 */
	public function fixture_configure_objects() {
		$user_list = $this->create_mocked_wp_users( 5 );
		$user_meta = $this->fixture_user_meta();
		$fixture   = array();

		foreach ( $user_list as $user ) {
			echo "Adding user: {$user->display_name}";
			$order     = $this->fixture_pmpro_order( $user );
			$fixture[] = array( $user, $order, $user_meta );
		}

		return $fixture;
	}

	/**
	 * Generate mock/fake PMPro MemberOrder object
	 *
	 * @param \WP_User|null $user User information
	 *
	 * @return \MemberOrder
	 */
	public function fixture_pmpro_order( $user = null ) {

		if ( null === $user ) {
			$user = $this->create_mocked_wp_users( 1 );
		}

		if ( empty( $this->mock_order ) ) {
			$this->mock_order = $this->construct(
				MemberOrder::class,
				array(),
				array(
					'getLastMemberOrder' => $this->mock_order,
					'setGateway'         => false,
					'getRandomCode'      => $this->random_strings( 10 ),
					'get_test_order'     => $this->mock_order,
				)
			);
		}
		$this->mock_order->set( 'id', 1 );
		$this->mock_order->set( 'user_id', $user->ID );
		$this->mock_order->set( 'FirstName', $user->first_name );
		$this->mock_order->set( 'LastName', $user->last_name ); // phpcs:ignore
		$this->mock_order->set( 'Email', $user->user_email ); // phpcs:ignore
		$this->mock_order->set( 'membership_id', 1 );
		$this->mock_order->set( 'billing', '' );
		$this->mock_order->set( 'billing_name', $user->display_name );
		$this->mock_order->setGateway( 'stripe' );

		return $this->mock_order;
	}
}
