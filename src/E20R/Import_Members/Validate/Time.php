<?php
/**
 * Copyright (c) 2018-2022. - Eighty / 20 Results by Wicked Strong Chicks.
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
 *
 * @package E20r\Import_Members\Validate\Time
 */

namespace E20R\Import_Members\Validate;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Variables;

if ( ! class_exists( '\E20R\Import_Members\Validate\Time' ) ) {
	/**
	 * Class Time
	 * @package E20R\Import_Members\Validate
	 */
	class Time extends Base_Validation {

		/**
		 * Instantiates the validation class
		 *
		 * @param Variables|null $variables Instance of the Variables() class
		 * @param Error_Log|null $error_log Instance of the Error_Log() class
		 *
		 * @throws \E20R\Exceptions\InvalidInstantiation Raised when we don't include the Error_Log and Variables() classes
		 */
		public function __construct( $variables = null, $error_log = null ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
			parent::__construct( $variables, $error_log );
		}
		/**
		 * Validate whether the time string supplied is a valid time string
		 *
		 * @param string $time_string
		 *
		 * @return bool
		 */
		public function validate( $time_string ) {

			$time = strtotime( $time_string );

			if ( false === $time ) {
				return false;
			}

			return true;
		}

		/**
		 * Attempt to locate a valid time for the specified time string
		 *
		 * @param string $time_string
		 *
		 * @return false|int
		 */
		public function convert( $time_string ) {

			$timestamp = strtotime( $time_string, time() );

			if ( false === $timestamp ) {
				$timestamp = strtotime(
					preg_replace( '/\//', '-', $time_string ),
					time()
				);
			}

			return $timestamp;
		}

		/**
		 * Load filter and action handlers
		 *
		 * @return void
		 */
		public function load_actions() {
		}

		/**
		 * Set error types we can ignore for this validator (if applicable)
		 *
		 * @param array $ignored_error_list List of error classes/types to ignore
		 * @param string $module_name The name of this module
		 *
		 * @return array|void
		 */
		public function load_ignored_module_errors( $ignored_error_list, $module_name = 'base' ) {
			return $ignored_error_list;
		}
	}
}
