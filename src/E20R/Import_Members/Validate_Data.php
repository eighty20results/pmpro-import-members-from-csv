<?php
/**
 * Copyright (c) 2018 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @package E20R\Import_Members\Validate_Data
 */

namespace E20R\Import_Members;

if ( ! class_exists( 'E20R\Import_Members\Validate_Data' ) ) {
	/**
	 * Class Validate_Data
	 */
	class Validate_Data {

		/**
		 * Fields to process
		 *
		 * @var array
		 */
		private $fields = array();

		/**
		 * Instance of the debug logger|error message handler
		 *
		 * @var Error_Log|null
		 */
		private $error_log = null;

		/**
		 * Constructor for the Validate_Data() class
		 *
		 * @param null|Error_Log $error_log Instance of the Error_Log() class
		 */
		public function __construct( &$error_log = null ) {
			if ( null === $error_log ) {
				$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			$this->error_log = $error_log;
		}

		/**
		 * Test the date supplied for MySQL compliance
		 *
		 * @param string $date
		 * @param string $format
		 *
		 * @return bool
		 *
		 * @credit Stack Overflow: User @glavić - https://stackoverflow.com/a/12323025
		 */
		public function date( $date, $format = 'Y-m-d H:i:s' ) {

			$check_date = \DateTime::createFromFormat( $format, $date );
			$retval     = ( false !== $check_date ) && ( $check_date->format( $format ) === $date );

			return $retval;
		}
	}
}
