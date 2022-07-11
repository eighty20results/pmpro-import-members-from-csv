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
 * @package E20R\Import_Members\Modules\Users
 */

namespace E20R\Import_Members\Modules\Users;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Variables;
use WP_Error;

if ( ! class_exists( 'E20R\Import_Members\Modules\Users\Generate_Password' ) ) {
	/**
	 * Class Generate_Password
	 * @package E20R\Import_Members\Modules\Users
	 */
	class Generate_Password extends Base_Validation {

		/**
		 * Instance of the WP_Error() class
		 *
		 * @var WP_Error|null
		 */
		private $wp_error = null;

		/**
		 * Constructor for the User_Update() class
		 *
		 * @param Variables|null $variables The Variable() class instance we're using
		 * @param Error_Log|null $error_log The Error_Log() class instance we're using
		 * @param WP_Error|null $wp_error Mockable error object
		 *
		 * @throws \E20R\Exceptions\InvalidInstantiation Thrown if we mess up/ignore including the required Variables() and Error_Log() classes
		 *
		 */
		public function __construct( $variables = null, $error_log = null, $wp_error = null ) {
			parent::__construct( $variables, $error_log );

			if ( null === $variables ) {
				$variables = new Variables( $this->error_log );
			}
			$this->variables = $variables;

			if ( null === $wp_error ) {
				$wp_error = new WP_Error();
			}
			$this->wp_error = $wp_error;
		}

		/**
		 * Set a status message in error/warning log and return the status
		 *
		 * @param bool $status Returned value from the __CLASS__::validate() method
		 * @param bool $allow_updates Whether the record can be updated or not
		 *
		 * @return void
		 */
		public function status_msg( $status, $allow_updates ) {
			global $e20r_import_warn;
			global $active_line_number;

			if ( ! is_array( $e20r_import_warn ) ) {
				$e20r_import_warn = array();
			}

			switch ( $status ) {
				case Status::E20R_USER_EXISTS_NEW_PASSWORD:
					$msg = sprintf(
					// translators: %1$d: The line number in the CSV import file
						esc_attr__(
							'Warning: Changing password for an existing user! (line: %1$d)',
							'pmpro-import-members-from-csv'
						),
						$active_line_number
					);
					$new_error = $this->wp_error;
					$new_error->add( 'e20r_im_ident', $msg );
					$e20r_import_warn[ "overwriting_password_{$active_line_number}" ] = $new_error;
					break;
			}
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

			// We set an auto-generated password when...

			// No user_pass record is included in the CSV import file _and_ the user doesn't exist
			if ( ! isset( $record['user_pass'] ) && empty( $user ) ) {
				return true;
			}

			// A password record exists in the CSV file _and_ it's empty and the $user doesn't exist
			if ( isset( $record['user_pass'] ) && empty( $record['user_pass'] ) && empty( $user ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Load actions triggering functionality in this class (none)
		 *
		 * @return void
		 */
		public function load_actions() {}

		/**
		 * List of modules we can ignore in this validator
		 *
		 * @param array $ignored_error_list
		 * @param string $module_name
		 *
		 * @return array
		 */
		public function load_ignored_module_errors( $ignored_error_list, $module_name = 'base' ) {
			return $ignored_error_list;
		}
	}
}
