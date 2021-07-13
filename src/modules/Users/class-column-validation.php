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

namespace E20R\Import_Members\Modules\Users;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Status;

class Column_Validation extends Base_Validation {

	/**
	 * Instance of the Variables() class
	 * @var null|Variables $variables
	 */
	private $variables = null;

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

			// Add list of errors to ignore for the BuddyPress module
			self::$instance->errors_to_ignore = apply_filters(
				'e20r_import_errors_to_ignore',
				self::$instance->errors_to_ignore,
				'users'
			);

			self::$instance->error_log = new Error_Log(); // phpcs:ignore
			self::$instance->variables = new Variables();

		}

		return self::$instance;
	}

	/**
	 * Load action and filter handlers for PMPro validation
	 */
	public function load_actions() {

		add_filter(
			'e20r_import_users_validate_field_data',
			array( $this, 'validate_user_id' ),
			1,
			3
		);
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
	 * @param array $record
	 *
	 * @return bool|string
	 */
	public function validate_email( $record ) {

		global $active_line_number;

		$update = (bool) $this->variables->get( 'update_users' );

		if ( isset( $record['user_login'] ) && ! empty( 'user_login' ) && false === $update && false !== get_user_by( 'login', $record['user_login'] ) ) {
			return 'user_login';
		}

		if ( isset( $record['user_email'] ) && ! empty( 'user_email' ) && false === $update && false !== get_user_by( 'login', $record['user_login'] ) ) {
			return 'user_email';
		}

		// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the email address
		if ( empty( $user_data['user_login'] ) && empty( $user_data['user_email'] ) ) {
			return 'user_email and user_login';
		}

		return true;
	}

	/**
	 * Find and validate the supplied user id
	 *
	 * @param bool                 $has_error
	 * @param int                  $user_id
	 * @param array                $record
	 * @param null|string|string[] $field_name
	 *
	 * @return bool|int
	 */
	public function validate_user_id( $has_error, $user_id, $record, $field_name = null ) {

		global $e20r_import_err;

		if ( empty( $field_name ) ) {
			return $has_error;
		}

		if ( ! ( isset( $record['ID'] ) || isset( $record['user_email'] ) || isset( $record['user_login'] ) ) ) {
			$this->error_log->debug( 'Cannot find one of the expected column(s): ID, user_email, user_login' );
			return $has_error;
		}

		$allow_update = (bool) $this->variables->get( 'update_users' );
		$has_id       = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) && is_int( $record['ID'] ) );
		$has_email    = ( isset( $record['user_email'] ) && ! empty( $record['user_email'] ) );
		$has_login    = ( isset( $record['user_login'] ) && ! empty( $record['user_login'] ) );

		$this->error_log->debug( 'ID column found (and contains an integer)? ' . ( $has_id ? 'Yes' : 'No' ) );
		$this->error_log->debug( 'user_email column found? ' . ( $has_email ? 'Yes' : 'No' ) );
		$this->error_log->debug( 'user_login column found? ' . ( $has_login ? 'Yes' : 'No' ) );

		$this->error_log->debug( 'Record being processed: ' . print_r( $record, true ) ); // phpcs:ignore

		if ( false === $has_id && false === $has_login && false === $has_email ) {
			$e20r_import_err['error_no_id'] = __( 'Error: No user ID has been supplied', 'pmpro-import-members-from-csv' );
			return Status::E20R_ERROR_NO_USER_ID;
		}

		if ( false === $has_email && true === $has_login ) {
			$e20r_import_err['error_no_email'] = sprintf(
				// translators: %d - User ID
				__( 'Error: No email address supplied for user record # %d', 'pmpro-import-members-from-csv' ),
				$user_id
			);
			return Status::E20R_ERROR_NO_EMAIL;
		}

		$found_by_email = ( true === $has_email && get_user_by( 'email', $record['user_email'] ) );
		$found_by_login = ( true === $has_login && get_user_by( 'login', $record['user_login'] ) );
		$found_by_id    = ( true === $has_id && get_user_by( 'ID', $record['ID'] ) );

		if ( false === $allow_update && ( true === $found_by_email || true === $found_by_id || true === $found_by_login ) ) {
			$this->error_log->debug( 'User exists, but not allowing updates!' );

			return Status::E20R_ERROR_USER_EXISTS_NO_UPDATE;
		}

		return true;
	}
}
