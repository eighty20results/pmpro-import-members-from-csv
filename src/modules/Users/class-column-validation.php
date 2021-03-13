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
use E20R\Import_Members\Variables;
use E20R\Import_Members\Status;

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

		add_filter(
			'e20r_import_users_validate_field_data',
			array( $this, 'validate_user_id' ),
			1,
			4
		);
	}

	/**
	 * @param array $record
	 *
	 * @return bool|string
	 */
	public function validate_email( $record ) {

		global $active_line_number;

		$variables = Variables::get_instance();
		$update    = (bool) $variables->get( 'update_users' );

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
	 * @param bool                 $has_error
	 * @param int                  $user_id
	 * @param array                $record
	 * @param null|string|string[] $field_name
	 *
	 * @return bool|int|false
	 */
	public function validate_user_id( $has_error, $user_id, $record, $field_name = null ) {

		$error_log = Error_Log::get_instance();
		$variables = Variables::get_instance();

		if ( empty( $field_name ) ) {
			return $has_error;
		}

		if ( is_array( $field_name ) ) {
			// TODO: Process list of fields
		}

		$allow_update = (bool) $variables->get( 'update_users' );
		$has_id       = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) && is_int( $record['ID'] ) );
		$has_email    = ( isset( $record['user_email'] ) && ! empty( $record['user_email'] ) );
		$has_login    = ( isset( $record['user_login'] ) && ! empty( $record['user_login'] ) );

		$error_log->debug( 'ID column found (and contains an integer)? ' . ( $has_id ? 'Yes' : 'No' ) );
		$error_log->debug( 'user_email column found? ' . ( $has_email ? 'Yes' : 'No' ) );
		$error_log->debug( 'user_login column found? ' . ( $has_login ? 'Yes' : 'No' ) );

		$error_log->debug( 'Record being processed: ' . print_r( $record, true ) );

		if ( false === $has_id && false === $has_login && false === $has_email ) {
			return Status::E20R_ERROR_NO_USER_ID;
		}

		if ( false === $has_email && true === $has_login ) {
			return Status::E20R_ERROR_NO_EMAIL;
		}

		$found_by_email = ( true === $has_email && get_user_by( 'email', $record['user_email'] ) );
		$found_by_login = ( true === $has_login && get_user_by( 'login', $record['user_login'] ) );
		$found_by_id    = ( true === $has_id && get_user_by( 'ID', $record['ID'] ) );

		if ( false === $allow_update && ( true === $found_by_email || true === $found_by_id || true === $found_by_login ) ) {
			$error_log->debug( 'User exists, but not allowing updates!' );

			return Status::E20R_ERROR_USER_EXISTS_NO_UPDATE;
		}

		return true;
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