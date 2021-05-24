<?php
/**
 * Plugin Name: Import Paid Memberships Pro Members from CSV
 * Plugin URI: http://wordpress.org/plugins/pmpro-import-members-from-csv/
 * Description: Import Users and their metadata from a csv file.
 * Version: 3.0
 * Requires PHP: 7.0
 * Author: <a href="https://eighty20results.com/thomas-sjolshagen/">Thomas Sjolshagen <thomas@eighty20results.com></a>
 * License: GPL2
 * Text Domain: pmpro-import-members-from-csv
 * Domain Path: languages/
 */

/**
 * Copyright 2017-2021 - Thomas Sjolshagen (https://eighty20results.com/thomas-sjolshagen)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @credit http://wordpress.org/plugins/import-users-from-csv/ - Ulich Sossou -  https://github.com/sorich87
 * @credit https://github.com/strangerstudios/pmpro-import-users-from-csv - Jason Coleman - https://github.com/ideadude
 */

namespace E20R\Import_Members;

use E20R\Import_Members\Import\Ajax;
use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Import\Page;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\Users\Column_Validation as User_Validation;
use E20R\Import_Members\Modules\PMPro\Column_Validation as PMPro_Validation;
use E20R\Import_Members\Modules\BuddyPress\Column_Validation as BuddyPress_Validation;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function plugin_dir_path;
use function add_filter;
use function remove_filter;
use function add_action;
use function remove_action;
use function error_log;

if ( ! defined( 'E20R_IM_CSV_DELIMITER' ) ) {
	define( 'E20R_IM_CSV_DELIMITER', ',' );
}
if ( ! defined( 'E20R_IM_CSV_ESCAPE' ) ) {
	define( 'E20R_IM_CSV_ESCAPE', '\\' );
}
if ( ! defined( 'E20R_IM_CSV_ENCLOSURE' ) ) {
	define( 'E20R_IM_CSV_ENCLOSURE', '"' );
}

if ( ! defined( 'E20R_IMPORT_VERSION' ) ) {
	define( 'E20R_IMPORT_VERSION', '3.0' );
}

class Import_Members {

	const PLUGIN_SLUG = 'pmpro-import-members-from-csv';
	/**
	 * Path to this plugin (directory path)
	 *
	 * @var null|string $plugin_path
	 */
	public static $plugin_path = null;

	/**
	 * Instance of this class
	 *
	 * @var null|Import_Members $instance
	 */
	private static $instance = null;

	/**
	 * Import_Members constructor.
	 */
	private function __construct() {
		self::$plugin_path = plugin_dir_path( __FILE__ );
	}

	/**
	 * Plugin deactivation hook
	 */
	public static function deactivation() {

		delete_option( 'e20r_import_has_donated' );
		delete_option( 'e20r_link_for_sponsor' );
	}

