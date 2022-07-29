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

use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidProperty;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Variables;

if ( ! class_exists( '\E20R\Import_Members\Validate\Base_Validation' ) ) {
	/**
	 * Class Base_Validation
	 * @package E20R\Import_Members\Validate
	 */
	abstract class Base_Validation {

		/**
		 * Error log class
		 *
		 * @var null|Error_Log
		 */
		protected $error_log = null;

		/**
		 * The Variables() class
		 *
		 * @var null|Variables $variables
		 */
		protected $variables = null;

		/**
		 * List of error types we should ignore
		 *
		 * @var array
		 */
		protected $errors_to_ignore = array();

		/**
		 * Base_Validation constructor.
		 *
		 * @param null|Error_Log $error_log Instance of the Error_Log() class
		 *
		 * @throws InvalidInstantiation Thrown if the class is instantiated incorrectly
		 */
		public function __construct( $variables = null, $error_log = null ) {
			if ( null === $error_log ) {
				$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			if ( ! is_object( $error_log ) ) {
				throw new InvalidInstantiation(
					sprintf(
					// translators: %1$s: Name of supplied class, %2$s: Name of expected class
						esc_attr__(
							'"%1$s" is an unexpected class. Expecting "%2$s"',
							'pmpro-import-members-from-csv'
						),
						gettype( $error_log ),
						class_basename( Error_Log::class )
					)
				);
			}
			$this->error_log = $error_log;

			if ( null === $variables ) {
				$variables = new Variables( $this->error_log );
			}

			if ( ! is_object( $variables ) ) {
				throw new InvalidInstantiation(
					sprintf(
					// translators: %1$s: Name of supplied class, %2$s: Name of expected class
						esc_attr__(
							'"%1$s" is an unexpected class. Expecting "%2$s"',
							'pmpro-import-members-from-csv'
						),
						gettype( $variables ),
						class_basename( Variables::class )
					)
				);
			}
			$this->variables = $variables;
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
		 * Load all validation actions for the specific module
		 */
		abstract public function load_actions();

		/**
		 * Load error keys that represents non-fatal validation errors for a given module
		 *
		 * @param array $ignored_error_list - Error keys we can treat as non-fatal
		 * @param string $module_name - Name of the module we're processing
		 *
		 * @return array
		 */
		abstract public function load_ignored_module_errors( $ignored_error_list, $module_name = 'base' );

		/**
		 * Returns the class parameter value requested
		 *
		 * @param string $param Name of class parameter
		 *
		 * @return mixed
		 * @throws InvalidProperty Thrown if the current class lacks the specified parameter
		 */
		public function get( $param ) {
			if ( ! property_exists( $this, $param ) ) {
				throw new InvalidProperty( sprintf( '%1$s is an invalid property in %2$s', $param, self::class ) );
			}

			return $this->{$param};
		}

		/**
		 * Test whether the supplied value is an integer or not
		 *
		 * @param mixed $val Value to test for integer-ness
		 *
		 * @return bool
		 */
		public function is_valid_integer( $val ) {
			if ( is_float( $val ) ) {
				return false;
			}

			if ( in_array( $val, array( true, false, null ), true ) ) {
				return false;
			}

			if ( is_float( (float) $val + 0 ) && ( (int) $val + 0 ) > PHP_INT_MAX ) {
				return false;
			}

			if ( 0 === preg_match( '/^[\"\'+\-]?\d+[\"\']?$/', $val ) ) {
				return false;
			}

			return true;
		}
	}
}
