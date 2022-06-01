<?php
/**
 *  Copyright (c) 2021 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
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
 *
 * @package \
 */

use Codeception\Util\Autoload;

Autoload::addNamespace( 'E20R\\Tests\\Integration\\Fixtures', __DIR__ . '/inc/' );

if ( file_exists( __DIR__ . '/../unit/inc/csv-fixtures.php' ) ) {
	require_once __DIR__ . '/../unit/inc/csv-fixtures.php';
}

if ( ! defined( 'PLUGIN_INTEGRATION' ) ) {
	define( 'PLUGIN_INTEGRATION', true );
}
// PMPro isn't very defensively coded and
if ( ! defined( 'AUTH_KEY' ) ) {
	define( 'AUTH_KEY', e20r_rand_string( 32 ) );
}

// pmpro_next_payment() assumes AUTH_KEY and SECURE_AUTH_KEY will always be defined
if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
	define( 'SECURE_AUTH_KEY', e20r_rand_string( 32 ) );
}

/**
 * Create a semi-randomized string to use for the test versions of the *AUTH_KEY constants
 *
 * @param int $length
 *
 * @return string|null
 */
function e20r_rand_string( $length ) {
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz@#$&*';
	$size  = strlen( $chars );
	$str   = null;

	for ( $i = 0; $i < $length; $i++ ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
		$str = $chars[ rand( 0, $size - 1 ) ];
	}
	return $str;
}
