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

namespace E20R\Import_Members\Validate;

class Time {

	/**
	 * Validate whether the time string supplied is a valid time string
	 * @param string $time_string
	 *
	 * @return bool
	 */
	public static function validate( $time_string ) {

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
	public static function convert( $time_string ) {

		$timestamp = strtotime( $time_string, time() );

		if ( false === $timestamp ) {
			$timestamp = strtotime(
				preg_replace( '/\//', '-', $time_string ),
				time()
			);
		}

		return $timestamp;
	}
}
