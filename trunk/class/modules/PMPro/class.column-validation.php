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

namespace E20R\Paid_Memberships_Pro\Import_Members\Modules\PMPro;


use E20R\Paid_Memberships_Pro\Import_Members\Validate\Time;
use E20R\Paid_Memberships_Pro\Import_Members\Validate\Validate;
use E20R\Paid_Memberships_Pro\Import_Members\Validate_Data;
use E20R\Paid_Memberships_Pro\Import_Members\Import_Members_From_CSV;

class Column_Validation {
	
	/**
	 * Instance of the column validation logic for PMPro
	 *
	 * @var null|Column_Validation
	 */
	private static $instance = null;
	
	/**
	 * Column_Validation constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Get or instantiate and get the current class
	 *
	 * @return Column_Validation|null
	 */
	public static function get_instance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load action and filter handlers for PMPro validation
	 */
	public function load_actions() {
		
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_membership_id' ), 1, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_invalid_membership_id' ), 2, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_no_startdate' ), 3, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_invalid_startdate' ), 4, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_invalid_enddate' ), 5, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'is_inactive_with_enddate' ), 6, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_valid_status' ), 7, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_valid_recurring_config', 8, 3 ) );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'can_link_subscription' ), 9, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_payment_id' ), 10, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_supported_gateway' ), 11, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'has_gateway_environment' ), 12, 3 );
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'correct_gw_environment' ), 13, 3 );
	}
	
	/**
	 *
	 * Can't import membership data when user has an invalid (non-existing) membership ID
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_invalid_membership_id( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! isset( $e20r_import_err['no_membership_id'] ) && (
				isset( $fields['membership_id'] ) &&
				is_numeric( $fields['membership_id'] ) &&
				0 < $fields['membership_id'] )
		) {
			
			$found_level = pmpro_getLevel( $fields['membership_id'] );
			
			if ( empty( $found_level ) ) {
				$msg = sprintf( __(
					'Error: The membership ID (%d) specified for this user (ID: %d) is not a defined membership level, so we can\'t assign it. (Membership data not imported!)',
					Import_Members_From_CSV::plugin_slug
				),
					$fields['membership_id'],
					$user_id
				);
				
				$errors['invalid_membership_id'] = new \WP_Error( 'e20r_im_member', $msg );
				$has_error                       = true;
			}
		}
		
		return $has_error;
	}
	
	/**
	 * Can't import membership data when user has incorrect start date format
	 *
	 * @param $has_error
	 * @param $user_id
	 * @param $fields
	 *
	 * @return bool
	 */
	public function has_invalid_startdate( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		$validate = Validate_Data::get_instance();
		
		if ( ! empty( $fields['membership_startdate'] ) &&
		     false === $validate->date( $fields['membership_startdate'] ) ) {
			
			$msg = sprintf(
				__(
					'Error: The start date (membership_startdate) column in the import .CSV file must use a MySQL formatted date (YYYY-MM-DD HH:MM:SS). Your file uses \'%s\' (Membership data not imported for user with ID %d!)',
					Import_Members_From_CSV::plugin_slug
				),
				$fields['membership_startdate'],
				$user_id
			);
			
			$errors[]  = new \WP_Error( 'e20r_im_member', $msg );
			$has_error = true;
			
			$should_be = Time::convert( $fields['membership_startdate'] );
			$should_be = false === $should_be ? current_time( 'timestamp' ) : $should_be;
			
			$e20r_import_err['startdate_format'] = sprintf( __( 'Error: The %2$smembership_startdate column%3$s contains an unrecognized date/time format. (Your format: \'%1$s\'. Expected format: \'%4$s\'). Membership data will not be imported for this user (ID: %5$d )!', Import_Members_From_CSV::plugin_slug ), $fields['membership_startdate'], '<strong>', '</strong>', date( 'Y-m-d h:i:s', $should_be ), $user_id );
		}
		
		return $has_error;
	}
	
	/**
	 * Can't import membership data when user has incorrect start date format or value
	 *
	 * @param $has_error
	 * @param $user_id
	 * @param $fields
	 *
	 * @return bool
	 */
	public function has_no_startdate( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		$validate = Validate_Data::get_instance();
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		// Have a date field, but no date supplied
		if ( isset( $fields['membership_startdate'] ) && empty( $fields['membership_startdate'] ) ) {
			
			$msg = sprintf(
				__(
					'Error: No start date provided for user\'s membership (field: %1$s) (user: %2$d)',
					Import_Members_From_CSV::plugin_slug
				),
				$fields['membership_startdate'],
				$user_id
			);
			
			$e20r_import_err['no_startdate'] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                       = true;
		}
		
		// Make sure the start date field has the right format
		if ( isset( $fields['membership_startdate'] ) &&
		     ! empty( $fields['membership_startdate'] ) &&
		     false === $validate->date( $fields['membership_enddate'] )
		) {
			
			$msg = sprintf(
				__(
					'Error: Invalid datetime format used for %1$s. Needs to be \'YYYY-MM-DD HH:MM:SS\' (for user/ID: %2$d)',
					Import_Members_From_CSV::plugin_slug
				),
				$fields['membership_startdate'],
				$user_id
			);
			
			$e20r_import_err['no_startdate'] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                       = true;
		}
		
		return $has_error;
	}
	
	/**
	 * Possible problem for membership data when user has incorrect end date format or value
	 *
	 * @param $has_error
	 * @param $user_id
	 * @param $fields
	 *
	 * @return bool
	 */
	public function has_invalid_enddate( $has_error, $user_id, $fields ) {
		
		$validate = Validate_Data::get_instance();
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		/**
		 * Can't import membership if user has incorrect enddate format
		 */
		if ( ! empty( $fields['membership_enddate'] ) &&
		     false === $validate->date( $fields['membership_enddate'] ) ) {
			
			$msg = sprintf( __( 'Error: The membership end date (membership_enddate) column for the user (ID: %d) in the import .CSV file must use a MySQL formatted date (YYYY-MM-DD HH:MM:SS). You appear to have used \'%s\' (Membership data not imported!)', Import_Members_From_CSV::plugin_slug ), $user_id, $fields['membership_enddate'] );
			
			$e20r_import_err['bad_format_enddate'] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                             = true;
			
			$should_be = Time::convert( $fields['membership_enddate'] );
			$should_be = false === $should_be ? current_time( 'timestamp' ) : $should_be;
			
			$e20r_import_err['unrecognized_enddate'] = sprintf(
				__( 'Error: The membership_enddate column contains an unrecognized date/time format. (Your format: \'%1$s\'. Expected format: \'%2$s\') Membership data may not have been imported!', Import_Members_From_CSV::plugin_slug ),
				$fields['membership_enddate'],
				date( 'Y-m-d h:i:s', $should_be ) );
		}
		
		return $has_error;
	}
	
	/**
	 * Warn if the user has an enddate in the future but have had their membership_status set to 'inactive'
	 *
	 * NOTE: Not really an error, but we should warn about it
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function is_inactive_with_enddate( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_enddate'] ) &&
		     isset( $fields['membership_status'] ) && 'inactive' == $fields['membership_status'] &&
		     ( current_time( 'timestamp' ) < strtotime( $fields['membership_enddate'], current_time( 'timestamp' ) ) )
		) {
			
			$msg = sprintf(
				__(
					'Notice: The membership (id: %1$d) for user ID %2$d will end at a future date (membership_enddate = %3$s), but you set the status to %4$s...',
					Import_Members_From_CSV::plugin_slug
				),
				$fields['membership_id'],
				$user_id,
				$fields['membership_enddate'],
				$fields['membership_status']
			);
			
			$e20r_import_err['inactive_and_enddate'] = new \WP_Error( 'e20r_im_member', $msg );
		}
		
		return $has_error;
	}
	
	/**
	 * Is the membership status configured correctly for the user being imported ('inactive' or 'active')
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_valid_status( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		$valid_statuses = apply_filters( 'e20r-import-members-valid-member-status', array(
			'active',
			'inactive',
			true,
			1,
		) );
		
		if ( ! empty( $fields['membership_status'] ) &&
		     ! in_array( $fields['membership_status'], $valid_statuses ) ) {
			
			$msg = sprintf( __( "Error: The membership_status column for user (ID: %d) contains an unexpected value (expected values: 'active' or 'inactive'). You used '%s' (Membership data not imported!)", Import_Members_From_CSV::plugin_slug ), $fields['membership_status'], $user_id );
			
			$e20r_import_err['valid_status'] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                       = true;
		}
		
		return $has_error;
	}
	
	/**
	 * No gateway specified when importing subscription_transaction_id (or subscription transaction ID(s) without
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function can_link_subscription( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_subscription_transaction_id'] ) &&
		     ( empty( $fields['membership_gateway'] ) || empty( $fields['membership_gateway_environment'] ) )
		) {
			
			$msg = sprintf(
				__(
					'Error: You need to specify \'membership_gateway\' (value: %1$s), \'membership_gateway_environment\' (value: %2$s) and the \'membership_subscription_transaction_id\' (value: %3$s) to link active subscription plan(s) to order(s). (Unable to link subscription %1$s for this member\'s (ID: %4$s) order)',
					Import_Members_From_CSV::plugin_slug
				),
				$fields['membership_gateway'],
				$fields['membership_gateway_environment'],
				$fields['membership_subscription_transaction_id'],
				$user_id
			);
			
			$e20r_import_err['link_subscription'] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                            = true;
		}
		
		return $has_error;
	}
	
	/**
	 * Is the Recurring configuration supplied a valid configuration?
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_valid_recurring_config( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		/**
		 * Can't import if the cycle period is defined but has no cycle number
		 */
		if ( empty( $fields['membership_cycle_number'] ) &&
		     ! empty( $fields['membership_cycle_period'] )
		) {
			$msg = sprintf(
				__(
					'Warning: You have configured a Membership Cycle Period (%s) for the user (ID %d), but you haven\'t included the number of periods (\'membership_cycle_number\'). Can\'t import member data user with ID %1$d',
					Import_Members_From_CSV::plugin_slug ),
				$fields['membership_cycle_period'],
				$user_id
			);
			
			$e20r_import_err['invalid_recurring_config'] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                                   = true;
		}
		
		return $has_error;
	}
	
	/**
	 * Has a subscription_transaction_id, but we also expect there to be a payment transaction ID
	 *
	 * NOTE: Not an error, but worth warning about
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_payment_id( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_subscription_transaction_id'] ) && empty( $fields['membership_payment_transaction_id'] ) ) {
			$msg                                  = sprintf( __( 'Notice: You have defined a subscription_transaction_id (%s) without also including a payment_transaction_id for user (ID: %d)', Import_Members_From_CSV::plugin_slug ), $fields['membership_subscription_transaction_id'], $user_id );
			$e20r_import_err['sub_id_no_paym_id'] = new \WP_Error( 'e20r_im_member', $msg );
		}
		
		return $has_error;
	}
	
	/**
	 * Gateway environment specified when importing with a gateway integration in the data row?
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_gateway_environment( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_gateway'] ) && empty( $fields['membership_gateway_environment'] ) ) {
			
			$msg = sprintf(
				__(
					'Notice: You specified a membership_gateway (%s), but you didn\'t specify the gateway environment to use (membership_gateway_environment). Using current PMPro setting.', Import_Members_From_CSV::plugin_slug ),
				$fields['membership_gateway']
			);
			
			$e20r_import_err['no_gw_environment'] = new \WP_Error( 'e20r_im_member', $msg );
		}
		
		return $has_error;
	}
	
	/**
	 * Make sure the membership_id column is present in the import file
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 * @param array $errors
	 *
	 * @return bool
	 */
	public function has_membership_id( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! isset( $fields['membership_id'] ) ) {
			$msg = sprintf( __(
				'Error: The membership ID (membership_id) column is not present in the import file (ID: %d). (Membership cannot be imported!)', Import_Members_From_CSV::plugin_slug ), $user_id );
			
			$e20r_import_err["no_membership_id_column_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                                                        = true;
		}
		
		if ( false ===  $has_error && false === is_numeric( $fields['membership_id'] ) ) {
			
			$msg = sprintf( __(
				'Error: The membership ID (membership_id) column does not contain a numeric membership Level for user (ID: %d). (Membership data not imported!)', Import_Members_From_CSV::plugin_slug ), $user_id );
			
			$e20r_import_err["no_membership_id_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                                                 = true;
		}
		
		// Allow cancelling memberships, but trigger a warning!
		if ( false ===  $has_error && empty( $fields['membership_id'] ) ) {
			
			$msg = sprintf( __(
				'Warning: May cancel membership for user (ID: %d) since there is no membership ID assigned for them.', Import_Members_From_CSV::plugin_slug ), $user_id );
			
			$e20r_import_err["cancelling_membership_level_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                                                            = false;
		}
		
		return $has_error;
	}
	
	/**
	 * Make sure the specified level ID exists on the system
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function level_exists( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( isset( $fields['membership_id'] ) && false === pmpro_getLevel( $fields['membership_id'] ) ) {
			
			$msg = sprintf(
				__(
					'Error: The provided membership ID (%d) is not valid. (Membership data not imported for user (ID: %d)!)',
					Import_Members_From_CSV::plugin_slug
				),
				$fields['membership_id'],
				$user_id
			);
			
			$e20r_import_err["level_exists_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                                             = true;
		}
		
		return $has_error;
	}
	
	/**
	 * If the import file specifies membership_enddate + recurring billing setup, it is probably _not_ correct...
	 *
	 * NOTE: Technically _NOT_ an error, but worth recording
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function recurring_and_enddate( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_enddate'] ) &&
		     ! empty( $fields['membership_cycle_number'] ) &&
		     ! empty( $fields['membership_cycle_period'] ) ) {
			
			$msg = sprintf(
				__(
					'Warning: You have an end date AND a recurring billing configuration (the membership_enddate, membership_billing_amount, membership_cycle_number and membership_cycle_period columns are not empty) user (ID: %d). Could result in an incorrectly configured membership for this user',
					Import_Members_From_CSV::plugin_slug
				),
				$user_id
			);
			
			$e20r_import_err["recurring_w_enddate_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
		}
		
		return $has_error;
	}
	
	/**
	 * Specified a gateway integration and environment, is the environment value supplied the one we'd expect?
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function correct_gw_environment( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_gateway'] ) && ! empty( $fields['membership_gateway_environment'] ) && ! in_array( $fields['membership_gateway_environment'], array(
				'live',
				'sandbox',
			) ) ) {
			$msg                                                              = sprintf( __( 'Error: You specified a payment gateway integration (membership_gateway) to use, but we do not recognize the gateway environment you have specified for this record (membership_gateway_environment: %2$s). (Skipping!)', Import_Members_From_CSV::plugin_slug ), $fields['membership_gateway_environment'] );
			$e20r_import_err["correct_gw_env_variable_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
			$has_error                                                        = true;
		}
		
		return $has_error;
	}
	
	/**
	 * Did they specify a supported PMPro gateway?
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_supported_gateway( $has_error, $user_id, $fields ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		if ( ! empty( $fields['membership_gateway'] ) ) {
			
			$gateways = Import_Members_From_CSV::is_pmpro_active() ? pmpro_gateways() : array();
			
			if ( ! in_array( $fields['membership_gateway'], array_keys( $gateways ) ) ) {
				$msg = sprintf(
					__(
						'Warning: The payment gateway integration provided (membership_gateway: %s) is not one of the supported payment gateway integrations! (Changed and using the default value for user (ID: %d) )',
						Import_Members_From_CSV::plugin_slug
					),
					$fields['membership_gateway'],
					$user_id
				);
				
				$e20r_import_err["supported_gateway_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
				$has_error                                                  = true;
			}
		}
		
		return $has_error;
	}
	
	/**
	 * Disable the __clone() magic method
	 *
	 * @access private
	 */
	private function __clone() {
		// TODO: Implement __clone() method.
	}
	
}