	/**
	 * Class auto-loader
	 *
	 * @param string $class_name Name of the class to auto-load
	 *
	 * @since  1.0
	 * @access public static
	 *
	 * @since  v2.2 - BUG FIX: Include blocks/ directory to auto_loader() method
	 */
	public static function auto_loader( $class_name ) {

		if ( false === stripos( $class_name, 'e20r' ) ) {
			return;
		}

		$parts      = explode( '\\', $class_name );
		$c_name     = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
		$base_paths = array();

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'src/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'src/';
		}

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'classes/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'classes/';
		}

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'class/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'class/';
		}

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'blocks/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'blocks/';
		}
		$filename = "class-{$c_name}.php";

		foreach ( $base_paths as $base_path ) {

			$iterator = new RecursiveDirectoryIterator( $base_path, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveIteratorIterator::SELF_FIRST | RecursiveIteratorIterator::CATCH_GET_CHILD | RecursiveDirectoryIterator::FOLLOW_SYMLINKS );

			$filter = new RecursiveCallbackFilterIterator(
				$iterator,
				function ( $current, $key, $iterator ) use ( $filename ) {

					// Skip hidden files and directories.
					if ( $current->getFilename()[0] == '.' || $current->getFilename() == '..' ) {
						return false;
					}

					if ( $current->isDir() ) {
						// Only recurse into intended subdirectories.
						return $current->getFilename() === $filename;
					} else {
						// Only consume files of interest.
						return str_starts_with( $current->getFilename(), $filename );
					}
				}
			);

			foreach ( new RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {

				$class_path = $f_file->getPath() . '/' . $f_file->getFilename();

				if ( $f_file->isFile() && false !== str_contains( $class_path, $filename ) ) {

					require_once $class_path;
				}
			}
		}
	}

	/**
	 * Is the Paid Memberships Pro plugin active on the current site?
	 *
	 * @param null|string[] $active_plugins - Used by unit tests
	 *
	 * @return bool
	 * @test \\E20R\Test\ImportMembersTest::test_is_pmpro_active
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

		add_action( 'plugins_loaded', array( Email_Templates::get_instance(), 'load_hooks' ), 99 );
		add_action( 'plugins_loaded', array( Ajax::get_instance(), 'load_hooks' ), 99 );
		add_action( 'plugins_loaded', array( Page::get_instance(), 'load_hooks' ), 99 );

		// Add validation logic for all modules
		add_action( 'plugins_loaded', array( User_Validation::get_instance(), 'load_actions' ), 30 );
		add_action( 'plugins_loaded', array( PMPro_Validation::get_instance(), 'load_actions' ), 31 );
		add_action( 'plugins_loaded', array( BuddyPress_Validation::get_instance(), 'load_actions' ), 32 );

		add_action( 'init', array( $this, 'load_i18n' ), 5 );
		add_action( 'init', array( Data::get_instance(), 'process_csv' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// PMPro specific import functionality
		add_action( 'e20r-pre-member-import', array( CSV::get_instance(), 'pre_import' ), 10, 2 );
		add_filter( 'e20r-import-usermeta', array( Import_User::get_instance(), 'import_usermeta' ), 10, 2 );
		add_action(
			'e20r_after_user_import',
			array(
				Import_Member::get_instance(),
				'import_membership_info',
			),
			- 1,
			2
		);
		add_action( 'e20r_after_user_import', array( Data::get_instance(), 'cleanup' ), 9999, 2 );

		// Set URIs in plugin listing to plugin support
		add_filter( 'plugin_row_meta', array( self::get_instance(), 'plugin_row_meta' ), 10, 2 );

		// Clear action handler(s) from the Import Users from CSV Integration Add-on for PMPro
		add_action( 'wp_loaded', array( $this, 'remove_iucsv_support' ), 10 );

		// Remove Import action for Sponsored Members add-on (handled directly by this plugin)
		remove_action( 'is_iu_post_user_import', 'pmprosm_is_iu_post_user_import', 20 );

		do_action( 'e20r-import-load-licensed-modules' );
	}

	/**
	 * Return or instantiate class for use
	 *
	 * @return Import_Members
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Save delayed sponsor link when users are imported...
	 */
	public function __destruct() {

		$variables    = Variables::get_instance();
		$sponsor_link = $variables->get( '_delayed_sponsor_link' );
		if ( ! empty( $sponsor_link ) ) {
			update_option( 'e20r_link_for_sponsor', $sponsor_link, 'no' );
		}
	}

	/**
	 * Load translation (glotPress friendly)
	 */
	public function load_i18n() {

		load_plugin_textdomain(
			self::PLUGIN_SLUG,
			false,
			basename( dirname( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add admin JS
	 *
	 * @param string $hook
	 *
	 * @since 1.0
	 **/
	public function admin_enqueue_scripts( $hook ) {

		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] != self::PLUGIN_SLUG ) {
			return;
		}

		$settings  = Variables::get_instance();
		$error_log = \E20R\Import_Members\Error_Log::get_instance();

		$settings->load_settings();

		/**
		 * Calculate the max timeout for the AJAX calls. Gets padded with a 20% bonus
		 */
		$max_run_time = (
			apply_filters( 'pmp_im_import_time_per_record', 3 ) *
			apply_filters( 'pmp_im_import_records_per_scan', $settings->get( 'per_partial' ) )
		);

		$timeout_value = ceil( $max_run_time * 1.2 );
		$errors        = $settings->get( 'display_errors' );

		$error_log->debug( "Setting JavaScript timeout for import operations to {$timeout_value} seconds" );

		wp_enqueue_style( self::PLUGIN_SLUG, plugins_url( 'css/pmpro-import-members-from-csv.css', __FILE__ ), null, E20R_IMPORT_VERSION );
		wp_register_script( self::PLUGIN_SLUG, plugins_url( 'javascript/pmpro-import-members-from-csv.js', __FILE__ ), array( 'jquery' ), E20R_IMPORT_VERSION, true );

		wp_localize_script(
			self::PLUGIN_SLUG,
			'e20r_im_settings',
			apply_filters(
				'pmp_im_import_js_settings',
				array(
					'timeout'                     => $timeout_value,
					'background_import'           => intval( $settings->get( 'background_import' ) ),
					'filename'                    => $settings->get( 'filename' ),
					'update_users'                => intval( $settings->get( 'update_users' ) ),
					'deactivate_old_memberships'  => intval( $settings->get( 'deactivate_old_memberships' ) ),
					'new_user_notification'       => intval( $settings->get( 'new_user_notification' ) ),
					'create_order'                => intval( $settings->get( 'create_order' ) ),
					'admin_new_user_notification' => intval( $settings->get( 'admin_new_user_notification' ) ),
					'send_welcome_email'          => intval( $settings->get( 'send_welcome_email' ) ),
					'suppress_pwdmsg'             => intval( $settings->get( 'suppress_pwdmsg' ) ),
					'password_hashing_disabled'   => intval( $settings->get( 'password_hashing_disabled' ) ),
					'password_nag'                => intval( $settings->get( 'password_nag' ) ),
					'per_partial'                 => intval( $settings->get( 'per_partial' ) ),
					'site_id'                     => intval( $settings->get( 'site_id' ) ),
					'admin_page'                  => add_query_arg(
						'page',
						self::PLUGIN_SLUG,
						admin_url( 'admin.php' )
					),
					'import'                      => isset( $_REQUEST['import'] ) ? sanitize_text_field( $_REQUEST['import'] ) : null,
					'lang'                        => array(
						'whitespace_in_filename' => __(
							'Error: Your file name contains one or more whitespace characters. Please rename the file and remove any whitespace characters from the file name.',
							self::PLUGIN_SLUG
						),
						'pausing'                => __(
							'Pausing. You may see one more update here as we clean up.',
							self::PLUGIN_SLUG
						),
						'resuming'               => __( 'Resuming...', self::PLUGIN_SLUG ),
						'loaded'                 => __( 'JavaScript Loaded.', self::PLUGIN_SLUG ),
						'done'                   => __( 'Done!', self::PLUGIN_SLUG ),
						'alert_msg'              => __( 'Error with import. Close to reload the admin page.', self::PLUGIN_SLUG ),
						'error'                  => __( 'Error with import. Close to refresh the admin page.', self::PLUGIN_SLUG ),
						'excel_info'             => sprintf( __( 'If you use Microsoft Excel(tm) to view/edit your .CSV files, may we suggest you %1$stry using Google Sheets instead%2$s? Using Google Sheets may reduce/eliminate issues with date formats!', self::PLUGIN_SLUG ), sprintf( '<a href="%s" target="_blank" title="%s">', 'http://docs.google.com/spreadsheets', __( 'To Google Sheets', self::PLUGIN_SLUG ) ), '</a>' ),
					),
					'display_errors'              => ( ! empty( $errors ) ? $errors : null ),
				)
			)
		);

		wp_enqueue_script( self::PLUGIN_SLUG );
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
	 * @param array  $links - Links for the Plugins page
	 * @param string $file
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {

		if ( true === str_contains( $file, 'class.pmpro-import-members.php' ) ) {

			// Add (new) 'Import Users from CSV' links to plugin listing
			$new_links = array(
				'donate'        => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url_raw( 'https://www.paypal.me/eighty20results' ),
					__(
						'Donate to support updates, maintenance and tech support for this plugin',
						self::PLUGIN_SLUG
					),
					__( 'Donate', self::PLUGIN_SLUG )
				),
				'documentation' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/' ),
					__( 'View the documentation', self::PLUGIN_SLUG ),
					__( 'Docs', self::PLUGIN_SLUG )
				),
				'help'          => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv' ),
					__( 'Visit the support forum', self::PLUGIN_SLUG ),
					__( 'Support', self::PLUGIN_SLUG )
				),
			);

			$links = array_merge( $links, $new_links );
		}

		return $links;
	}
}

# Load the composer autoloader for the 10quality utilities
if ( file_exists( __DIR__ . '/inc/autoload.php' ) ) {
	require_once __DIR__ . '/inc/autoload.php';
}

try {
	spl_autoload_register( '\E20R\Import_Members\Import_Members::auto_loader' );
} catch ( \Exception $exception ) {
	error_log(
		__(
			'Unable to load PHP autoloader for the PMPro Import Members from CSV plugin!',
			Import_Members::PLUGIN_SLUG
		),
		E_USER_ERROR
	);

	return;
}

\register_deactivation_hook( __FILE__, 'E20R\Import_Members::deactivation' );

// Load the plugin.
add_action( 'plugins_loaded', array( Import_Members::get_instance(), 'load_hooks' ), 10 );