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

class User_Update {

	/**
	 * User_Update::status_msg() is a stub function
	 *
	 * @param string $status
	 * @param bool $allow_updates
	 */
	public static function status_msg( $status, $allow_updates ) {
		// Not doing anything - stub for compatibility
	}

	/**
	 * @param array $record
	 * @param bool $allow_update
	 *
	 * @return bool|string
	 */
	public static function validate( $record, $allow_update ) {

		global $active_line_number;

		$variables = new Variables();
		$update    = (bool) $variables->get( 'update_users' );

		if (
			isset( $record['user_login'] ) &&
			! empty( 'user_login' ) &&
			false === $update &&
			false !== get_user_by( 'login', $record['user_login'] )
		) {
			return 'user_login';
		}

		if (
			isset( $record['user_email'] ) &&
			! empty( 'user_email' ) &&
			false === $update &&
			false !== get_user_by( 'login', $record['user_login'] )
		) {
			return 'user_email';
		}

		// BUG FIX: Not loading/updating record if user exists and the user identifiable data is the email address
		if ( ( empty( $user_data['user_login'] ) && empty( $user_data['user_email'] ) ) ) {

			return 'user_login and user_email';
		}

		return true;
	}
}
