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
 * @package E20R\Import_Members\Modules\User_Update
 */

namespace E20R\Import_Members\Modules\Users;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;

if ( ! class_exists( '\E20R\Import_Members\Modules\User_Update' ) ) {
	/**
	 * Class User_Update
	 */
	class User_Update {

		/**
		 * Instance of the Error_Log() class
		 *
		 * @var Error_Log|null
		 */
		private $error_log = null;

		/**
		 * Instance of the Variables() class
		 *
		 * @var Variables|null
		 */
		private $variables = null;

		/**
		 * Constructor for the User_Update() class
		 *
		 * @param Variables|null $variables
		 * @param Error_Log|null $error_log
		 */
		public function __construct( $variables = null, $error_log = null ) {
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
		 * User_Update::status_msg() is a stub function
		 *
		 * @param string $status
		 * @param bool $allow_updates
		 */
		public function status_msg( $status, $allow_updates ) {
			// Not doing anything - stub for compatibility
		}

		/**
		 * Validate the user information
		 *
		 * @param array $record The record being updated/imported
		 * @param bool|null $allow_update Whether the user/member info can be updated or not
		 *
		 * @return bool|int
		 * @throws InvalidSettingsKey Thrown if the update_users parameter doesn't exist in the Variables() class
		 */
		public function validate( $record, $allow_update = null ) {

			global $active_line_number;

			if ( null === $allow_update ) {
				$allow_update = (bool) $this->variables->get( 'update_users' );
			}

			if (
				isset( $record['user_login'] ) &&
				! empty( 'user_login' ) &&
				false === $allow_update &&
				false !== get_user_by( 'login', $record['user_login'] )
			) {
				return Status::E20R_ERROR_NO_UPDATE_FROM_LOGIN;
			}

			if (
				isset( $record['user_email'] ) &&
				! empty( 'user_email' ) &&
				false === $allow_update &&
				false !== get_user_by( 'email', $record['user_email'] )
			) {
				return Status::E20R_ERROR_NO_UPDATE_FROM_EMAIL;
			}

			// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the Email address
			if ( ( empty( $user_data['user_login'] ) && empty( $user_data['user_email'] ) ) ) {
				return Status::E20R_ERROR_NO_EMAIL_OR_LOGIN;
			}

			return true;
		}
	}
}
