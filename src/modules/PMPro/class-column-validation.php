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

namespace E20R\Import_Members\Modules\PMPro;

use E20R\Import_Members\Validate\Time;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Import_Members;
use WP_Error;


class Column_Validation extends Base_Validation {

	/**
	 * Instance of the PMPro Column_Validation class
	 *
	 * @var null|Column_Validation|Base_Validation $instance
	 */
	protected static $instance = null;

	/**
	 * Get or instantiate and get the current class
	 *
	 * @return Column_Validation|Base_Validation|null
	 */
	public static function get_instance() {

		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter(
				'e20r_import_errors_to_ignore',
				array( self::$instance, 'load_ignored_module_errors' ),
				10,
				2
			);

			// Add list of errors to ignore for the pmpro module
			self::$instance->errors_to_ignore = apply_filters(
				'e20r_import_errors_to_ignore',
				self::$instance->errors_to_ignore,
				'pmpro'
			);
		}

		return self::$instance;
	}

	/**
	 * Load action and filter handlers for PMPro validation
	 */
	public function load_actions() {

		$this->error_log->debug( 'Loading default field validation checks!' );

		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_membership_id' ), 1, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_invalid_membership_id' ), 2, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_no_startdate' ), 3, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_invalid_startdate' ), 4, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_invalid_enddate' ), 5, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'is_inactive_with_enddate' ), 6, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_valid_status' ), 7, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_valid_recurring_config' ), 8, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'can_link_subscription' ), 9, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_payment_id' ), 10, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_supported_gateway' ), 11, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'has_gateway_environment' ), 12, 3 );
		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'correct_gw_environment' ), 13, 3 );
	}

	/**
	 * Define the module specific errors to ignore
	 *
	 * @param array $ignored_error_list - List of error keys to ignore/treat as non-fatal
	 * @param string $module_name - Name of the module (BuddyPress)
	 *
	 * @return array
	 */
	public function load_ignored_module_errors( $ignored_error_list, $module_name = 'pmpro' ) {

		if ( 'pmpro' !== $module_name ) {
			return $ignored_error_list;
		}

		$this->error_log->debug( 'Loading PMPro specific error(s) to ignore' );

		// List of validation errors we should ignore
		// (don't prevent importing the record as a result of this validation error)
		$this->errors_to_ignore = array(
			'unrecognized_enddate' => true,
			'inactive_and_enddate' => true,
			'link_subscription'    => true,

		);

		return $ignored_error_list + $this->errors_to_ignore;
	}

	/**
	 *
	 * Can't import membership data when user has an invalid (non-existing) membership ID
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $record
	 *
	 * @return bool
	 */
	public function has_invalid_membership_id( $has_error, $user_id, $record ) {

		global $e20r_import_err;

		$this->error_log->debug( "Running 'has_invalid_membership_id' validations" );

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		$this->error_log->debug( 'Is the error code for membership_id set? ' . ( isset( $e20r_import_err['no_membership_id'] ) ? 'Yes' : 'No' ) );
		$this->error_log->debug( 'Is the membership_id value set? ' . ( isset( $record['membership_id'] ) ? 'Yes' : 'No' ) );
		$this->error_log->debug( 'Is the membership_id value numeric? ' . ( is_numeric( $record['membership_id'] ) ? 'Yes' : 'No' ) );
		$this->error_log->debug( "Is the membership_id value ({$record['membership_id']}) > 0? " . ( 0 <= intval( $record['membership_id'] ) ? 'Yes' : 'No' ) );

		if (
			( isset( $e20r_import_err['no_membership_id'] ) && ! empty( $e20r_import_err['no_membership_id'] ) ) ||
			( ! isset( $record['membership_id'] ) )
		) {
			$this->error_log->debug( "membership_id field wasn't found, so no level to test the validity of" );
			return $has_error;
		}

		$found_level = function_exists( 'pmpro_getLevel' ) ? pmpro_getLevel( $record['membership_id'] ) : 0;

		if ( ! empty( $found_level ) ) {
			return $has_error;
		}

		$msg = sprintf(
			// translators: %1$d: numeric PMPro membership id, %2$d Numeric user ID
			__(
				'Error: The membership ID (%1$d) specified for this user (ID: %2$d) is not a defined membership level, so we can\'t assign it. (Membership data not imported!)',
				'pmpro-import-members-from-csv'
			),
			$record['membership_id'],
			$user_id
		);

		$e20r_import_err['invalid_membership_id'] = new WP_Error( 'e20r_im_member', $msg );

		$has_error = ( ! $this->ignore_validation_error( 'invalid_membership_id' ) );

		return $has_error;
	}

	/**
	 * Can't import membership data when user has incorrect start date format
	 *
	 * @param bool $has_error
	 * @param int $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_invalid_startdate( $has_error, $user_id, $fields ) {

		global $e20r_import_err;
		$this->error_log->debug( "Running 'has_invalid_startdate' validations" );

		if ( ! isset( $fields['membership_startdate'] ) ) {
			$this->error_log->debug( 'No membership_startdate field in the record. Returning...' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		$validate = Validate_Data::get_instance();

		if (
			! empty( $fields['membership_startdate'] ) &&
			false === $validate->date( $fields['membership_startdate'] )
		) {

			$msg = sprintf(
				// translators: %1$s - Start date for membership (from CSV file), %2$d - WP User ID
				__(
					'Error: The start date (membership_startdate) column in the import .CSV file must use a MySQL formatted date (YYYY-MM-DD HH:MM:SS). Your file uses \'%1$s\' (Membership data not imported for user with ID %2$d!)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_startdate'],
				$user_id
			);

			$e20r_import_err['startdate_format_error'] = new WP_Error( 'e20r_im_member', $msg );

			$should_be = Time::convert( $fields['membership_startdate'] );
			$should_be = ( false === $should_be ? time() : $should_be );

			$e20r_import_err['startdate_format'] = sprintf(
				// translators: %1$s - Member start date from CSV file, %2$s - HTML formatting, %3$s - HTML formatting, %4$s - current date/time in expected format
				__(
					'Error: The %2$smembership_startdate column%3$s contains an unrecognized date/time format. (Your format: \'%1$s\'. Expected format: \'%4$s\'). Membership data will not be imported for this user (ID: %5$d )!',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_startdate'],
				'<strong>',
				'</strong>',
				date_i18n( 'Y-m-d h:i:s', $should_be ),
				$user_id
			);

			$has_error = ( ! $this->ignore_validation_error( 'startdate_format' ) );
		}

		return $has_error;
	}

	/**
	 * Can't import membership data when user has incorrect start date format or value
	 *
	 * @param bool $has_error
	 * @param int $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_no_startdate( $has_error, $user_id, $fields ) {

		global $e20r_import_err;

		$this->error_log->debug( "Running 'has_no_startdate' validations" );

		$validate = Validate_Data::get_instance();

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		// Have a date field, but no date supplied
		if ( isset( $fields['membership_startdate'] ) && empty( $fields['membership_startdate'] ) ) {
			$msg = sprintf(
				// translators: %1$s - CSV file membership_startdate content, %2$d - User ID for imported user
				__(
					'Error: No start date provided for user\'s membership (field: %1$s) (user: %2$d)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_startdate'],
				$user_id
			);

			$e20r_import_err['no_startdate'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                       = true;
		}

		// Make sure the start date field has the right format
		if (
			isset( $fields['membership_startdate'] ) &&
			! empty( $fields['membership_startdate'] ) &&
			false === $validate->date( $fields['membership_enddate'] )
		) {

			$msg = sprintf(
				// translators: %1$s - date value from CSV file, %2$d - WP User ID for record being imported
				__(
					'Error: Invalid datetime format used for %1$s. Needs to be \'YYYY-MM-DD HH:MM:SS\' (for user/ID: %2$d)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_startdate'],
				$user_id
			);

			$e20r_import_err['no_startdate'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                       = ( ! $this->ignore_validation_error( 'no_startdate' ) );
		}
		return $has_error;
	}

	/**
	 * Possible problem for membership data when user has incorrect end date format or value
	 *
	 * @param bool $has_error
	 * @param int $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_invalid_enddate( $has_error, $user_id, $fields ) {

		$validate = Validate_Data::get_instance();
		$this->error_log->debug( "Running 'has_invalid_enddate' validations" );

		global $e20r_import_err;

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! isset( $fields['membership_enddate'] ) ) {
			$this->error_log->debug( 'No membership_enddate log set. Returning from check' );
			return $has_error;
		}

		/**
		 * Can't import membership if user has incorrect enddate format
		 */
		if (
			! empty( $fields['membership_enddate'] ) &&
			false === $validate->date( $fields['membership_enddate'] )
		) {

			$msg = sprintf(
				// translators: %1$d - User ID, %2$d - Date (MySQL format)
				__( 'Error: The membership end date (membership_enddate) column for the user (ID: %1$d) in the import .CSV file must use a MySQL formatted date (YYYY-MM-DD HH:MM:SS). You appear to have used \'%2$s\' (Membership data not imported!)', 'pmpro-import-members-from-csv' ),
				$user_id,
				$fields['membership_enddate']
			);

			$e20r_import_err['bad_format_enddate'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                             = ( ! $this->ignore_validation_error( 'bad_format_enddate' ) );

			$should_be = Time::convert( $fields['membership_enddate'] );
			$should_be = ( false === $should_be ? time() : $should_be );

			$e20r_import_err['unrecognized_enddate'] = sprintf(
				// translators: %1$s - Membership end-date from CSV file, %2$s - Example of correctly formatted date/time value
				__( 'Error: The membership_enddate column contains an unrecognized date/time format. (Your format: \'%1$s\'. Expected format: \'%2$s\') Membership data may not have been imported!', 'pmpro-import-members-from-csv' ),
				$fields['membership_enddate'],
				date_i18n( 'Y-m-d h:i:s', $should_be )
			);
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
		$this->error_log->debug( "Running 'is_inactive_with_enddate' validations" );

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! ( isset( $fields['membership_enddate'] ) && isset( $fields['membership_status'] ) ) ) {
			$this->error_log->debug( 'membership_enddate and membership_status need to both be present for check...' );
			return $has_error;
		}

		if (
			! empty( $fields['membership_enddate'] ) &&
			! empty( $fields['membership_status'] ) &&
			'inactive' === $fields['membership_status'] &&
			( time() < strtotime( $fields['membership_enddate'], time() ) )
		) {

			$msg = sprintf(
				// translators: %1$d PMpro membership level id, %2$d - WP User ID, %3$s - End date from CSV file, %4$s - incorrect value from CSV file
				__(
					'Notice: The membership (id: %1$d) for user ID %2$d will end at a future date (membership_enddate = %3$s), but you set the status to %4$s...',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_id'],
				$user_id,
				$fields['membership_enddate'],
				$fields['membership_status']
			);

			$e20r_import_err['inactive_and_enddate'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                               = ( ! $this->ignore_validation_error( 'inactive_and_enddate' ) );
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
		$this->error_log->debug( "Running 'has_valid_status' validations" );

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! isset( $fields['membership_status'] ) ) {
			$this->error_log->debug( 'membership_status not present for this record...' );
			return $has_error;
		}

		$valid_statuses = apply_filters(
			'e20r_import_valid_member_status',
			array(
				'active',
				'inactive',
				true,
				1,
			)
		);

		if (
			! empty( $fields['membership_status'] ) &&
			! in_array( $fields['membership_status'], $valid_statuses, true )
		) {

			$msg = sprintf(
				// translators: %1$d - WP User ID, %2$s - CSV supplied, invalid, membership status
				__(
					"Error: The membership_status column for user (ID: %1\$d) contains an unexpected value (expected values: 'active' or 'inactive'). You used '%2\$s' (Membership data not imported!)",
					'pmpro-import-members-from-csv'
				),
				$fields['membership_status'],
				$user_id
			);

			$e20r_import_err['valid_status'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                       = ( ! $this->ignore_validation_error( 'valid_status' ) );
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
		$this->error_log->debug( "Running 'can_link_subscription' validations" );

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( !
		(
			isset( $fields['membership_subscription_transaction_id'] ) &&
			isset( $fields['membership_gateway'] ) &&
			isset( $fields['membership_gateway_environment'] )
		)
		) {
			$this->error_log->debug( "No 'membership_subscription_transaction_id', 'membership_gateway', 'membership_gateway_environment' fields found for record. Skipping 'can_link_subscription' check..." );
			return $has_error;
		}

		if (
			! empty( $fields['membership_subscription_transaction_id'] ) && (
				empty( $fields['membership_gateway'] ) ||
				empty( $fields['membership_gateway_environment'] )
			)
		) {

			$msg = sprintf(
				// translators: %1$s CSV supplied value, %2$s - CSV supplied value, %3$s - CSV supplied value, %4$d - User ID
				__(
					'Error: You need to specify \'membership_gateway\' (value: %1$s), \'membership_gateway_environment\' (value: %2$s) and the \'membership_subscription_transaction_id\' (value: %3$s) to link active subscription plan(s) to order(s). (Unable to link subscription %1$s for this member\'s (ID: %4$s) order)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_gateway'],
				$fields['membership_gateway_environment'],
				$fields['membership_subscription_transaction_id'],
				$user_id
			);

			$e20r_import_err['link_subscription'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                            = ( ! $this->ignore_validation_error( 'link_subscription' ) );
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
		$this->error_log->debug( "Running 'has_valid_recurring_config' validations" );

		if ( ! ( isset( $fields['membership_cycle_number'] ) && isset( $fields['membership_cycle_period'] ) ) ) {
			$this->error_log->debug( 'membership_cycle_number and membership_cycle_period need to both exist for check' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		/**
		 * Can't import if the cycle period is defined but has no cycle number
		 */
		if (
			empty( $fields['membership_cycle_number'] ) &&
			! empty( $fields['membership_cycle_period'] )
		) {
			$msg = sprintf(
				// translators: %1$s - CSV supplied value, %2$d - User ID
				__(
					'Warning: You have configured a Membership Cycle Period (%1$s) for the user (ID %2$d), but you haven\'t included the number of periods (\'membership_cycle_number\'). Can\'t import member data user with ID %3$d',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_cycle_period'],
				$user_id
			);

			$e20r_import_err['invalid_recurring_config'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                                   = ( ! $this->ignore_validation_error( 'invalid_recurring_config' ) );
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
		$this->error_log->debug( "Running 'has_payment_id' validations" );

		if ( ! ( isset( $fields['membership_subscription_transaction_id'] ) && isset( $fields['membership_payment_transaction_id'] ) ) ) {
			$this->error_log->debug( 'membership_subscription_transaction_id and membership_payment_transaction_id need to both exist for check' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! empty( $fields['membership_subscription_transaction_id'] ) && empty( $fields['membership_payment_transaction_id'] ) ) {
			$msg = sprintf(
				// translators: %1$s - CSV supplied value, %2$d - User ID
				__(
					'Notice: You have defined a subscription_transaction_id (%1$s) without also including a payment_transaction_id for user (ID: %2$d)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_subscription_transaction_id'],
				$user_id
			);
			$e20r_import_err['sub_id_no_paym_id'] = new WP_Error( 'e20r_im_member', $msg );
			$has_error                            = ( ! $this->ignore_validation_error( 'sub_id_no_paym_id' ) );
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
	public function has_gateway_environment( $has_error, $user_id, $fields ) : bool {

		global $e20r_import_err;
		$this->error_log->debug( "Running 'has_gateway_environment' validations" );

		if ( ! ( isset( $fields['membership_gateway'] ) && isset( $fields['membership_gateway_environment'] ) ) ) {
			$this->error_log->debug( 'membership_gateway and membership_gateway_environment need to both exist for check' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! empty( $fields['membership_gateway'] ) && empty( $fields['membership_gateway_environment'] ) ) {

			$msg = sprintf(
				// translators: %1$s - CSV supplied value, %2$d - User ID
				__(
					'Notice: You specified a membership_gateway (%1$s), but you didn\'t specify the gateway environment to use (membership_gateway_environment) for user with ID %2$d. Using current PMPro setting.',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_gateway'],
				$user_id
			);

			$e20r_import_err['no_gw_environment'] = new WP_Error( 'e20r_im_member', $msg );
		}

		return $has_error;
	}

	/**
	 * Make sure the membership_id column is present in the import file
	 *
	 * @param bool  $has_error
	 * @param int   $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function has_membership_id( $has_error, $user_id, $fields ) {

		global $e20r_import_err;
		global $active_line_number;

		$this->error_log->debug( "Running 'has_membership_id' validations" );

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! isset( $fields['membership_id'] ) ) {
			$this->error_log->debug( "Record doesn't contain membership id? -> " . print_r( $fields, true ) ); // phpcs:ignore

			$msg = sprintf(
				// translators: %1$d - PMPro Membership Level ID
				__(
					'Error: The membership ID (membership_id) column is not present in the import file (ID: %1$d). (Membership cannot be imported!)',
					'pmpro-import-members-from-csv'
				),
				$user_id
			);

			$e20r_import_err[ "no_membership_id_column_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

			$has_error = ( ! $this->ignore_validation_error( 'no_membership_id_column' ) );
		}

		if ( false === $has_error && false === is_numeric( $fields['membership_id'] ) ) {

			$msg = sprintf(
				// translators: %1$d - User ID
				__(
					'Error: The membership ID (membership_id) column does not contain a numeric membership Level for user (ID: %1$d). (Membership data not imported!)',
					'pmpro-import-members-from-csv'
				),
				$user_id
			);

			$e20r_import_err[ "no_membership_id_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

			$has_error = ( ! $this->ignore_validation_error( 'no_membership_id' ) );
		}

		// Allow cancelling memberships, but trigger a warning!
		if ( false === $has_error && empty( $fields['membership_id'] ) ) {

			$msg = sprintf(
				// translators: %1$d - User ID
				__(
					'Warning: May cancel membership for user (ID: %1$d) since there is no membership ID assigned for them.',
					'pmpro-import-members-from-csv'
				),
				$user_id
			);

			$e20r_import_err[ "cancelling_membership_level_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

			$has_error = ( ! $this->ignore_validation_error( 'cancelling_membership_level' ) );
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

		$this->error_log->debug( "Running 'level_exists' validations" );

		if ( ! isset( $fields['membership_id'] ) ) {
			$this->error_log->debug( 'membership_id needs to exist in the record for check to make sense' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( isset( $fields['membership_id'] ) && false === pmpro_getLevel( $fields['membership_id'] ) ) {

			$msg = sprintf(
				// translators: %1$d - PMPro Membership Level ID, %2$d - User ID
				__(
					'Error: The provided membership ID (%1$d) is not valid. (Membership data not imported for user (ID: %2$d)!)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_id'],
				$user_id
			);

			$e20r_import_err[ "level_exists_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

			$has_error = ( ! $this->ignore_validation_error( 'level_exists' ) );
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
		$this->error_log->debug( "Running 'recurring_and_enddate' validations" );

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! ( isset( $fields['membership_enddate'] ) && isset( $fields['membership_cycle_number'] ) && isset( $fields['membership_cycle_period'] ) ) ) {
			$this->error_log->debug( 'membership_enddate, membership_cycle_number and membership_cycle_period needs to exist in record to test it' );
			return $has_error;
		}

		if (
			! empty( $fields['membership_enddate'] ) &&
			! empty( $fields['membership_cycle_number'] ) &&
			! empty( $fields['membership_cycle_period'] )
		) {

			$msg = sprintf(
				// translators: %1$d - User ID
				__(
					'Warning: You have an end date AND a recurring billing configuration (the membership_enddate, membership_billing_amount, membership_cycle_number and membership_cycle_period columns are not empty) user (ID: %1$d). Could result in an incorrectly configured membership for this user',
					'pmpro-import-members-from-csv'
				),
				$user_id
			);

			$e20r_import_err[ "recurring_w_enddate_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

			$has_error = ( ! $this->ignore_validation_error( 'recurring_w_enddate' ) );
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
		$this->error_log->debug( "Running 'correct_gw_environment' validations" );

		if ( ! ( isset( $fields['membership_gateway'] ) && isset( $fields['membership_gateway_environment'] ) ) ) {
			$this->error_log->debug( 'membership_gateway, membership_gateway_environment need to exist in record to test it' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if (
			! empty( $fields['membership_gateway'] ) &&
			! empty( $fields['membership_gateway_environment'] ) &&
			! in_array( $fields['membership_gateway_environment'], array( 'live', 'sandbox' ), true )
		) {
			$msg = sprintf(
				// translators: %1$s - CSV supplied value, %2$d - User ID
				__(
					'Error: You specified a payment gateway integration (membership_gateway) to use, but we do not recognize the gateway environment you have specified for this record (membership_gateway_environment: %1$s, User ID: %3$d). (Skipping!)',
					'pmpro-import-members-from-csv'
				),
				$fields['membership_gateway_environment'],
				$user_id
			);

			$e20r_import_err[ "correct_gw_env_variable_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

			$has_error = ( ! $this->ignore_validation_error( 'correct_gw_env_variable' ) );
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

		$this->error_log->debug( "Running 'has_supported_gateway' validations" );

		if ( ! isset( $fields['membership_gateway'] ) ) {
			$this->error_log->debug( 'membership_gateway needs to exist in record to test it' );
			return $has_error;
		}

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! empty( $fields['membership_gateway'] ) ) {

			$gateways = Import_Members::is_pmpro_active() ? pmpro_gateways() : array();

			if ( ! in_array( $fields['membership_gateway'], array_keys( $gateways ), true ) ) {
				$msg = sprintf(
					// translators: %1$s - CSV supplied value, %2$d - User ID
					__(
						'Warning: The payment gateway integration provided (membership_gateway: %1$s) is not one of the supported payment gateway integrations! (Changed and using the default value for user (ID: %2$d) )',
						'pmpro-import-members-from-csv'
					),
					$fields['membership_gateway'],
					$user_id
				);

				$e20r_import_err[ "supported_gateway_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );

				$has_error = ( ! $this->ignore_validation_error( 'supported_gateway' ) );
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
