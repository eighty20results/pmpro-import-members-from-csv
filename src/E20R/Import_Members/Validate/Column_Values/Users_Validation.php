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
 * @package E20R\Import_Members\Validate\Column_Values\Users_Validation
 */

namespace E20R\Import_Members\Validate\Column_Values;

use E20R\Exceptions\InvalidProperty;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Status;
use E20R\Utilities\Utilities;
use WP_Error;

if ( ! class_exists( 'E20R\Import_Members\Validate\Column_Values\Users_Validation' ) ) {
	/**
	 * Used to validate the column data for WordPress users being imported/updated
	 */
	class Users_Validation extends Base_Validation {

		/**
		 * Instance of the WP_Error() class
		 *
		 * @var null|WP_Error
		 */
		private $wp_error = null;

		/**
		 * Constructor for the Users_Validation class
		 *
		 * @param Variables|null $variables Instance of the Variables() class
		 * @param Error_Log|null $error_log Instance of the Error_Log() class
		 *
		 * @throws InvalidProperty Thrown when the Import::get() operation uses the wrong property
		 */
		public function __construct( $variables = null, $error_log = null ) {
			parent::__construct( $variables, $error_log );

			add_filter(
				'e20r_import_errors_to_ignore',
				array( $this, 'load_ignored_module_errors' ),
				10,
				2
			);

			// Add list of errors to ignore for the BuddyPress module
			$this->errors_to_ignore = apply_filters(
				'e20r_import_errors_to_ignore',
				$this->errors_to_ignore,
				'users'
			);
		}

		/**
		 * Load action and filter handlers for User validation
		 */
		public function load_actions() {
			$this->error_log->debug( 'Loading default field validation checks!' );
			add_filter( 'e20r_import_users_validate_field_data', array( $this, 'validate_user_id' ), 1, 3 );
			add_filter( 'e20r_import_users_validate_field_data', array( $this, 'validate_email' ), 2, 3 );
		}

		/**
		 * Define the module specific errors to ignore
		 *
		 * @param array $ignored_error_list - List of error keys to ignore/treat as non-fatal
		 * @param string $module_name - Name of the module (Users)
		 *
		 * @return array
		 */
		public function load_ignored_module_errors( $ignored_error_list, $module_name = 'users' ) {

			if ( 'users' !== $module_name ) {
				return $ignored_error_list;
			}

			$this->error_log->debug( 'Loading WP User specific error(s) to ignore' );
			$this->errors_to_ignore = array();

			return $ignored_error_list + $this->errors_to_ignore;
		}

		/**
		 * Verify that the record contains a valid email address or user_login AND that the user doesn't exist (if we're updating)
		 *
		 * @param bool $success Whether the validation is successful or not
		 * @param int $user_id ID of WP_User record being imported/updated
		 * @param array $record The supplied user array (record) from the .CSV file row
		 * @param string|null $field_name The field name to test (deprecated)
		 *
		 * @return bool|int
		 *
		 * @throws InvalidProperty Raised if the 'update_users' key is not a valid setting/variable
		 */
		public function validate_email( $success, $user_id, $record, $field_name = null, $wp_error = null ) {

			global $active_line_number;

			$allow_update = (bool) $this->variables->get( 'update_users' );

			if ( isset( $record['user_login'] ) && ! empty( 'user_login' ) && false === $allow_update && false !== get_user_by( 'login', $record['user_login'] ) ) {
				$this->status_msg( Status::E20R_ERROR_NO_UPDATE_FROM_LOGIN, $allow_update );
				$success = false;
			}

			if ( isset( $record['user_email'] ) && ! empty( 'user_email' ) && false === $allow_update && false !== get_user_by( 'login', $record['user_email'] ) ) {
				$this->status_msg( Status::E20R_ERROR_NO_UPDATE_FROM_EMAIL, $allow_update );
				$success = false;
			}

			// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the Email address
			if ( empty( $user_data['user_login'] ) && empty( $user_data['user_email'] ) ) {
				$this->status_msg( Status::E20R_ERROR_NO_EMAIL_OR_LOGIN, $allow_update );
				$success = false;
			}

			return $success;
		}

		/**
		 * Find and Validate the supplied user id
		 *
		 * @param bool $success Whether the validation is successful or not
		 * @param int $user_id ID of WP User record
		 * @param array $record The data we're importing and validating
		 * @param null|string|string[] $field_name Name of the field to validate
		 *
		 * @return bool|int
		 * @throws InvalidProperty Thrown if Variables::get() references an invalid property
		 */
		public function validate_user_id( $success, $user_id, $record, $field_name = null, $wp_error = null ) {

			$allow_update = (bool) $this->variables->get( 'update_users' );
			// TODO: Remove duplication of the following code from lines 214-216 in User_Present.php file

			$has_id    = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) && $this->is_valid_integer( $record['ID'] ) );
			$has_email = ( isset( $record['user_email'] ) && ! empty( $record['user_email'] ) );
			$has_login = ( isset( $record['user_login'] ) && ! empty( $record['user_login'] ) );

			if ( false === $has_id && false === $has_login && false === $has_email ) {
				$this->status_msg( Status::E20R_ERROR_NO_USER_ID, $allow_update );
				$success = false;
			}

			if ( false === $has_email && true === $has_login ) {
				$this->status_msg( Status::E20R_ERROR_NO_EMAIL, $allow_update );
				$success = false;
			}

			$found_by_email = ( true === $has_email && get_user_by( 'Email', $record['user_email'] ) );
			$found_by_login = ( true === $has_login && get_user_by( 'login', $record['user_login'] ) );
			$found_by_id    = ( true === $has_id && get_user_by( 'ID', $record['ID'] ) );

			if ( false === $allow_update && ( true === $found_by_email || true === $found_by_id || true === $found_by_login ) ) {
				$this->status_msg( Status::E20R_ERROR_USER_EXISTS_NO_UPDATE, $allow_update );
				$success = false;
			}

			return $success;
		}

		/**
		 * Process the status for user validations and set a status message
		 *
		 * @param int $status Received status code for the validation being executed
		 * @param bool $allow_update Whether we allow updates to the user record or not
		 *
		 * @return void
		 */
		public function status_msg( $status, $allow_update ) {

			global $e20r_import_err;
			global $e20r_import_warn;
			global $active_line_number;

			if ( ! is_array( $e20r_import_err ) ) {
				$e20r_import_err = array();
			}

			if ( ! is_array( $e20r_import_warn ) ) {
				$e20r_import_warn = array();
			}

			switch ( $status ) {
				case Status::E20R_ERROR_NO_EMAIL_OR_LOGIN:
					$msg = sprintf(
					// translators: %1$d: Current line number in the CSV file being imported
						esc_attr__(
							'Neither user_email, nor user_login information provided in import file (from CSV file line: %1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'missing_email_login', $msg );
					$e20r_import_err[ "user_email_missing_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_NO_UPDATE_FROM_EMAIL:
					$msg = sprintf(
					// translators: %1$d: Current line number in the CSV file being imported
						esc_attr__(
							'User exists (user_email), and updates are disallowed (from CSV file line: %1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'email_exists_no_updates', $msg );
					$e20r_import_warn[ "existing_user_email_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_USER_NOT_FOUND:
					$msg = sprintf(
					// translators: %1$d: Current line number in the CSV file being imported
						esc_attr__(
							'WP User ID was not found in database (from CSV file line: %1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'error_no_email', $msg );
					$e20r_import_err[ "user_missing_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_NO_UPDATE_FROM_LOGIN:
					$msg = sprintf(
					// translators: %1$d - Current line number in the CSV file being imported
						esc_attr__(
							'User exists (user_login), and updates are disallowed (from CSV file line: %2$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'login_exists_no_updates', $msg );
					$e20r_import_warn[ "existing_user_login_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_NO_EMAIL:
					$msg = sprintf(
					// translators: %1$d: Active line number in CSV file
						esc_attr__(
							'Error: No Email address supplied for user (line %1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'error_no_email', $msg );
					$e20r_import_err[ "error_no_email_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_NO_USER_ID:
					$msg = sprintf(
					// translators: %1$d: Current line in CSV file being imported
						esc_attr__(
							'Missing ID, user_login and/or user_email information column (line #%1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'error_no_id', $msg );
					$e20r_import_err[ "error_no_id_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_ID_NOT_NUMBER:
					$msg = sprintf(
					// translators: %1$d: Active line number in CSV file
						esc_attr__(
							'The value specified in the \'ID\' column is not numeric (integer) - (line #%1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'error_id_not_numeric', $msg );
					$e20r_import_err[ "error_id_not_numeric_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED:
					$msg = sprintf(
					// translators: %1$d: Active line number in CSV file
						esc_attr__(
							'User ID specified and user record exists but the "Update User Record" option is not selected (line #%1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'warn_user_exists_no_update', $msg );
					$e20r_import_warn[ "warn_user_exists_no_update_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
				case Status::E20R_ERROR_USER_EXISTS_NO_UPDATE:
					$msg = sprintf(
					// translators: %1$d: Active line number in CSV file
						esc_attr__(
							'User exists, but the "Update User Record" option is not selected. (line #%1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'warn_user_exists_no_update', $msg );
					$e20r_import_warn[ "warn_user_exists_no_update_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $msg );
					break;
			}
		}
	}
}
