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

use E20R\Import_Members\Variables;

class Create_Password {

	public static function status_msg( $status, $allow_updates ) {

		// TODO: Create status_msg for Create password validation!
		return $status;
	}

	/**
	 * Should we create a new password? (Based on the import data & settings)
	 *
	 * @param array $record
	 * @param bool|null $update_user - The received setting for 'update_users"
	 * @param null|\WP_User $user - The User record (if we're potentially updating the user)
	 *
	 * @return bool
	 */
	public static function validate( $record, $update_user = false, $user = null ) {

		// We set a dummy password when...
		$set_password = false;

		// A password record exists in the import file, and 'update_user' is true
		if ( isset( $record['user_pass'] ) && true === $update_user ) {
			$set_password = true;
		}

		// A password record exists in the import file _and_ it's empty and the $user doesn't exist
		if ( isset( $record['user_pass'] ) && empty( $record['user_pass'] ) && empty( $user ) ) {
			$set_password = true;
		}

		// When we're not supposed to update the user and the user doesn't exist
		if ( false === $update_user && empty( $user ) ) {
			$set_password = true;
		}

		// No user_pass record is included in the import file _and_ the user doesn't exist
		if ( ! isset( $record['user_pass'] ) && empty( $user ) ) {
			$set_password = true;
		}

		return $set_password;
	}
}
