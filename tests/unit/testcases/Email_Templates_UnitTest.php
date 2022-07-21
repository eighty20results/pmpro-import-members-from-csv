<?php
/**
 * Copyright (c) 2018 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @package E20R\Tests\Unit\Email_Templates_UnitTest
 */

namespace E20R\Tests\Unit;

use Codeception\Test\Unit;

use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Variables;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Mock;

use stdClass;
use MemberOrder;
use WP_User;

class Email_Templates_UnitTest extends Unit {
	use MockeryPHPUnitIntegration;

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
		$this->load_stubs();
		$this->load_mocks();
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

		$this->mocked_errorlog  = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Mocked log: {$msg}" );
				},
			)
		);
		$this->mocked_variables = $this->makeEmpty( Variables::class );
	}

	/**
	 * Load all class mocks we need (with namespaces)
	 *
	 * @return void
	 */
	public function load_stubs() : void {

		Functions\when( 'wp_upload_dir' )
			->justReturn(
				array(
					'baseurl' => 'https://localhost:7537/wp-content/uploads/',
					'basedir' => '/var/www/html/wp-content/uploads',
				)
			);

		Functions\when( 'plugin_dir_path' )
			->justReturn( '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv' );
	}

	/**
	 * Unit test for the Email_Templates::load_hooks() method (Check for expected actions)
	 *
	 * @return void
	 * @test
	 * @covers \E20R\Import_Members\Email\Email_Templates::load_hooks
	 */
	public function it_should_have_loaded_action_hooks() {
		try {
			Monkey\Actions\expectAdded( 'wp_loaded' )
				->with( Mockery::contains( array( Email_Templates::class, 'add_email_templates' ) ) )
				->once();
			Monkey\Actions\expectAdded( 'wp_mail_failed' )
				->with( Mockery::contains( array( Email_Templates::class, 'mail_failure_handler' ) ) )
				->once();
		} catch ( Monkey\Expectation\Exception\ExpectationArgsRequired $e ) {
			$this->fail( $e->getMessage() );
		}

		$template = new Email_Templates( $this->mocked_variables, $this->mocked_errorlog );
		$template->load_hooks();
	}

	/**
	 * Unit test for the Email_Templates::load_hooks() method (Check for expected actions)
	 *
	 * @return void
	 * @test
	 * @covers \E20R\Import_Members\Email\Email_Templates::load_hooks
	 */
	public function it_should_have_loaded_filter_hooks() {
		try {
			Monkey\Filters\expectAdded( 'e20r_import_message_body' )
				->with( Mockery::contains( array( Email_Templates::class, 'substitute_data' ) ) )
				->once();
			Monkey\Filters\expectAdded( 'e20r_import_message_subject' )
				->with( Mockery::contains( array( Email_Templates::class, 'substitute_data' ) ) )
				->once();
		} catch ( Monkey\Expectation\Exception\ExpectationArgsRequired $e ) {
			$this->fail( $e->getMessage() );
		}

		$template = new Email_Templates( $this->mocked_variables, $this->mocked_errorlog );
		$template->load_hooks();
	}
	/**
	 * It should test string substitution for supplied variable(s)
	 *
	 * @param string $text
	 * @param WP_User $user_object
	 * @param array $fields_to_substitute
	 * @param string $expected_text
	 *
	 * @return void
	 *
	 * @dataProvider fixture_string_substitutions
	 * @test
	 */
	public function it_should_substitute_data_in_string( $text, $user_object, $fields_to_substitute, $expected_text ) {
		$templates = new Email_Templates( $this->mocked_variables, $this->mocked_errorlog );
		$result    = $templates->substitute_data( $text, $user_object, $fields_to_substitute );
		self::assertIsString( $result );
		self::assertSame( $expected_text, $result );
	}

	/**
	 * Substitution test fixture
	 *
	 * @return array[]
	 *
	 * @throws \Exception
	 */
	public function fixture_string_substitutions() {
		$wp_user = $this->create_mocked_wp_users( 1 );
		return array(
			array( 'This is a string with no substitutions', $wp_user, array( 'membership_id' => 1 ), 'This is a string with no substitutions' ),
			array( 'Welcome to the !!sitename!! website', $wp_user, array( 'sitename' => 'Testing' ), 'Welcome to the Testing website' ),
			array( 'Welcome to the !!SITENAME!! website', $wp_user, array( 'SITENAME' => 'Testing' ), 'Welcome to the Testing website' ),
			array( 'Hi !!display_name!!! Welcome to the !!sitename!! website', $wp_user, array( 'sitename' => 'Testing' ), 'Hi Test User # 1000! Welcome to the Testing website' ),
			array( '', $wp_user, array( 'sitename' => 'Testing' ), '' ),
			array( null, $wp_user, array( 'sitename' => 'Testing' ), '' ),
			array( false, $wp_user, array( 'sitename' => 'Testing' ), '' ),
			array( 'Hi !!display_Name!!! Welcome to the !!sitename!! website', $wp_user, array( 'sitename' => 'Testing' ), 'Hi !!display_Name!!! Welcome to the Testing website' ),
			array( 'Hi !!user_login!!! Welcome to the !!sitename!! website', $wp_user, array( 'sitename' => 'Testing' ), 'Hi ! Welcome to the Testing website' ),
		);
	}

	/**
	 * Create mocked WP_User objects
	 *
	 * @param int $count The number of mocked WP_User objects to create and return
	 *
	 * @return array
	 * @throws \Exception Thrown if we're unable to construct the mock WP_User object
	 */
	private function create_mocked_wp_users( $count = 1 ) {
		$user_list = array();
		$base_id   = 1000;

		for ( $i = 0; $i < $count; $i++ ) {
			$id = ( $base_id + $i );

			if ( class_exists( '\WP_User' ) ) {
				$user = $this->construct(
					WP_User::class,
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
}
