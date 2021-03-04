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

namespace E20R\Import_Members;


class Validate_Data {
	
	private static $instance = null;
	
	private $fields = array();
	
	private function __construct() {
	}
	
	private function __clone() {}
	
	/**
	 * Get or instantiate and return this class instance (Validate_Data)
	 *
	 * @return Validate_Data|null
	 */
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
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
		$retval     = ( false !== $check_date ) && ( $check_date->format( $format ) == $date );
		
		return $retval;
	}
	
	
}