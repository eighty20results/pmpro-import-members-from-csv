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

namespace E20R\Paid_Memberships_Pro\Import_Members\Validate;


use E20R\Paid_Memberships_Pro\Import_Members\Variables;

class Create_Password extends Validate {
	
	public static function status_msg( $status, $allow_updates ) {
		
		// TODO: Create status_msg for Create password validation!
		return $status;
	}
	
	/**
	 * Should we create a new password? (Based on the import data & settings)
	 *
	 * @param array $record
	 * @param bool $allow_update
	 *
	 * @return bool
	 */
	public static function validate( $record, $allow_update = false ) {
		
		$variables = Variables::get_instance();
		$update    = (bool) $variables->get( 'update_user' );
		
		return ( false === $update && (
				! isset( $record['user_pass'] ) || ( isset( $record['user_pass'] ) && empty( $record['user_pass'] ) )
			)
		);
	}
}