<?php
/**
 * Copyright (c) 2018-2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

use E20R\Import_Members\Data;
use E20R\Import_Members\Error_Log;

class BuddyPress {

	/**
	 * Singleton instance of this class (BuddyPress)
	 *
	 * @var null|BuddyPress
	 */
	private static $instance = null;

	/**
	 * Instance of the Data class
	 *
	 * @var Data|null $data
	 */
	private $data = null;

	/**
	 * Instance of the Error_Log class
	 *
	 * @var Error_Log|null $error_log
	 */
	private $error_log = null;

	/**
	 * The names of BuddyPress tables we can import to
	 *
	 * @var string[] $required_tables
	 */
	private $required_tables = array();

	/**
	 * BuddyPress fields (XProfile, XGroup, etc)
	 *
	 * @var array $field_list
	 */
	private $field_list = array();

	/**
	 * Fields to ignore/exclude when importing
	 *
	 * @var array $excluded_fields
	 */
	private $excluded_fields = array();

	/**
	 * BuddyPress constructor.
	 *
	 * @access private
	 */
	public function __construct() {
		$this->data      = new Data();
		$this->error_log = new Error_Log(); // phpcs:ignore

		$this->required_tables = apply_filters(
			'e20r_import_buddypress_tables',
			array(
				'bp_xprofile_fields' => 'bp_profile',
				'bp_xprofile_groups' => 'bp_group',
			)
		);
	}

	/**
	 * Load BuddyPress specific functionality
	 */
	public function load_hooks() {
		add_filter( 'e20r_import_supported_fields', array( $this, 'load_fields' ), 2, 1 );
	}

	/**
	 * Load supported fields for BuddyPress
	 *
	 * @param array $field_list
	 *
	 * @return array
	 */
	public function load_fields( $field_list ) {

		/**
		 * Fetch list of fields to ignore/exclude for import
		 */
		$this->excluded_fields = apply_filters( 'e20r_import_buddypress_excluded_fields', array() );

		foreach ( $this->required_tables as $table_name => $meta_key_prefix ) {
			// Make sure the table exists
			if ( false === $this->data->does_table_exist( $table_name ) ) {
				$this->error_log->add_error_msg(
					sprintf(
					// translators: %1$s - The database table we're checking for (<prefix><name>)
						__(
							'Error: table %1$s does not exists in the database. Is BuddyPress correctly installed?',
							'pmpro-import-members-from-csv'
						),
						$table_name
					)
				);
				// It didn't so we'll skip to the next table.
				$this->error_log->debug( "Could not find {$table_name} table" );
				continue; // Skip
			}

			// Fetch field info for the BuddyPress table
			$field_data = $this->data->get_table_info( $table_name );

			// Process BuddyPress fields names and set default values as applicable
			foreach ( $field_data as $field_name => $default ) {
				if ( ! in_array( $field_name, $this->excluded_fields, true ) ) {
					$this->field_list[ "${meta_key_prefix}_{$field_name}" ] = $default;
				}
			}
		}

		return $field_list + $this->field_list;
	}
}

add_action( 'e20r_import_load_licensed_modules', array( new Import_BuddyPress(), 'load_actions' ) );
