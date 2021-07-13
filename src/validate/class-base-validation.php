<?php
/*
 *  Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

namespace E20R\Import_Members\Validate;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\PMPro\Column_Validation as PMPro_Validation;
use E20R\Import_Members\Modules\Users\Column_Validation as User_Validation;
use E20R\Import_Members\Modules\BuddyPress\Column_Validation as BP_Validation;

abstract class Base_Validation {

	/**
	 * Instance of the column validation logic for PMPro
	 *
	 * @var null|Base_Validation|PMPro_Validation|User_Validation|BP_Validation
	 */
	protected static $instance = null;

	/**
	 * Error log class
	 *
	 * @var null|Error_Log
	 */
	protected $error_log = null;

	/**
	 * List of error types we should ignore
	 *
	 * @var array
	 */
	protected $errors_to_ignore = array();

	/**
	 * Base_Validation constructor.
	 *
	 * @access private
	 */
	protected function __construct() {
		$this->error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Should we ignore the column validation error type specified?
	 *
	 * @param string $error_key
	 *
	 * @return bool
	 */
	protected function ignore_validation_error( $error_key ) {

		if ( in_array( $error_key, $this->errors_to_ignore, true ) ) {
			return $this->errors_to_ignore[ $error_key ];
		}

		return false;
	}

	/**
	 * Get or instantiate and get the current class
	 *
	 * @return User_Validation|PMPro_Validation|BP_Validation|Base_Validation|null
	 */
	abstract public static function get_instance();

	/**
	 * Load all validation actions for the specific module
	 */
	abstract public function load_actions();

	/**
	 * Load error keys that represents non-fatal validation errors for a given module
	 *
	 * @param array $ignored_error_list - Error keys we can treat as non-fatal
	 * @param string $module_name - Name of the module we're processing
	 * @return array
	 */
	abstract public function load_ignored_module_errors( $ignored_error_list, $module_name = 'base' );
}
