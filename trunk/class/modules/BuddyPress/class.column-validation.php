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

namespace E20R\Paid_Memberships_Pro\Import_Members\Modules\BuddyPress;


use E20R\Paid_Memberships_Pro\Import_Members\Error_Log;

class Column_Validation {
	
	/**
	 * Instance of the column validation logic for BuddyPress
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
		
		if ( ! function_exists( 'bp_core_new_nav_default' ) ) {
			return;
		}
		
		add_filter( 'e20r-import-members-validate-field-data', array( $this, 'bp_field_exists' ), 1, 3 );
	}
	
	/**
	 * Example: check of membership ID for the BuddyPress column value validation
	 *
	 * @param bool $has_error
	 * @param int $user_id
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function bp_field_exists( $has_error, $user_id, $fields ) {
		
		$error_log   = Error_Log::get_instance();
		$buddy_press = BuddyPress::get_instance();
		$buddy_press->load_fields( array() );
		
		if ( ! isset( $fields['bp_field_name'])) {
			$error_log->debug("No need to process 'bp_field_name' column");
			return $has_error;
		}
		
		if ( ! isset( $fields['bp_field_name'] ) && in_array( 'bp_field_name', array_keys( $fields ) ) ) {
			$error_log->debug( "'bp_field_name' is doesn't need to be processed..." );
			
			return $has_error;
		}
		
		if ( isset( $fields['bp_field_name'] ) && empty( $fields['bp_field_name'] ) ) {
			$has_error = $has_error && true;
		}
		
		// FIXME: Add check for 'bp_field_exists' for the supplied fields/data
		
		return $has_error;
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