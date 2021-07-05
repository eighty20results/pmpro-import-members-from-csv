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

use E20R\Import_Members\Status;

/**
 * Class Validate
 * @package E20R\Import_Members\Validate
 */
abstract class Validate extends Base_Validation {

	abstract public static function status_msg( $status, $allow_updates );

	/**
	 * Validate the record
	 *
	 * @param array $record
	 * @param bool $allow_update
	 *
	 * @return int|bool
	 */
	abstract public static function validate( $record, $allow_update );
}
