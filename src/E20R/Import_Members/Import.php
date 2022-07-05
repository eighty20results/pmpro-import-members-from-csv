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
 */
namespace E20R\Import_Members;

use E20R\Exceptions\AutoloaderNotFound;
use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Email\Email_Templates;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\PMPro\PMPro;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Process\Ajax;
use E20R\Import_Members\Process\CSV;
use E20R\Import_Members\Process\Page;
use E20R\Import_Members\Validate\Column_Values\PMPro_Validation;
use E20R\Import_Members\Validate\Column_Values\Users_Validation;
use E20R\Import_Members\Validate\Column_Values\BuddyPress_Validation;
use E20R\Import_Members\Validate\Validate;
use E20R\Licensing\License;

use function esc_url_raw;

if ( ! class_exists( 'E20R\Import_Members\Import' ) ) {
	/**
	 * Class Import
	 */
	class Import {

		const E20R_LICENSE_SKU = 'E20R_IMPORT_MEMBERS';
		/**
		 * Path to this plugin (directory path)
		 *
		 * @var null|string $plugin_path
		 */
		private $plugin_path = null;

		/**
		 * Instance of this class
		 *
		 * @var null|Import $instance
		 */
		private static $instance = null;

		/**
		 * Instance of the CSV class
		 *
		 * @var null|CSV $csv
		 */
		private $csv = null;

		/**
		 * Instance of the Data class
		 *
		 * @var null|Data $data
		 */
		private $data = null;

		/**
		 * Instance of the Variables class
		 *
		 * @var Variables|null $variables
		 */
		private $variables = null;

		/**
		 * Instance of Error_Log class
		 *
		 * @var Error_Log|null $error_log
		 */
		private $error_log = null;

		/**
		 * Instance of the Import_User class
		 *
		 * @var Import_User $import_user
		 */
		private $import_user = null;

		/**
		 * Instance of the Validate class
		 *
		 * @var array|Validate
		 */
		private $validations = array();

		/**
		 * Instance of the PMPro Import logic
		 *
		 * @var PMPro|null $pmpro
		 */
		private $pmpro = null;

		/**
		 * Holds the HTML generator for the import admin page
		 *
		 * @var Page|null
		 */
		private $page = null;

		/**
		 * Instance of the Ajax Handler
		 *
		 * @var Ajax|null $ajax
		 */
		private $ajax = null;

		/**
		 * Class handling integration with the PMPro Email Templates
		 *
		 * @var Email_Templates|null
		 */
		private $email_templates = null;

		/**
		 * Collection of various data validation methods
		 *
		 * @var null|Validate_Data
		 */
		private $validate_data = null;

		/**
		 * Class handling import operations for PMPro Member data
		 *
		 * @var Import_Member|null
		 */
		private $import_member = null;

		/**
		 * Holds the *_Column_Validation classes we'll load and use.
		 *
		 * @var string[]
		 */
		private $column_validation_classes = array();

		/**
		 * Class instances for any custom validator(s)
		 *
		 * @var array
		 */
		private $validators = array();

		/**
		 * Import class constructor with support for unit test mocking
		 *
		 * @param null|Variables       $variables
		 * @param null|PMPro           $pmpro
		 * @param null|Data            $data
		 * @param null|Import_User     $import_user
		 * @param null|Import_Member   $import_member
		 * @param null|Validate_Data   $validate_data
		 * @param null|CSV             $csv
		 * @param null|Email_Templates $email_templates
		 * @param null|Page            $page
		 * @param null|Ajax            $ajax
		 * @param null|Error_Log       $error_log
		 *
		 * @throws InvalidInstantiation Thrown if this class isn't instantiated properly
		 * @throws InvalidSettingsKey Thrown if the specified class property is undefined/not present
		 *
		 * @filter e20r_import_column_validation_classes - Add to/remove Column Validation classes
		 */
		public function __construct(
			$variables = null,
			$pmpro = null,
			$data = null,
			$import_user = null,
			$import_member = null,
			$csv = null,
			$email_templates = null,
			$validate_data = null,
			$page = null,
			$ajax = null,
			$error_log = null
		) {
			if ( empty( $error_log ) ) {
				$error_log = new Error_Log(); // phpcs:ignore
			}
			$this->error_log = $error_log;

			if ( empty( $pmpro ) ) {
				$pmpro = new PMPro();
			}
			$this->pmpro = $pmpro;

			if ( empty( $variables ) ) {
				$variables = new Variables( $this->error_log );
			}
			$this->variables = $variables;

			if ( empty( $import_user ) ) {
				$import_user = new Import_User( $this->variables, $this->error_log );
			}
			$this->import_user = $import_user;

			if ( empty( $csv ) ) {
				$csv = new CSV( $this->variables, $this->error_log );
			}
			$this->csv = $csv;

			if ( empty( $data ) ) {
				$data = new Data( $this->variables, $this->csv, $this->error_log );
			}
			$this->data = $data;

			if ( null === $email_templates ) {
				$email_templates = new Email_Templates( $this->variables, $this->error_log );
			}
			$this->email_templates = $email_templates;

			if ( null === $validate_data ) {
				$validate_data = new Validate_Data( $this->error_log );
			}
			$this->validate_data = $validate_data;

			$this->plugin_path               = \plugin_dir_path( E20R_IMPORT_PLUGIN_FILE );
			$this->column_validation_classes = apply_filters(
				'e20r_import_column_validation_class_names',
				array(
					'PMPro_Validation'      => false,
					'Users_Validation'      => false,
					'BuddyPress_Validation' => false,
				)
			);

			$defaults = array( 'PMPro_Validation', 'Users_Validation', 'BuddyPress_Validation' );

			try {
				$this->load_validation_classes( $defaults );
			} catch ( AutoloaderNotFound $e ) {
				$this->error_log->debug( 'Could not find the auto-loader so did not load custom validation classes' );
			}

			if ( null === $import_member ) {
				$import_member = new Import_Member( $this );
			}
			$this->import_member = $import_member;

			if ( null === $ajax ) {
				$ajax = new Ajax( $this );
			}
			$this->ajax = $ajax;

			if ( null === $page ) {
				$page = new Page( $this );
			}
			$this->page = $page;

		}

		/**
		 * Autoload custom import validation to the E20R\Import_Members\Validate\Custom_Validator namespace
		 *
		 * @param string[] $defaults List of validators defined by this plugin (defaults)
		 *
		 * @return void
		 * @throws AutoloaderNotFound Thrown if the Composer PSR4 auto-loader instance was not found
		 */
		private function load_validation_classes( $defaults = array() ) {
			global $e20r_import_loader;
			$this->validators = array();

			if ( empty( $e20r_import_loader ) ) {
				throw new AutoloaderNotFound();
			}

			// BUG FIX: PHP Warning during PHPStan testing
			if ( ! is_array( $this->column_validation_classes ) ) {
				$this->column_validation_classes = array();
			}

			// Iterate through the list of Column Validation classes and add them to the autoloader as needed
			foreach ( $this->column_validation_classes as $name => $path ) {

				$base_namespace = 'E20R\\Import_Members\\Validate\\Custom';

				// Only default Column Value validators have 'false' as their path
				if ( ! $path && in_array( $name, $defaults, true ) ) {
					$this->error_log->debug( 'Attempting to enable a validator class: ' . $name );
					$class_name = sprintf( '%1$s\\%2$s', 'E20R\\Import_Members\\Validate\\Column_Values', $name );

					$this->validators[ $name ] = new $class_name( $this );

					add_action( 'plugins_loaded', array( $this->validators[ $name ], 'load_actions' ), 99 );
					$this->error_log->debug( "Loaded {$name} column validation class" );
					// Process the next entry
					continue;
				}

				if ( ! empty( $path ) && 1 !== preg_match( '/Custom/', $base_namespace ) ) {
					// Add custom validation class
					$this->error_log->debug( "Adding custom validator class {$name} in {$path} to the {$base_namespace} namespace" );
					$class_name = sprintf( '%1$s\\%2$s', $base_namespace, $name );
					$e20r_import_loader->setPsr4( $class_name, $path );

					// Then instantiate the class
					$new_class                               = new $class_name( $this->error_log );
					$this->validators[ strtolower( $name ) ] = $new_class;

					// And add its load_actions() method to the plugins_loaded
					add_action( 'plugins_loaded', array( $this->validators[ strtolower( $name ) ], 'load_actions' ), 99 );
				} else {
					$this->error_log->debug( 'Error in custom list from the "e20r_import_column_validation_class_names" filter!' );
				}
			}
		}

		/**
		 * Return parameter values for the class
		 *
		 * @param string $param The class parameter we're trying to get the value of
		 *
		 * @return mixed
		 * @throws InvalidSettingsKey Raised if the parameter doesn't exist in this class
		 */
		public function get( $param = 'plugin_path' ) {
			if ( ! property_exists( $this, $param ) ) {
				throw new InvalidSettingsKey( esc_attr__( 'Error: The requested parameter does not exist!', 'pmpro-import-members-from-csv' ) );
			}

			return $this->{$param};
		}

		/**
		 * Plugin deactivation hook
		 */
		public static function deactivation() {
			delete_option( 'e20r_import_has_donated' );
			delete_option( 'e20r_link_for_sponsor' );
		}

		/**
		 * Is the Paid Memberships Pro plugin active on the current site?
		 *
		 * @param null|string[] $active_plugins - Used by unit tests
		 *
		 * @return bool
		 *
		 * @test \\E20R\Test\ImportMembers_UnitTest::test_is_pmpro_active
		 */
		public static function is_pmpro_active( $active_plugins = null ) {

			if ( empty( $active_plugins ) ) {
				$active_plugins = get_site_option( 'active_plugins' );
			}

			return in_array(
				'paid-memberships-pro/paid-memberships-pro.php',
				$active_plugins,
				true
			);
		}

		/**
		 * Initialization
		 *
		 * @since 2.0
		 **/
		public function load_hooks() {

			if ( false === apply_filters( 'e20r_utilities_module_installed', false ) ) {
				add_action( 'init', '\E20R\Import\Loader::is_utilities_module_active', 10, 0 );
			}
			add_action( 'plugins_loaded', array( $this->pmpro, 'load_hooks' ), 11, 0 );
			add_action( 'plugins_loaded', array( $this->import_user, 'load_actions' ), 11, 0 );
			add_action( 'plugins_loaded', array( $this->import_member, 'load_actions' ), 11, 0 );
			add_action( 'plugins_loaded', array( $this->email_templates, 'load_hooks' ), 99, 0 );
			add_action( 'plugins_loaded', array( $this->ajax, 'load_hooks' ), 99, 0 );
			add_action( 'plugins_loaded', array( $this->page, 'load_hooks' ), 99, 0 );

			// Add validation logic for all Modules
			add_action( 'plugins_loaded', array( $this->validators['Users_Validation'], 'load_actions' ), 30, 0 );
			add_action( 'plugins_loaded', array( $this->validators['PMPro_Validation'], 'load_actions' ), 31, 0 );
			add_action( 'plugins_loaded', array( $this->validators['BuddyPress_Validation'], 'load_actions' ), 32, 0 );

			add_action( 'init', array( $this, 'load_i18n' ), 5, 0 );
			add_action( 'init', array( $this->data, 'process_csv' ), 10, 0 );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 0 );

			// PMPro specific capabilities
			// We do this in the CSV() class as it's a clean-up operation
			// add_action( 'e20r_before_user_import', array( $this->csv, 'pre_import' ), 10, 2 ); // phpcs:ignore
			// add_filter( 'e20r_import_usermeta', array( $this->import_user, 'import_usermeta' ), 10, 3 );
			// add_action( 'e20r_after_user_import', array( $this->import_member, 'import_membership_info' ), -1, 3 );
			// add_action( 'e20r_after_user_import', array( $this->data, 'cleanup' ), 9999, 3 );

			// Set URIs in plugin listing to plugin support
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

			// Clear action handler(s) from the Import Users from CSV Integration Add-on for PMPro
			add_action( 'wp_loaded', array( $this, 'remove_iucsv_support' ), 10, 0 );

			// Remove Import action for Sponsored Members add-on (handled directly by this plugin)
			remove_action( 'is_iu_post_user_import', 'pmprosm_is_iu_post_user_import', 20 );

			if ( ! class_exists( 'E20R\Licensing\License' ) ) {
				return;
			}

			$check = new \ReflectionMethod( 'E20R\Licensing\License', '__construct' );

			// In case the ReflectionMethod doesn't return anything if the class doesn't exist
			if ( empty( $check ) ) {
				$this->error_log->debug( 'E20R Licensing module is missing. Not activating...' );
				return;
			}

			$is_licensed = false;

			if ( false === $check->isPrivate() ) {
				$licensing   = new License( self::E20R_LICENSE_SKU );
				$is_licensed = $licensing->is_licensed( self::E20R_LICENSE_SKU, false );
			} elseif ( true === $check->isPrivate() ) {
				// @phpstan-ignore-next-line
				$is_licensed = Licensing::is_licensed( self::E20R_LICENSE_SKU, false );
			}

			if ( true === $is_licensed ) {
				do_action( 'e20r_import_load_licensed_modules' );
			}
		}

		/**
		 * Save delayed sponsor link when users are imported...
		 */
		public function __destruct() {
			$sponsor_link = $this->variables->get( 'delayed_sponsor_link' );
			if ( ! empty( $sponsor_link ) ) {
				update_option( 'e20r_link_for_sponsor', $sponsor_link, 'no' );
			}
		}

		/**
		 * Load translation (glotPress friendly)
		 */
		public function load_i18n() {
			// Use filtering, etc, to select appropriate locale for this installation
			$locale  = apply_filters( 'plugin_locale', get_user_locale(), 'pmpro-import-members-from-csv' );
			$mo_file = sprintf( 'pmpro-import-members-from-csv-%1$s.mo', $locale );

			//paths to local (plugin) and global (WP) language files
			$mo_file_local  = sprintf( '%1$s/languages/%2$s', dirname( E20R_IMPORT_PLUGIN_FILE ), $mo_file );
			$mo_file_global = sprintf( '%1$s/pmpro-import-members-from-csv/%2$s', WP_LANG_DIR, $mo_file );

			//load global first
			if ( file_exists( $mo_file_global ) ) {
				load_textdomain( 'pmpro-import-members-from-csv', $mo_file_global );
			}

			//load local second
			load_textdomain( 'paid-memberships-pro', $mo_file_local );

			load_plugin_textdomain(
				'pmpro-import-members-from-csv',
				false,
				sprintf( '%1$s/languages', dirname( E20R_IMPORT_PLUGIN_FILE ) )
			);
		}

		/**
		 * Load JavaScript and styles for wp-admin page
		 *
		 * @since 1.0
		 **/
		public function admin_enqueue_scripts() {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_REQUEST['page'] ) || 'pmpro-import-members-from-csv' !== $_REQUEST['page'] ) {
				return;
			}

			$this->variables->load_settings();

			/**
			 * Calculate the max timeout for the AJAX calls. Gets padded with a 20% bonus
			 */
			$max_run_time = (
				$this->variables->calculate_per_record_time() *
				apply_filters( 'e20r_import_records_per_scan', $this->variables->get( 'per_partial' ) )
			);

			$timeout_value = ceil( $max_run_time * 1.2 );
			$errors        = $this->variables->get( 'display_errors' );

			$this->error_log->debug( "Setting JavaScript timeout for Import operations to {$timeout_value} seconds" );

			wp_enqueue_style(
				'pmpro-import-members-from-csv',
				plugins_url( 'css/pmpro-import-members-from-csv.css', E20R_IMPORT_PLUGIN_FILE ),
				array(),
				E20R_IMPORT_VERSION
			);

			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_register_script(
				'pmpro-import-members-from-csv',
				plugins_url( 'javascript/pmpro-import-members-from-csv.js', E20R_IMPORT_PLUGIN_FILE ),
				array( 'jquery' ),
				E20R_IMPORT_VERSION
			);

			wp_localize_script(
				'pmpro-import-members-from-csv',
				'e20r_im_settings',
				apply_filters(
					'pmp_im_import_js_settings',
					array(
						'timeout'                     => $timeout_value,
						'background_import'           => intval( $this->variables->get( 'background_import' ) ),
						'filename'                    => $this->variables->get( 'filename' ),
						'update_users'                => intval( $this->variables->get( 'update_users' ) ),
						'update_id'                   => intval( $this->variables->get( 'update_id' ) ),
						'deactivate_old_memberships'  => intval( $this->variables->get( 'deactivate_old_memberships' ) ),
						'new_user_notification'       => intval( $this->variables->get( 'new_user_notification' ) ),
						'create_order'                => intval( $this->variables->get( 'create_order' ) ),
						'admin_new_user_notification' => intval( $this->variables->get( 'admin_new_user_notification' ) ),
						'send_welcome_email'          => intval( $this->variables->get( 'send_welcome_email' ) ),
						'suppress_pwdmsg'             => intval( $this->variables->get( 'suppress_pwdmsg' ) ),
						'password_hashing_disabled'   => intval( $this->variables->get( 'password_hashing_disabled' ) ),
						'password_nag'                => intval( $this->variables->get( 'password_nag' ) ),
						'per_partial'                 => intval( $this->variables->get( 'per_partial' ) ),
						'site_id'                     => intval( $this->variables->get( 'site_id' ) ),
						'admin_page'                  => add_query_arg(
							'page',
							'pmpro-import-members-from-csv',
							admin_url( 'admin.php' )
						),
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						'import'                      => isset( $_REQUEST['import'] ) ? sanitize_text_field( $_REQUEST['import'] ) : null,
						'lang'                        => array(
							'whitespace_in_filename' => esc_attr__(
								'Error: Your file name contains one or more whitespace characters. Please rename the file and remove any whitespace characters from the file name.',
								'pmpro-import-members-from-csv'
							),
							'pausing'                => esc_attr__(
								'Pausing. You may see one more update here as we clean up.',
								'pmpro-import-members-from-csv'
							),
							'resuming'               => esc_attr__( 'Resuming...', 'pmpro-import-members-from-csv' ),
							'loaded'                 => esc_attr__( 'JavaScript Loaded.', 'pmpro-import-members-from-csv' ),
							'done'                   => esc_attr__( 'Done!', 'pmpro-import-members-from-csv' ),
							'alert_msg'              => esc_attr__( 'Error with Import. Close to reload the admin page.', 'pmpro-import-members-from-csv' ),
							'error'                  => esc_attr__( 'Error with Import. Close to refresh the admin page.', 'pmpro-import-members-from-csv' ),
							'excel_info'             => sprintf(
							// translators: %1$s link html %2$s terminating link html
								esc_attr__(
									'If you use Microsoft Excel(tm) to view/edit your .CSV files, may we suggest you %1$stry using Google Sheets instead%2$s? Using Google Sheets may reduce/eliminate issues with date formats!',
									'pmpro-import-members-from-csv'
								),
								sprintf(
								// translators: %s URL to google docs, %s description
									'<a href="%1$s" target="_blank" title="%2$s">',
									'https://docs.google.com/spreadsheets',
									esc_attr__( 'To Google Sheets', 'pmpro-import-members-from-csv' )
								),
								'</a>'
							),
						),
						'display_errors'              => ( ! empty( $errors ) ? $errors : null ),
					)
				)
			);

			wp_enqueue_script( 'pmpro-import-members-from-csv' );
		}

		/**
		 * Remove handlers for the "Import Users from CSV Integration" add-on
		 */
		public function remove_iucsv_support() {

			if ( has_action( 'is_iu_pre_user_import', 'pmproiufcsv_is_iu_pre_user_import' ) ) {

				remove_action( 'is_iu_pre_user_import', 'pmproiufcsv_is_iu_pre_user_import', 10 );
				remove_action( 'is_iu_post_user_import', 'pmproiufcsv_is_iu_post_user_import' );
				remove_filter( 'is_iu_import_usermeta', 'pmproiufcsv_is_iu_import_usermeta', 10 );
			}
		}

		/**
		 * Add links to support & docs for the plugin
		 *
		 * @param array $links - Links for the Plugins page
		 * @param string $file
		 *
		 * @return array
		 */
		public function plugin_row_meta( $links, $file ) {

			if ( false !== stripos( $file, 'class.pmpro-import-members.php' ) ) {
				// Add (new) 'Import Users from CSV' links to plugin listing
				$new_links = array(
					'donate'        => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( 'https://www.paypal.me/eighty20results' ),
						esc_attr__(
							'Donate to support updates, maintenance and tech support for this plugin',
							'pmpro-import-members-from-csv'
						),
						esc_attr__( 'Donate', 'pmpro-import-members-from-csv' )
					),
					'documentation' => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/' ),
						esc_attr__( 'View the documentation', 'pmpro-import-members-from-csv' ),
						esc_attr__( 'Docs', 'pmpro-import-members-from-csv' )
					),
					'filters'       => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( plugin_dir_url( E20R_IMPORT_PLUGIN_FILE ) . 'docs/FILTERS.md' ),
						esc_attr__( 'View the Filter documentation', 'pmpro-import-members-from-csv' ),
						esc_attr__( 'Filters', 'pmpro-import-members-from-csv' )
					),
					'actions'       => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( plugin_dir_url( E20R_IMPORT_PLUGIN_FILE ) . 'docs/ACTIONS.md' ),
						esc_attr__( 'View the Actions documentation', 'pmpro-import-members-from-csv' ),
						esc_attr__( 'Actions', 'pmpro-import-members-from-csv' )
					),
					'help'          => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv' ),
						esc_attr__( 'Visit the support forum', 'pmpro-import-members-from-csv' ),
						esc_attr__( 'Support', 'pmpro-import-members-from-csv' )
					),
					'issues'        => sprintf(
						'<a href="%1$s" title="%2$s" target="_blank">%3$s</a>',
						esc_url_raw( 'https://github.com/eighty20results/pmpro-import-members-from-csv/issues' ),
						esc_attr__( 'Report bugs for this plugin', 'pmpro-import-members-from-csv' ),
						esc_attr__( 'Report Bugs', 'pmpro-import-members-from-csv' )
					),
				);

				$links = array_merge( $links, $new_links );
			}

			return $links;
		}
	}
}
