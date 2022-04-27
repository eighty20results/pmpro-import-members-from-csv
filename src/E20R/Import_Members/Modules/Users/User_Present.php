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
 * @package E20R\Import_Members\Modules\User_Present
 */

namespace E20R\Import_Members\Modules\Users;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;
use WP_Error;
use WP_User;

if ( ! class_exists( '\E20R\Import_Members\Modules\Users\User_Present' ) ) {
	/**
	 * Class User_ID
	 */
	class User_Present {

		/**
		 * Instance of the Error_Log() class
		 *
		 * @var Error_Log|null
		 */
		protected $error_log = null;

		/**
		 * Instance of the Variables() class
		 *
		 * @var Variables|null
		 */
		private $variables = null;

		/**
		 * WP Error object
		 *
		 * @var mixed|WP_Error|null
		 */
		private $wp_error = null;

		/**
		 * Constructor for the User_Present() class
		 *
		 * @param Variables|null $variables
		 * @param Error_Log|null $error_log
		 */
		public function __construct( $variables = null, $error_log = null, $wp_error = null ) {
			if ( null === $wp_error ) {
				$wp_error = new WP_Error();
			}
			$this->wp_error = $wp_error;

			if ( null === $error_log ) {
				$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			$this->error_log = $error_log;

			if ( null === $variables ) {
				$variables = new Variables( $this->error_log );
			}
			$this->variables = $variables;
		}
		/**
		 * Set the status/error message for the User_Presence validation logic
		 *
		 * @param int $status The status code to generate a messaage for
		 * @param bool $allow_updates Whether we allow user updates or not
		 */
		public function status_msg( $status, $allow_updates ) {

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
				case Status::E20R_USER_IDENTIFIER_MISSING:
					$msg = sprintf(
						// translators: %1$d: The line number in the CSV import file
						esc_attr__(
							'Error: Neither the ID, user_login nor user_email field exists in import record from line %1$d!',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_ident', $msg );
					$e20r_import_err[ "no_identifying_info_{$active_line_number}" ] = $new_error;
					break;

				case Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED:
					$msg = sprintf(
					// translators: %1$d: The line number from the CSV import file
						esc_attr__(
							'Warning: User at line %1$d exists but cannot be updated per our settings',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);

					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_noupd', $msg );
					$e20r_import_warn[ "cannot_update_{$active_line_number}" ] = $new_error;
					break;

				case Status::E20R_ERROR_ID_NOT_NUMBER:
					$msg = sprintf(
						// translators: %1$d: The line number from the CSV import file
						esc_attr__(
							'Supplied information in ID column on line %1$d is not a number',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);

					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_id', $msg );
					$e20r_import_err[ "error_invalid_user_id_{$active_line_number}" ] = $new_error;
					break;

				case Status::E20R_ERROR_NO_EMAIL:
					$msg = sprintf(
						// translators: %1$d: The line number from the CSV import file
						esc_attr__( 'Invalid email in row %1$d (Not imported).', 'pmpro-import-members-from-csv' ),
						$active_line_number
					);

					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_email', $msg, $user_data['user_email'] ?? null );
					$e20r_import_warn[ "warn_invalid_email_{$active_line_number}" ] = $new_error;
					break;

				case Status::E20R_ERROR_NO_EMAIL_OR_LOGIN:
					$msg = sprintf(
						// translators: %1$s column name, %2$s: row number
						esc_attr__(
							'Neither "user_email" nor "user_login" column found, or the "user_email" and "user_login" column(s) was/were included, the user exists, and the "Update user record" option was NOT selected (row: %1$d). Will not import/update user.',
							'pmpro-import-members-from-csv'
						),
						$active_line_number++
					);

					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_email_login', $msg );
					$e20r_import_warn[ "warn_invalid_email_login_{$active_line_number}" ] = $new_error;
					break;
			}
		}

		/**
		 * Validate user presence on system
		 *
		 * @param array $record
		 * @param bool|null $allow_update
		 *
		 * @return bool|int
		 */
		public function validate( $record, $allow_update = null ) {

			$has_id    = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) );
			$has_email = ( isset( $record['user_email'] ) && ! empty( $record['user_email'] ) );
			$has_login = ( isset( $record['user_login'] ) && ! empty( $record['user_login'] ) );
			$this->error_log->debug( "The user's ID value is present? " . ( $has_id ? 'Yes' : 'No' ) );
			$this->error_log->debug( "The user's email value is present? " . ( $has_email ? 'Yes' : 'No' ) );
			$this->error_log->debug( "The user's login value is present? " . ( $has_login ? 'Yes' : 'No' ) );

			// None of the user identifiers (username or user ID) are set in import data so can't determine that user is persent
			if ( false === $has_id && false === $has_login && false === $has_email ) {
				$this->error_log->debug( 'Neither of the user identification keys exist in import data' );
				return Status::E20R_USER_IDENTIFIER_MISSING;
			}

			// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the Email address
			if ( ! $has_login && ! $has_email ) {
				$this->error_log->debug( 'Need either user_login or user_email to be present!' );
				return Status::E20R_ERROR_NO_EMAIL_OR_LOGIN;
			}

			// Value in the ID column of the import file, but it's not a number (that's so many levels of wrong!)
			if ( true === $has_id && false === is_int( $record['ID'] ) ) {
				$this->error_log->debug( "'ID' column isn't a number" );
				return Status::E20R_ERROR_ID_NOT_NUMBER;
			}

			// Is the user_email supplied and is it a valid email address
			if ( true === $has_email && false === is_email( $record['user_email'] ) ) {
				$this->error_log->debug( "'user_email' column doesn't contain a valid email address" );
				return Status::E20R_ERROR_NO_EMAIL;
			}

			// Figure out if we allow updates since we didn't receive the value from the calling method
			if ( null === $allow_update ) {
				try {
					$allow_update = (bool) $this->variables->get( 'update_users' );
				} catch ( InvalidSettingsKey $e ) {
					$this->error_log->add_error_msg(
						sprintf(
						// translators: %1$s: Exception message
							esc_attr__( 'Unexpected error: %1$s', 'pmpro-import-members-from-csv' ),
							$e->getMessage()
						)
					);
				}
			}

			// Check if user exists on the system based on the supplied WP_User->ID
			$user = $has_id ? get_user_by( 'ID', $record['ID'] ) : false;
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			if ( true === ( $id_status = $this->user_data_can_be_imported( $has_id, $user, $allow_update ) ) ) {
				$this->error_log->debug( 'User found using the ID value' );
				return $id_status;
			}
			$this->status_msg( $id_status, $allow_update );

			// Check if the user exists based on the user_login info
			$user = $has_login ? get_user_by( 'login', $record['user_login'] ) : false;
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			if ( true === ( $login_status = $this->user_data_can_be_imported( $has_login, $user, $allow_update ) ) ) {
				$this->error_log->debug( 'User found using the user_login value' );
				return $login_status;
			}

			$this->status_msg( $login_status, $allow_update );

			// Check if the user exists on the system based on the supplied user_email data
			$user = $has_email ? get_user_by( 'email', $record['user_email'] ) : false;
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			if ( true === ( $email_status = $this->user_data_can_be_imported( $has_email, $user, $allow_update ) ) ) {
				$this->error_log->debug( 'User found using the user_email value' );
				return $email_status;
			}
			$this->status_msg( $email_status, $allow_update );

			return false;
		}

		/**
		 * Test if the import record can be applied (used to add/update the user)
		 *
		 * @param bool          $field_exists The presence of the specified import column (ID, user_login, user_email) in the import data
		 * @param false|WP_User $user The WP_User record for the import data (if it exists)
		 * @param bool          $allow_update Whether the admin set the 'allow updates' flag to true or not
		 *
		 * @return true|int
		 */
		private function user_data_can_be_imported( $field_exists, $user, $allow_update ) {

			// The user identifying field is not present in import data
			if ( false === $field_exists ) {
				$this->error_log->debug( 'Specified column does not exist in the import record' );
				return Status::E20R_USER_IDENTIFIER_MISSING;
			}

			// User doesn't exist
			if ( false === $user ) {
				$this->error_log->debug( 'User does not exist on this system' );
				return Status::E20R_ERROR_USER_NOT_FOUND;
			}

			// User exists on the system, but can't be updated by import data
			if ( false === $allow_update ) {
				$this->error_log->debug( "User exists _BUT_ we're not allowing updates" );
				return Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED;
			}

			return true;
		}
	}
}
