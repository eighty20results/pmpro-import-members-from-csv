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

use Brain\Monkey\Functions;
use Codeception\Test\Unit;
use Brain\Monkey;
use Mockery;

use E20R\Import_Members\Email\Template_Data;
use E20R\Import_Members\Error_Log;
use function Brain\faker;


/**
 * Class Imported_Data_Test
 * @package E20R\Test\Unit
 */
class Template_Data_UnitTest extends Unit {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * @see https://github.com/Brain-WP/BrainFaker
	 */
	protected $wp_faker;

	private $faker;

	/**
	 * Codeception _before() method
	 */
	public function setUp() : void {  //phpcs:ignore
		parent::setUp();
		Monkey\setUp();
		$this->loadMocks();
		$this->loadTestSources();

		$this->faker    = faker();
		$this->wp_faker = $this->faker->wp();

	}

	/**
	 * Codeception _after() method
	 */
	public function tearDown() : void { //phpcs:ignore
		\Brain\fakerReset();
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

	}

	/**
	 * Load all needed source files for the unit test
	 */
	public function loadTestSources(): void {
		require_once __DIR__ . '/../../../inc/autoload.php';
		require_once __DIR__ . '/../../../inc/wp_plugins/paid-memberships-pro/classes/class.memberorder.php';
	}

	/**
	 * Validates the Template_Data class and it's methods and attributes
	 *
	 * @dataProvider fixture_template_data_create
	 */
	public function test_template_data_create( $methods, $attributes, $instantiated_classes ) {

		$errlog_mock = $this->getMockBuilder( Error_Log::class )
							->onlyMethods( array( 'debug' ) )
							->getMock();

		$errlog_mock->method( 'debug' )
					->willReturn( null );

		try {
			Functions\expect( 'get_option' )
				->with( Mockery::contains( 'blogname' ) )
				->andReturn( 'E20R Import Members from CSV Test Site' );
		} catch ( \Exception $e ) {
			echo 'Error: ' . $e->getMessage(); // phpcs:ignore
		}

		$template_data_class = new Template_Data();
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
			'E20R\\Import_Members\\Error_Log' => 'error_log',
		);

		return array(
			array( $methods, $attributes, $instantiated_classes ),
		);
	}

	/**
	 * Test whether the correct path is returned for the Import file specified
	 * (no user, order or user meta data supplied in constructor)
	 *
	 * @dataProvider fixture_default_site_field_values
	 */
	public function test_default_site_field_values( $user, $site_name, $wp_login_url, $levels_link, $from_email, $display_name, $default_site_field_keys, $expected_results ) {

		if ( ! empty( $user ) ) {
			global $current_user;
			// For testing purposes
			$current_user = $user; // phpcs:ignore
		}

		$errlog_mock = $this->getMockBuilder( Error_Log::class )
							->onlyMethods( array( 'debug' ) )
							->getMock();

		$errlog_mock->method( 'debug' )
					->willReturn( null );

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

		// Instantiate our class
		$imported_data = new Template_Data();
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
	public function fixture_default_site_field_values() : array {

		$user_list               = $this->wp_faker->user( 5 );
		$default_site_field_keys = array( 'sitename', 'siteemail', 'login_link', 'levels_link', 'name' );
		$fixture                 = array();

		// Generate list of fixures (driven by user info)
		foreach ( $user_list as $user ) {
			$fixture[] = array(
				$user,
				'Test Site',
				'http://mytestwebsite.com/wp-login.php',
				'http://mytestwebsite.com/membership/membership_levels/',
				'admin@mytestwebsite.com',
				$user->display_name,
				$default_site_field_keys,
				array(
					'sitename'    => 'Test Site',
					'siteemail'   => 'admin@mytestwebsite.com',
					'login_link'  => 'http://mytestwebsite.com/wp-login.php',
					'levels_link' => 'http://mytestwebsite.com/membership/membership_levels/',
					'name'        => $user->display_name,
				),
			);
		}

		return $fixture;
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
	 * @return false|string
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
	 * @dataProvider fixture_configure_objects
	 */
	public function test_configure_objects( $user, $order, $user_meta ) {

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

		$imported_data = new Template_Data();

		$this->assertEquals( null, $imported_data->get( 'ID', 'user' ) );
		$this->assertEquals( null, $imported_data->get( 'display_name', 'user' ) );
		$this->assertEquals( null, $imported_data->get( 'id', 'order' ) );
		$this->assertEquals( null, $imported_data->get( 'membership_id', 'meta' ) );

		$imported_data->configure_objects( $user, $order, $user_meta );

		$this->assertEquals( $user->id, $imported_data->get( 'ID', 'user' ) );
		$this->assertEquals( $user->display_name, $imported_data->get( 'display_name', 'user' ) );
		$this->assertEquals( $order->id, $imported_data->get( 'id', 'order' ) );
		$this->assertEquals( $user_meta['membership_id'], $imported_data->get( 'membership_id', 'meta' ) );
	}

	/**
	 * Fixture to generate User, MemberOrder and user metadata objects/arrays
	 *
	 * @return array[]
	 */
	public function fixture_configure_objects() {
		$user_list = $this->wp_faker->atLeastFiveUsers();
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
	 * @param \WP_User $user
	 * @return \MemberOrder
	 */
	private function fixture_pmpro_order( $user ) {

		$order = $this->getMockBuilder( \MemberOrder::class )
		              ->onlyMethods( array( 'getLastMemberOrder', 'setGateway', 'getRandomCode' ) )
		              ->getMock();

		$order->method( 'setGateway' )
		      ->willReturn( false );

		$order->method( 'getRandomCode' )
		      ->willReturn(
			      $this->random_strings( 10 )
		      );

		$order->method( 'getLastMemberOrder' )
		      ->willReturn( $order );

		$order->id            = $order->getRandomCode();
		$order->user_id       = $user->ID;
		$order->FirstName     = $user->first_name; // phpcs:ignore
		$order->LastName      = $user->last_name; // phpcs:ignore
		$order->Email         = $user->user_email; // phpcs:ignore
		$order->membership_id = 1;
		$order->billing       = new \stdClass();
		$order->billing->name = $user->display_name;
		$order->setGateway( 'stripe' );

		return $order;
	}
}
