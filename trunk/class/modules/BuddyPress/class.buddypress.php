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
namespace E20R\Paid_Memberships_Pro\Import_Members\Modules\BuddyPress;


use E20R\Paid_Memberships_Pro\Import_Members\Data;
use E20R\Paid_Memberships_Pro\Import_Members\Error_Log;
use E20R\Paid_Memberships_Pro\Import_Members\Import_Members_From_CSV;

class BuddyPress {
	
	/**
	 * Singleton instance of this class (BuddyPress)
	 *
	 * @var null|BuddyPress
	 */
	private static $instance = null;
	
	/**
	 * BuddyPress constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Get or instantiate and return this class (BuddyPress)
	 *
	 * @return BuddyPress|null
	 */
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load BuddyPress specific functionality
	 */
	public function load_hooks() {
		
		add_filter( 'e20r-import-members-supported-field-list', array( $this, 'load_fields' ), 2, 1 );
	}
	
	/**
	 * Load supported fields for BuddyPress
	 *
	 * @param array $field_list
	 *
	 * @return array
	 */
	public function load_fields( $field_list ) {
		
		$data = Data::get_instance();
		$error_log = Error_Log::get_instance();
		
		if ( false === $data->does_table_exist( 'bp_xprofile_fields' ) ) {
			
			$error_log->add_error_msg(
				sprintf(
					__( 'Error: table %s does not exists in the database!', Import_Members_From_CSV::plugin_slug ),
					'bp_xprofile_fields'
				)
			);

			$error_log->debug("Could not find 'bp_xprofile_fields' table");

			return $field_list;
		}
		
		/*
		 * Fetch xProfile fields for BuddyPress
		 */
		$profile_fields = $data->get_table_info( 'bp_xprofile_fields' );
		
		
		if ( false === $data->does_table_exist( 'bp_xprofile_groups' ) ) {
			$error_log->add_error_msg(
				sprintf(
					__( 'Error: table %s does not exists in the database!', Import_Members_From_CSV::plugin_slug ),
					'bp_xprofile_groups'
				)
			);
			
			$error_log->debug("Could not find 'bp_xprofile_groups' table");
			return $field_list;
		}
		/*
		 * Fetch Group fields for BuddyPress
		 */
		$group_fields = $data->get_table_info( 'bp_xprofile_groups' );
		
		/**
		 * Fetch list of fields to ignore/exclude for import
		 */
		$excluded_fields = apply_filters( 'e20r-import-members-excluded-buddypress-fields', array() );
		
		// Process BuddyPress Group fields names
		foreach( $group_fields as $field_name => $default ) {
			
			if ( !in_array( $field_name, $excluded_fields ) ) {
				
				$field_list[ "bp_group_{$field_name}"] = $default;
			}
		}
		
		// Process xProfile field names
		foreach ($profile_fields as $field_name => $default ) {
			
			if ( !in_array( $field_name, $excluded_fields ) ) {
				
				$field_list[ "bp_profile_{$field_name}"] = $default;
			}
		}
		
		return $field_list;
	}
	
	/**
	 * Clone the class (Singleton)
	 *
	 * @access private
	 */
	private function __clone() {
		// TODO: Implement __clone() method.
	}
}