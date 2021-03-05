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

namespace E20R\Import_Members\Validate;


use E20R\Import_Members\Import_Members;
use E20R\Import_Members\Status;

class User_Validation extends Validate {
	
	
	/**
	 * Process the status for user validations and set a status message
	 *
	 * @param int  $status
	 * @param bool $allow_update
	 *
	 * @return bool
	 */
	public static function status_msg( $status, $allow_update ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		$should_exit = false;
		
		switch ( $status ) {
			
			case Status::E20R_ERROR_ID_NOT_NUMBER:
				
				$msg = __( "The value specified in the 'ID' column is not numeric (integer)", Import_Members::PLUGIN_SLUG );
				break;
			
			case Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED:
				
				$msg = __(
					'User ID specified and user record exists but the "Update User Record" option is not selected',
					Import_Members::PLUGIN_SLUG
				);
				$should_exit = true;
				break;
			
			case Status::E20R_ERROR_USER_EXISTS_NO_UPDATE:
				
				$msg = __(
					'User exists, but the "Update User Record" option is not selected.',
					Import_Members::PLUGIN_SLUG
				);
				
				$should_exit = true;
				break;
			default:
				
				$msg = null;
				$should_exit = false;
		}
		
		// Process the resulting error/warning message
		if ( ! empty( $msg ) ) {
			
			// Save the error message (based on the supplied status)
			$e20r_import_err["user_check_{$active_line_number}"] = $msg;
			
			return $should_exit;
		}
		
		return false;
	}
	
	/**
	 * Validate the User information in the record
	 *
	 * @param array $record
	 * @param bool  $allow_update
	 *
	 * @return bool|int
	 */
	public static function validate( $record, $allow_update ) {
		
		return false;
	}
}