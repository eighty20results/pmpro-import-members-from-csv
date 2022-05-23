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

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Import;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Status;
use E20R\Utilities\Utilities;

if ( ! class_exists( 'E20R\Import_Members\Validate\Column_Values\Users_Validation' ) ) {
	/**
	 * Used to validate the column data for WordPress users being imported/updated
	 */
	class Users_Validation extends Base_Validation {

		/**
		 * Instance of the Variables() class
		 *
		 * @var null|Variables $variables
		 */
		private $variables = null;

		/**
		 * Constructor for the Users_Validation class
		 *
		 * @param Import|null $import Instance of the Import() class
		 *
		 * @throws InvalidSettingsKey Thrown when the Import::get() operation uses the wrong property
		 */
		public function __construct( $import ) {
			parent::__construct( $import );

			$this->variables = $this->import->get( 'variables' );

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
		 * @param array $record The supplied user array (record) from the .CSV file row
		 *
		 * @return bool|int
		 *
		 * @throws InvalidSettingsKey Raised if the 'update_users' key is not a valid setting/variable
		 */
		public function validate_email( $record ) {

			global $active_line_number;

			$update = (bool) $this->variables->get( 'update_users' );

			if ( isset( $record['user_login'] ) && ! empty( 'user_login' ) && false === $update && false !== get_user_by( 'login', $record['user_login'] ) ) {
				return Status::E20R_ERROR_NO_UPDATE_FROM_LOGIN;
			}

			if ( isset( $record['user_email'] ) && ! empty( 'user_email' ) && false === $update && false !== get_user_by( 'login', $record['user_email'] ) ) {
				return Status::E20R_ERROR_NO_UPDATE_FROM_EMAIL;
			}

			// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the Email address
			if ( empty( $user_data['user_login'] ) && empty( $user_data['user_email'] ) ) {
				return Status::E20R_ERROR_NO_EMAIL_OR_LOGIN;
			}

			return true;
		}

		/**
		 * Find and Validate the supplied user id
		 *
		 * @param bool $has_error
		 * @param int $user_id
		 * @param array $record
		 * @param null|string|string[] $field_name
		 *
		 * @return bool|int
		 * @throws InvalidSettingsKey Thrown if Variables::get() references an invalid property
		 */
		public function validate_user_id( $has_error, $user_id, $record, $field_name = null, $wp_error = null ) {

			global $e20r_import_err;
			global $active_line_number;

			if ( null === $wp_error ) {
				$wp_error = new \WP_Error();
			}

			if ( empty( $field_name ) ) {
				return $has_error;
			}

			if ( ! ( isset( $record['ID'] ) || isset( $record['user_email'] ) || isset( $record['user_login'] ) ) ) {
				$this->error_log->debug( 'Cannot find one of the expected column(s): ID, user_email, user_login' );

				return $has_error;
			}

			$allow_update = (bool) $this->variables->get( 'update_users' );
			// TODO: Remove duplication of the following code from lines 214-216 in User_Present.php file
			$has_id       = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) && Utilities::is_integer( $record['ID'] ) );
			$has_email    = ( isset( $record['user_email'] ) && ! empty( $record['user_email'] ) );
			$has_login    = ( isset( $record['user_login'] ) && ! empty( $record['user_login'] ) );

			if ( false === $has_id && false === $has_login && false === $has_email ) {
				$msg = sprintf(
					// translators: %1$d: Current line in CSV file being imported
					esc_attr__( 'Error: No way to identify the user has been supplied for record (line #%1$d)', 'pmpro-import-members-from-csv' ),
					$active_line_number
				);
				$new_error = $wp_error;
				$new_error->add( 'error_no_id', $msg );
				$e20r_import_err[ "error_no_id_{$active_line_number}" ] = $new_error;
				$this->error_log->debug( $msg );

				return Status::E20R_ERROR_NO_USER_ID;
			}

			if ( false === $has_email && true === $has_login ) {
				$msg = sprintf(
				// translators: %1$d: User ID, %2$d: Active line number in CSV file
					esc_attr__( 'Error: No Email address supplied for user %1$d (line %2$d)', 'pmpro-import-members-from-csv' ),
					$user_id,
					$active_line_number
				);
				$new_error = $wp_error;
				$new_error->add( 'error_no_email', $msg );
				$e20r_import_err[ "error_no_email_{$active_line_number}" ] = $new_error;
				$this->error_log->debug( $msg );

				return Status::E20R_ERROR_NO_EMAIL;
			}

			$found_by_email = ( true === $has_email && get_user_by( 'Email', $record['user_email'] ) );
			$found_by_login = ( true === $has_login && get_user_by( 'login', $record['user_login'] ) );
			$found_by_id    = ( true === $has_id && get_user_by( 'ID', $record['ID'] ) );

			if ( false === $allow_update && ( true === $found_by_email || true === $found_by_id || true === $found_by_login ) ) {
				$this->error_log->debug( 'User exists, but not allowing updates!' );
				return Status::E20R_ERROR_USER_EXISTS_NO_UPDATE;
			}

			return true;
		}

		/**
		 * Process the status for user validations and set a status message
		 *
		 * @param int $status
		 * @param bool $allow_update
		 *
		 * @return bool
		 */
		public static function status_msg( $status, $allow_update ) {

			global $e20r_import_err;
			global $active_line_number;

			$should_exit = false;

			switch ( $status ) {
				case Status::E20R_ERROR_ID_NOT_NUMBER:
					$msg = __(
						"The value specified in the 'ID' column is not numeric (integer)",
						'pmpro-import-members-from-csv'
					);
					break;

				case Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED:
					$msg         = __(
						'User ID specified and user record exists but the "Update User Record" option is not selected',
						'pmpro-import-members-from-csv'
					);
					$should_exit = true;
					break;

				case Status::E20R_ERROR_USER_EXISTS_NO_UPDATE:
					$msg = __(
						'User exists, but the "Update User Record" option is not selected.',
						'pmpro-import-members-from-csv'
					);

					$should_exit = true;
					break;
				default:
					$msg         = null;
					$should_exit = false;
			}

			// Process the resulting error/warning message
			if ( ! empty( $msg ) ) {

				// Save the error message (based on the supplied status)
				$e20r_import_err[ "user_check_{$active_line_number}" ] = $msg;

				return $should_exit;
			}

			return false;
		}
	}
}
