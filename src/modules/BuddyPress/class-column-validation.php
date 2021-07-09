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

namespace E20R\Import_Members\Modules\BuddyPress;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Validate\Base_Validation;
use E20R\Import_Members\Modules\BuddyPress\BuddyPress;

class Column_Validation extends Base_Validation {

	/**
	 * Get or instantiate and get the current class
	 *
	 * @return Column_Validation|Base_Validation|null
	 */
	public static function get_instance() {

		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter(
				'e20r_import_errors_to_ignore',
				array( self::$instance, 'load_ignored_module_errors' ),
				10,
				2
			);

			// Add list of errors to ignore for the BuddyPress module
			self::$instance->errors_to_ignore = apply_filters(
				'e20r_import_errors_to_ignore',
				self::$instance->errors_to_ignore,
				'buddypress'
			);
		}

		return self::$instance;
	}

	/**
	 * Define the module specific errors to ignore
	 *
	 * @param array $ignored_error_list - List of error keys to ignore/treat as non-fatal
	 * @param string $module_name - Name of the module (BuddyPress)
	 *
	 * @return array
	 */
	public function load_ignored_module_errors( $ignored_error_list, $module_name = 'buddypress' ) {

		if ( 'buddypress' !== $module_name ) {
			return $ignored_error_list;
		}

		$this->error_log->debug( "Loading BuddyPress specific error(s) when it's safe to can continue importing" );

		$this->errors_to_ignore = array(
			'bp_field_name' => true,
		);

		return $ignored_error_list + $this->errors_to_ignore;
	}

	/**
	 * Load action and filter handlers for PMPro validation
	 */
	public function load_actions() {

		if ( ! function_exists( 'bp_core_new_nav_default' ) ) {
			return;
		}

		add_filter( 'e20r_import_members_validate_field_data', array( $this, 'bp_field_exists' ), 1, 3 );
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

		$buddy_press = new BuddyPress();
		$buddy_press->load_fields( array() );

		if ( ! isset( $fields['bp_field_name'] ) ) {
			$this->error_log->debug( "No need to process 'bp_field_name' column" );
			return $has_error;
		}

		if ( ! isset( $fields['bp_field_name'] ) && in_array( 'bp_field_name', array_keys( $fields ), true ) ) {
			$this->error_log->debug( "'bp_field_name' is doesn't need to be processed..." );
			return $has_error;
		}

		if ( isset( $fields['bp_field_name'] ) && empty( $fields['bp_field_name'] ) ) {
			$has_error = $has_error && ( ! $this->ignore_validation_error( 'bp_field_name' ) );
		}

		// FIXME: Add check for 'bp_field_exists' for the supplied fields/data

		return $has_error;
	}
}
