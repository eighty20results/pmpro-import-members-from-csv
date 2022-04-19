<?php
/**
 * Copyright (c) 2018 - 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

if ( ! class_exists( 'E20R\Import_Members\Modules\Users\Create_Password' ) ) {
	/**
	 * Class Create_Password
	 * @package E20R\Import_Members\Modules\Users
	 */
	class Create_Password {

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

		public function status_msg( $status, $allow_updates ) {

			// TODO: Create status_msg for Create password validation!
			return $status;
		}

		/**
		 * Should we create a new password? (Based on the Import data & settings)
		 *
		 * @param array $record
		 * @param bool|null $update_user - The received setting for 'update_users"
		 * @param null|\WP_User $user - The User record (if we're potentially updating the user)
		 *
		 * @return bool
		 */
		public function validate( $record, $update_user = false, $user = null ) {

			// We set a dummy password when...
			$set_password = false;

			// A password record exists in the Import file, and 'update_user' is true
			if ( isset( $record['user_pass'] ) && true === $update_user ) {
				$set_password = true;
			}

			// A password record exists in the Import file _and_ it's empty and the $user doesn't exist
			if ( isset( $record['user_pass'] ) && empty( $record['user_pass'] ) && empty( $user ) ) {
				$set_password = true;
			}

			// When we're not supposed to update the user and the user doesn't exist
			if ( false === $update_user && empty( $user ) ) {
				$set_password = true;
			}

			// No user_pass record is included in the Import file _and_ the user doesn't exist
			if ( ! isset( $record['user_pass'] ) && empty( $user ) ) {
				$set_password = true;
			}

			return $set_password;
		}
	}
}
