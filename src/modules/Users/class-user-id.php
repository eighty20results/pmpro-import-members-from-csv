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

use E20R\Paid_Memberships_Pro\Import_Members\Error_Log;
use E20R\Paid_Memberships_Pro\Import_Members\Status;
use E20R\Paid_Memberships_Pro\Import_Members\Import_Members_From_CSV;

class User_ID extends Validate {
	
	
	/**
	 * Set the status/error message for the User_ID validation logic
	 *
	 * @param int  $status
	 * @param bool $allow_updates
	 *
	 * @return string
	 */
	public static function status_msg( $status, $allow_updates ) {
		
		switch ( $status ) {
			case Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED:
				
				$msg = __( 'Error: User ID exists but cannot be updated per the plugin settings', Import_Members_From_CSV::plugin_slug );
				break;
			
			case Status::E20R_ERROR_ID_NOT_NUMBER:
				$msg = __( 'Supplied information in ID column is not a number', Import_Members_From_CSV::plugin_slug );
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
	 * @param bool  $allow_update
	 *
	 * @return bool|int
	 */
	public static function validate( $record, $allow_update ) {
		
		$error_log = Error_Log::get_instance();
		
		$has_ID = ( isset( $record['ID'] ) && ! empty( $record['ID'] ) );
		
		if ( false === $has_ID ) {
			$error_log->debug( "Is the user's ID value present? " . ( $has_ID ? 'Yes' : 'No' ) );
			
			return false;
		}
		
		if ( true === $has_ID && false === is_int( $record['ID'] ) ) {
			
			$error_log->debug( "'ID' column isn't a number" );
			
			return Status::E20R_ERROR_ID_NOT_NUMBER;
		}
		
		if ( true === $has_ID && false !== get_user_by( 'ID', $record['ID'] ) && false === $allow_update ) {
			$error_log->debug( "'ID' column is for a current user _AND_ we're not allowing updates" );
			
			return Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED;
		}
		
		$error_log->debug( "User ID is present and a number..." );
		
		return true;
	}
}