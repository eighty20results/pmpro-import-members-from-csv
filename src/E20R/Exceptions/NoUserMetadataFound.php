<?php
/**
 *   Copyright (c) 2021 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
 *   ALL RIGHTS RESERVED
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package E20R\Exceptions\NoUserDataFound
 */

namespace E20R\Exceptions;

use Exception;
use Throwable;

if ( ! defined( 'ABSPATH' ) && ( ! defined( 'PLUGIN_PATH' ) ) ) {
	die( 'Cannot access source file directly!' );
}

if ( ! class_exists( 'E20R\Exception\NoUserMetadataFound' ) ) {
	/**
	 * Custom exception raised when the autoloader isn't found
	 */
	class NoUserMetadataFound extends Exception {

		/**
		 * Custom exception constructor
		 *
		 * @param string $message The exception error message to use
		 * @param int $code The Exception error code (int) to use
		 * @param Throwable|null $previous Previous exception that called this one
		 */
		public function __construct( string $message = '', int $code = 0, ?Throwable $previous = null ) {
			$message = esc_attr__(
				"A registered filter handler for 'is_iu_import_usermeta', 'pmp_im_import_usermeta' or 'e20r_import_usermeta' returns an empty array of user metadata (invalid CSV data in file, or a problem with a user supplied filter handler?)",
				'pmpro-import-members-from-csv'
			);
			parent::__construct( $message, $code, $previous );
		}
	}
}
