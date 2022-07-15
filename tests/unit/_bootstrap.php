<?php
/**
 * Copyright (c) 2016 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @package \
 */

// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
error_log( 'Loading _bootstrap for unit tests' );

// Make sure we log all debug info to console during unit tests
if ( ! defined( 'WP_DEBUG' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Setting WP_DEBUG constant' );
	define( 'WP_DEBUG', true );
}

if ( ! class_exists( 'MemberOrder' ) && file_exists( __DIR__ . '/inc/class.memberorder.php' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Loading mock/replacement MemberOrder() class' );
	require_once __DIR__ . '/inc/class.memberorder.php';
}

if ( ! class_exists( 'WP_User' ) && file_exists( __DIR__ . '/inc/WP_User.php' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Loading mock/replacement WP_User() class' );
	require_once __DIR__ . '/inc/WP_User.php';
}

if ( ! class_exists( 'WP_Error' ) && file_exists( __DIR__ . '/inc/class-wp-error.php' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Loading mock/replacement WP_Error() class' );
	require_once __DIR__ . '/inc/class-wp-error.php';
}

if ( class_exists( '\WP_Mock' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Loading the WP_Mock settings' );
	WP_Mock::setUsePatchwork( true );
}

if ( file_exists( __DIR__ . '/inc/csv-fixtures.php' ) ) {
	require_once __DIR__ . '/inc/csv-fixtures.php';
}
