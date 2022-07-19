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

use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Variables;
use E20R\Utilities\Utilities;
use WP_Error;

if ( ! class_exists( '\E20R\Import_Members\Modules\Users\User_Present' ) ) {
	/**
	 * Class User_ID
	 */
	class User_Present extends Base_Validation {

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
		 *
		 * @throws InvalidInstantiation Thrown if the supplied class isn't what we expect
		 */
		public function __construct( $variables = null, $error_log = null, $wp_error = null ) {
			parent::__construct( $variables, $error_log );

			if ( null === $wp_error ) {
				$wp_error = new WP_Error();
			}

			if ( ! is_a( $wp_error, WP_Error::class ) || ! is_object( $wp_error ) ) {
				throw new InvalidInstantiation(
					sprintf(
						// translators: %1$s: Supplied class base name, %2$s expected class base name
						esc_attr__( '"%1$s" is an unexpected class. Expecting "%2$s"', 'pmpro-import-members-from-csv' ),
						gettype( $wp_error ),
						class_basename( WP_Error::class )
					)
				);
			}
			$this->wp_error = $wp_error;
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
				case Status::E20R_ERROR_USER_NOT_FOUND:
					$msg = sprintf(
					// translators: %1$d: The line number in the CSV import file
						esc_attr__(
							"Error: Expected to find user from information in record, but didn't succeed! (line: %1\$d)",
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_ident', $msg );
					$e20r_import_err[ "user_not_found_{$active_line_number}" ] = $new_error;
					break;
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
							'The import data specifies an existing user but the plugin settings disallow updating their record (line: %1$d)',
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
		 * @param array $record User data being imported from CSV file
		 * @param bool|null $allow_update Whether the admin is allowing updates to already present user-data
		 *
		 * @return bool|int
		 */
		public function validate( $record, $allow_update = null ) {

			$status = true;
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

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$ID    = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) && Utilities::is_integer( $record['ID'] ) );
			$email = ( isset( $record['user_email'] ) && ! empty( $record['user_email'] ) );
			$login = ( isset( $record['user_login'] ) && ! empty( $record['user_login'] ) );

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$this->error_log->debug( "The user's ID value is present? " . ( $ID ? 'Yes' : 'No' ) );
			$this->error_log->debug( "The user's email value is present? " . ( $email ? 'Yes' : 'No' ) );
			$this->error_log->debug( "The user's login value is present? " . ( $login ? 'Yes' : 'No' ) );

			// None of the user identifiers (username or user ID) are set in import data so can't determine that user is persent
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			if ( false === $ID && false === $login && false === $email ) {
				$this->status_msg( Status::E20R_USER_IDENTIFIER_MISSING, $allow_update );
				$status = false;
			}

			// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the Email address
			if ( ! $login && ! $email ) {
				$this->status_msg( Status::E20R_ERROR_NO_EMAIL_OR_LOGIN, $allow_update );
				$status = false;
			}

			// Value in the ID column of the import file, but it's not a number (that's so many levels of wrong!)
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			if ( true === $ID && false === Utilities::is_integer( $record['ID'] ) ) {
				$this->status_msg( Status::E20R_ERROR_ID_NOT_NUMBER, $allow_update );
			}

			// Is the user_email supplied and is it a valid email address
			if ( true === $email && false === is_email( $record['user_email'] ) ) {
				$this->status_msg( Status::E20R_ERROR_NO_EMAIL, $allow_update );
				$status = false;
			}

			$id_fields = array(
				'ID'         => 'ID',
				'user_login' => 'login',
				'user_email' => 'email',
			);

			if ( true === $email && true === $login ) {
				$exists = $this->db_user_exists( array( 'user_email', 'user_login' ), array( $record['user_email'], $record['user_login'] ) );
				$status = $this->data_can_be_imported( true, $exists, $allow_update );
				$this->status_msg( $status, $allow_update );

				if ( true === $status || Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED === $status ) {
					$this->error_log->debug( 'User found using the email and login values' );
					return $status;
				}
			}

			foreach ( $id_fields as $field => $type ) {
				// Using the name of the field in a dynamic variable,
				// which was set to true/false on lines 215-217 of this file - ${$type} <=> {$ID}|{$email}|{$login}
				$has_column = ${$type};
				$exists     = $this->db_user_exists( $field, $record[ $field ] );
				$status     = $this->data_can_be_imported( $has_column, $exists, $allow_update );
				$this->status_msg( $status, $allow_update );

				if ( true === $status || Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED === $status ) {
					$this->error_log->debug( "User found using the {$field} value" );
					break;
				}
			}

			return $status;
		}

		/**
		 * Test if the import record can be applied (used to add/update the user)
		 *
		 * @param bool $has_column The presence of the specified import column with data (ID, user_login, user_email) in the CSV data
		 * @param bool $user_exists The WP_User record for the import data (if it exists)
		 * @param bool $allow_update Whether the admin set the 'allow updates' flag to true or not
		 *
		 * @return true|int
		 */
		private function data_can_be_imported( $has_column, $user_exists, $allow_update ) {

			// The user identifying field is not present in import data
			if ( false === $has_column ) {
				return Status::E20R_USER_IDENTIFIER_MISSING;
			}

			// User doesn't exist
			if ( false === $user_exists ) {
				return Status::E20R_ERROR_USER_NOT_FOUND;
			}

			// User exists on the system, but can't be updated by import data
			if ( false === $allow_update ) {
				return Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED;
			}

			return true;
		}

		/**
		 * Find the user by searching the database (wp_users table)
		 *
		 * @param string|string[] $column The wp_users table column(s) to search
		 * @param string|array[]  $value The value(s) we're looking for in that column
		 *
		 * @return bool
		 * @access private
		 */
		private function db_user_exists( $column, $value ) {
			global $wpdb;

			$where_clause = 'WHERE ';

			if ( is_array( $column ) ) {
				foreach ( $column as $id => $name ) {
					if ( 'WHERE ' !== $where_clause ) {
						$where_clause .= ' AND ';
					}
					$where_clause .= sprintf( '%1$s = \'%2$s\'', esc_sql( $name ), esc_sql( $value[ $id ] ) );
				}
			} else {
				$where_clause .= sprintf( '%1$s = \'%2$s\'', esc_sql( $column ), esc_sql( $value ) );
			}

			$sql   = sprintf(
				'SELECT COUNT(*) FROM %1$s %2$s',
				$wpdb->users,
				$where_clause
			);
			$count = (int) $wpdb->get_var( $sql ); // // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return ( 1 <= $count );
		}

		/**
		 * Load presence test actions we use to trigger this class (N/A in this case)
		 *
		 * @return void
		 */
		public function load_actions() {
		}

		/**
		 * Returns the list of error categories this validation class will be willing to ignore
		 *
		 * @param array $ignored_error_list
		 * @param string $module_name
		 *
		 * @return array
		 */
		public function load_ignored_module_errors( $ignored_error_list, $module_name = 'user_present' ) {
			return $ignored_error_list;
		}
	}
}
