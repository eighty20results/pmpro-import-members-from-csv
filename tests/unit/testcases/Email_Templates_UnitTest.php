<?php

namespace E20R\Tests\Unit;

use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Data;
use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\PMPro\PMPro;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Process\Ajax;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Process\Page;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Variables;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use MemberOrder;
use Brain\Monkey;
use Brain\Monkey\Functions;

use stdClass;
use WP_Mock;
use WP_User;

class Email_Templates_UnitTest extends \Codeception\Test\Unit {
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
	 * Mocked Import() class
	 *
	 * @var null|Import
	 */
	private $mocked_import = null;

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

		$this->mocked_errorlog = $this->makeEmpty(
			Error_Log::class,
			array(
				'debug' => function( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Mocked log: {$msg}" );
				},
			)
		);

		$mocked_variables = $this->makeEmpty(
			Variables::class
		);

		$mocked_pmpro = $this->makeEmpty(
			PMPro::class
		);

		$mocked_data = $this->makeEmpty(
			Data::class
		);

		$mocked_import_user = $this->makeEmpty(
			Import_User::class
		);

		$mocked_import_member = $this->makeEmpty(
			Import_Member::class
		);

		$mocked_csv = $this->makeEmpty(
			CSV::class
		);

		$mocked_email_templates = $this->makeEmpty(
			Email_Templates::class
		);

		$mocked_validate_data = $this->makeEmpty(
			Validate_Data::class
		);

		$mocked_page = $this->makeEmpty(
			Page::class
		);

		$mocked_ajax = $this->makeEmpty(
			Ajax::class
		);

		$this->mocked_import = $this->constructEmpty(
			Import::class,
			array( $mocked_variables, $mocked_pmpro, $mocked_data, $mocked_import_user, $mocked_import_member, $mocked_csv, $mocked_email_templates, $mocked_validate_data, $mocked_page, $mocked_ajax, $this->mocked_errorlog ),
			array(
				'get' => function( $key ) use ( $mocked_variables ) {
					if ( 'variables' === $key ) {
						return $mocked_variables;
					}
					if ( 'error_log' === $key ) {
						return $this->mocked_errorlog;
					}

					return null;
				},
			)
		);

		/**
		 * phpcs:ignore Squiz.PHP.CommentedOutCode.Found









		 */
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
	 * @throws InvalidInstantiation Thrown if the instantiation of Import() is unexpected
	 * @throws InvalidSettingsKey   Thrown if the Import::get() method attempts to access an invalid property
	 *
	 * @test
	 */
	public function it_should_have_loaded_action_hooks() {
		$template = new Email_Templates( $this->mocked_import );
		$template->load_hooks();

		self::assertSame( 11, has_action( 'wp_loaded', array( $template, 'add_email_templates' ) ) );
		self::assertSame( 10, has_action( 'wp_mail_failed', array( $template, 'mail_failure_handler' ) ) );
	}

	/**
	 * Unit test for the Email_Templates::load_hooks() method (check for expected filters)
	 *
	 * @return void
	 * @throws InvalidInstantiation Thrown if the instantiation of Import() failed
	 * @throws InvalidSettingsKey Thrown if the Import::get() method attempts to access an invalid property
	 * @test
	 */
	public function it_should_have_loaded_filter_hooks() {
		$template = new Email_Templates( $this->mocked_import );
		$template->load_hooks();

		self::assertSame( 1, has_filter( 'e20r_import_message_body', array( $template, 'substitute_data' ) ) );
		self::assertSame( 1, has_filter( 'e20r_import_message_subject', array( $template, 'substitute_data' ) ) );
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
		$templates = new Email_Templates( $this->mocked_import );
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
