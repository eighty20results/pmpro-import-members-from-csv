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
namespace E20R\Import_Members;

use E20R\Import_Members\Import\Ajax;
use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Import\Page;
use E20R\Import_Members\Modules\BuddyPress\Column_Validation as BuddyPress_Validation;
use E20R\Import_Members\Modules\PMPro\Column_Validation as PMPro_Validation;
use E20R\Import_Members\Modules\PMPro\Import_Member;
use E20R\Import_Members\Modules\Users\Column_Validation as User_Validation;
use E20R\Import_Members\Modules\Users\Import_User;

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
		add_action( 'e20r_before_user_import', array( CSV::get_instance(), 'pre_import' ), 10, 2 );
		add_filter( 'e20r_import_usermeta', array( Import_User::get_instance(), 'import_usermeta' ), 10, 2 );
		add_action(
			'e20r_after_user_import',
			array(
				Import_Member::get_instance(),
				'import_membership_info',
			),
			- 1,
			3
		);
		add_action( 'e20r_after_user_import', array( Data::get_instance(), 'cleanup' ), 9999, 3 );
		
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
		
		if ( true === stripos( $file, 'class.pmpro-import-members.php' ) ) {
			
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