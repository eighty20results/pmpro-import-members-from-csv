<?php
/**
 * Copyright (c) 2018-2019. - Eighty / 20 Results by Wicked Strong Chicks.
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


use E20R\Import_Members\Import\CSV;
use PayPal\EBLBaseComponents\ExternalRememberMeOptInDetailsType;

class Variables {
	
	/**
	 * Instance of the Variables class (or null if not instantiated yet)
	 *
	 * @var null|Variables
	 */
	private static $instance = null;
	
	/**
	 * Path to error log file
	 *
	 * @var string $logfile_path
	 */
	private $logfile_path = '';
	
	/**
	 * URI for error log
	 *
	 * @var string $logfile_url
	 */
	private $logfile_url = '';
	
	/**
	 * List of Import fields (all supported modules)
	 *
	 * @var array|null $fields
	 */
	private $fields = null;
	
	/**
	 * Name/path of CSV import file
	 *
	 * @var null|string $filename
	 */
	private $filename = null;
	
	/**
	 * Update existing user data?
	 *
	 * @var bool $update_users
	 */
	private $update_users = false;
	
	/**
	 * Set the password nag message when user logs in for the first time?
	 *
	 * @var bool $password_nag
	 */
	private $password_nag = false;
	
	/**
	 * @var bool $password_hashing_disabled - Password is supplied in import file as an encrypted string
	 */
	private $password_hashing_disabled = false;
	
	/**
	 * Should we deactivate old membership levels for the user that
	 * match the record being imported?
	 *
	 * @var bool $deactivate_old_memberships
	 */
	private $deactivate_old_memberships = false;
	
	/**
	 * Do we send the imported user the "new WordPress Account" notice?
	 *
	 * @var bool $new_user_notification
	 */
	private $new_user_notification = false;
	
	/**
	 * Do we send a welcome message to the member if they're imported as an active member to the site
	 *
	 * @var bool $new_member_notification
	 */
	private $new_member_notification = false;
	
	/**
	 * Do we use the imported_member.html template and welcome the imported member?
	 *
	 * @var bool
	 */
	private $send_welcome_email = false;
	
	/**
	 * Do we suppress the "your password was changed" message to the (new/updated) user?
	 *
	 * @var bool
	 */
	private $suppress_pwdmsg = false;
	
	/**
	 * Do we include the admin in the New Member notification email on import?
	 *
	 * @var bool
	 */
	private $admin_new_user_notification = false;
	
	/**
	 * The ID of the multisite to import the user data to/for
	 *
	 * @var null|int $site_id
	 */
	private $site_id = null;
	
	/**
	 * Import the CSV file as a "background" process (i.e. with a JavaScript loop)
	 *
	 * @var bool $background_import
	 */
	private $background_import = false;
	
	/**
	 * Add a PMPro Order record for the imported user (assumes there's either/both initial_payment and billing_amount +
	 * cycle info)
	 *
	 * @var bool $create_order
	 */
	private $create_order = false;
	
	/**
	 * Number of records to import per transaction
	 *
	 * @var int $per_partial
	 */
	private $per_partial = 30;
	
	/**
	 * Do we import in chunks (# of records per chunk equals the $per_partial setting)
	 *
	 * @var bool $partial
	 */
	private $partial = false;
	
	/**
	 * @var null|\SplFileObject
	 */
	private $file_object = null;
	
	/**
	 * @var null|string
	 */
	private $welcome_mail_warning = null;
	
	/**
	 * Errors to include on the front-end of the import page
	 *
	 * @var array
	 */
	private $display_errors = array();
	
	/**
	 * List of user IDs that should be attempted linked to their sponsor(s) when we shut down...
	 *
	 * @var array
	 */
	private $_delayed_sponsor_link = array();
	
	/**
	 * The list of WP_User fields (to let us differentiate between WP_User and user meta fields)
	 *
	 * @var array $user_fields
	 */
	private $user_fields = array();
	
	/**
	 * Import_Members constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Get or instantiate and return this class (Variables)
	 *
	 * @return Variables|null
	 */
	public static function get_instance() {
		
		$error_log = Error_Log::get_instance();
		if ( is_null( self::$instance ) ) {
			
			$error_log->debug( "Instantiating the Variables class" );
			
			self::$instance = new self();
			$class          = self::$instance;
			
			$class->load_settings();
			
			// Set the error log info
			$upload_dir          = wp_upload_dir();
			$class->logfile_path = trailingslashit( $upload_dir['basedir'] ) . 'e20r_im_errors.log';
			$class->logfile_url  = trailingslashit( $upload_dir['baseurl'] ) . 'e20r_im_errors.log';
			$class->fields       = apply_filters( 'e20r-import-members-supported-field-list', array() );
			
			/**
			 * @since v2.60 - ENHANCEMENT: Trigger attempted link of sponsor info after everything is done
			 */
			$class->_delayed_sponsor_link = get_option( 'e20r_link_for_sponsor', array() );
		}
		
		return self::$instance;
	}
	
	/**
	 * Load/configure settings from $_REQUEST array (if available)
	 */
	public function load_settings() {
		
		$error_log = Error_Log::get_instance();
		
		if ( true === $this->is_configured() ) {
			$error_log->debug( "The plugin is configured already" );
			
			return;
		}
		
		$error_log->debug( "Current file name: {$this->filename}" );
		$this->maybe_load_from_request();
		
		$error_log->debug( "Configured the file name: {$this->filename}" );
		$error_log->debug( "Settings users update to: " . ( $this->update_users ? "True" : "False" ) );
		$error_log->debug( "Do we suppress the changed password email? " . ( $this->suppress_pwdmsg ? 'Yes' : 'No' ) );
		
		// Calculate # of records to import per iteration
		$this->calculate_max_records( (bool) $this->new_member_notification, (bool) $this->create_order );
		
		$this->per_partial = apply_filters( 'pmp_im_import_records_per_scan', $this->per_partial );
		$this->per_partial = apply_filters( 'e20r_import_records_per_scan', $this->per_partial );
		
		// User data fields list used to differentiate with user meta
		$this->user_fields = apply_filters( 'e20r-import-members-wpuser-fields', array(
				'ID',
				'user_login',
				'user_pass',
				'user_email',
				'user_url',
				'user_nicename',
				'display_name',
				'user_registered',
				'first_name',
				'last_name',
				'nickname',
				'description',
				'rich_editing',
				'comment_shortcuts',
				'admin_color',
				'use_ssl',
				'show_admin_bar_front',
				'show_admin_bar_admin',
				'role',
			)
		);
	}
	
	/**
	 * Is the class configured (Request variables read to variables) already?
	 *
	 * @return bool
	 */
	private function is_configured() {
		
		return ! empty( $this->filename );
	}
	
	/**
	 * Load settings from the $_REQUEST array
	 */
	private function maybe_load_from_request() {
		
		$error_log = Error_Log::get_instance();
		$csv_file  = CSV::get_instance();
		
		$tmp_name       = isset( $_FILES['members_csv']['tmp_name'] ) ? $_FILES['members_csv']['tmp_name'] : $this->filename;
		$this->filename = isset( $_FILES['members_csv']['name'] ) ? $_FILES['members_csv']['name'] : $this->filename;
		
		if ( empty( $this->filename ) ) {
			$this->filename = basename( get_transient( 'e20r_import_filename' ) );
		}
		
		$error_log->debug( "File name from transient is {$this->filename} vs tmp name of {$tmp_name}" );
		
		if ( empty( $filename ) && ( ! empty( $tmp_name ) && file_exists( $tmp_name ) ) ) {
			
			$error_log->debug( "Update/move the {$tmp_name} file!" );
			$this->filename = $csv_file->pre_process_file( $tmp_name );
			
			if ( false === $this->filename && true === (bool) $this->background_import ) {
				$error_log->debug( "Will redirect since we're processing in the background" );
				wp_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );
			}
			
		} else if ( ! empty( $filename ) ) {
			$this->filename = $filename;
		}
		
		$this->update_users                = ! empty( $_REQUEST['update_users'] ) ? ( 1 === intval( $_REQUEST['update_users'] ) ) : $this->update_users;
		$this->background_import           = ! empty( $_REQUEST['background_import'] ) ? ( 1 === intval( $_REQUEST['background_import'] ) ) : $this->background_import;
		$this->deactivate_old_memberships  = ! empty( $_REQUEST['deactivate_old_memberships'] ) ? ( 1 === intval( $_REQUEST['deactivate_old_memberships'] ) ) : $this->deactivate_old_memberships;
		$this->create_order                = ! empty( $_REQUEST['create_order'] ) ? ( 1 === intval( $_REQUEST['create_order'] ) ) : $this->create_order;
		$this->password_nag                = ! empty( $_REQUEST['password_nag'] ) ? ( 1 === intval( $_REQUEST['password_nag'] ) ) : $this->password_nag;
		$this->password_hashing_disabled   = ! empty( $_REQUEST['password_hashing_disabled'] ) ? ( 1 === intval( $_REQUEST['password_hashing_disabled'] ) ) : $this->password_hashing_disabled;
		$this->new_user_notification       = ! empty( $_REQUEST['new_user_notification'] ) ? ( 1 === intval( $_REQUEST['new_user_notification'] ) ) : $this->new_user_notification;
		$this->suppress_pwdmsg             = ! empty( $_REQUEST['suppress_pwdmsg'] ) ? ( 1 === intval( $_REQUEST['suppress_pwdmsg'] ) ) : $this->suppress_pwdmsg;
		$this->admin_new_user_notification = ! empty( $_REQUEST['admin_new_user_notification'] ) ? 1 === intval( $_REQUEST['admin_new_user_notification'] ) : $this->admin_new_user_notification;
		$this->send_welcome_email          = ! empty( $_REQUEST['send_welcome_email'] ) ? 1 === intval( $_REQUEST['send_welcome_email'] ) : $this->send_welcome_email;
		$this->new_member_notification     = ! empty( $_REQUEST['new_member_notification'] ) ? ( 1 === intval( $_REQUEST['new_member_notification'] ) ) : (bool) $this->new_member_notification;
		$this->per_partial                 = ! empty( $_REQUEST['per_partial'] ) ? intval( $_REQUEST['per_partial'] ) : $this->per_partial;
		$this->site_id                     = ! empty( $_REQUEST['site_id'] ) ? intval( $_REQUEST['site_id'] ) : $this->site_id;
		$this->partial                     = $this->background_import; // Partial is true if background import is true
	}
	
	/**
	 * Calculate the # of records we'll attempt to import per iteration
	 *
	 * @param bool $include_welcome_message
	 * @param bool $include_order_link
	 */
	private function calculate_max_records( $include_welcome_message = false, $include_order_link = false ) {
		
		$error_log = Error_Log::get_instance();
		
		/**
		 * Calculate the # of records to import per operation when running in background mode
		 */
		$max_exec_time   = intval( floor( intval( get_cfg_var( 'max_execution_time' ) ) * 0.80 ) );
		$per_record_time = self::calculate_per_record_time();
		
		if ( ! empty( $max_exec_time ) && ( is_numeric( $per_record_time ) ) ) {
			$this->per_partial = round( ceil( $max_exec_time / (float) $per_record_time ), 0 );
		}
		
		if ( $this->per_partial > 60 ) {
			$this->per_partial = 60;
		}
		
		$error_log->debug( "Will allow up to {$this->per_partial} records per iteration, using up to {$per_record_time} seconds per record..." );
	}
	
	/**
	 * Figure out the amount of time to reserve per record (during import)
	 *
	 *
	 * @return int
	 */
	public static function calculate_per_record_time() {
		
		$per_record_time = apply_filters( 'e20r_import_time_per_record', 1.5 );
		$class           = self::$instance;
		
		// Add time for creating order(s) (reduces # of records per iteration)
		if ( true === (bool) $class->create_order ) {
			$per_record_time += apply_filters( 'e20r_order_link_time', 1 );
		}
		
		// Add time for sending the welcome message (reduces # of records per iteration)
		if ( true === (bool) $class->send_welcome_email ) {
			$per_record_time += apply_filters( 'e20r_weclome_msg_time', 4 );
		}
		
		return $per_record_time;
	}
	
	/**
	 * Save the value to the class variable
	 *
	 * @param string $variable_name
	 * @param mixed  $value
	 */
	public function set( $variable_name, $value ) {
		
		if ( isset( $this->{$variable_name} ) ) {
			$this->{$variable_name} = $value;
		}
	}
	
	/**
	 * Default setting value(s)
	 *
	 * @return array
	 */
	public function get_defaults() {
		
		return array(
			'filename'                    => null,
			'password_nag'                => false,
			'background_import'           => false,
			'new_user_notification'       => false,
			'admin_new_user_notification' => false,
			'send_welcome_email'          => false,
			'suppress_pwdmsg'             => false,
			'password_hashing_disabled'   => false,
			'update_users'                => false,
			'deactivate_old_memberships'  => false,
			'create_order'                => false,
			'partial'                     => false,
			'per_partial'                 => $this->get( 'per_partial' ),
			'site_id'                     => null,
		);
	}
	
	/**
	 * Fetch the specified variable
	 *
	 * @param string|null $variable_name
	 *
	 * @return mixed|null
	 */
	public function get( $variable_name = null ) {
		
		if ( empty( $variable_name ) ) {
			return $this->get_current_vars();
		}
		
		if ( ! isset( $this->{$variable_name} ) ) {
			return null;
		}
		
		return $this->{$variable_name};
	}
	
	/**
	 * Return the class variable/value pairs (except the $excluded list)
	 *
	 * @return array
	 *
	 * @access private
	 */
	private function get_current_vars() {
		
		$settings = array();
		$excluded = apply_filters( 'e20r-import-settings-to-exclude',
			array(
				'instance',
			
			)
		);
		
		foreach ( get_object_vars( $this ) as $var_name => $value ) {
			
			if ( in_array( $var_name, $excluded ) ) {
				continue;
			}
			
			$settings[ $var_name ] = $value;
		}
		
		return $settings;
	}
	
	/**
	 * Return the configured settings used by the background process
	 *
	 * @return array
	 */
	public function get_request_settings() {
		
		if ( false === $this->is_configured() ) {
			$this->load_settings();
		}
		
		$exclude = array(
			'instance',
			'user_fields',
			'_delayed_sponsor_link',
			'display_errors',
			'file_object',
			'welcome_mail_warning',
			'fields',
			'logfile_path',
			'logfile_url',
		);
		
		$request_settings = array();
		
		foreach ( get_object_vars( $this ) as $parameter_name => $value ) {
			
			if ( in_array( $parameter_name, $exclude ) ) {
				continue;
			}
			
			$request_settings[ $parameter_name ] = $value;
		}
		
		return $request_settings;
	}
	
	/**
	 * Disallow cloning this class
	 *
	 * @access private
	 */
	private function __clone() {
	}
}