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
 * @package E20R\Import_Members\Modules\User_ID
 */

namespace E20R\Import_Members\Modules\Users;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;

if ( ! class_exists( '\E20R\Import_Members\Modules\Users\User_ID' ) ) {
	/**
	 * Class User_ID
	 */
	class User_ID {

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
		public function __construct( &$variables = null, &$error_log = null ) {
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
		 * Set the status/error message for the User_ID validation logic
		 *
		 * @param int $status
		 * @param bool $allow_updates
		 *
		 * @return string
		 */
		public function status_msg( $status, $allow_updates ) {

			switch ( $status ) {
				case Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED:
					$msg = __(
						'Error: User ID exists but cannot be updated per the plugin settings',
						'pmpro-import-members-from-csv'
					);
					break;

				case Status::E20R_ERROR_ID_NOT_NUMBER:
					$msg = __(
						'Supplied information in ID column is not a number',
						'pmpro-import-members-from-csv'
					);
					break;

				default:
					$msg = null;
			}

			return $msg;
		}

		/**
		 * Validate user ID data (if present)
		 *
		 * @param array $record
		 * @param bool|null $allow_update
		 *
		 * @return bool|int
		 */
		public function validate( $record, $allow_update = null ) {

			$has_id = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) );
			$this->error_log->debug( "The user's ID value is present? " . ( $has_id ? 'Yes' : 'No' ) );

			if ( false === $has_id ) {
				return false;
			}

			if ( false === is_int( $record['ID'] ) ) {
				$this->error_log->debug( "'ID' column isn't a number" );
				return Status::E20R_ERROR_ID_NOT_NUMBER;
			}

			if ( false !== get_user_by( 'ID', $record['ID'] ) && false === $allow_update ) {
				$this->error_log->debug( "'ID' column is for a current user _AND_ we're not allowing updates" );
				return Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED;
			}

			$this->error_log->debug( 'User ID is present and a number...' );
			return true;
		}
	}
}
