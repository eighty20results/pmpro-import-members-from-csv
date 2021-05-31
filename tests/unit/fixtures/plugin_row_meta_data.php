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

namespace E20R\Test\Unit\Fixtures;

use E20R\Import_Members\Import_Members;
use Brain\Monkey\Functions;

if ( ! defined( 'E20R_UNITTEST_ROW_COUNT' ) ) {
	define( 'E20R_UNITTEST_ROW_COUNT', 0 );
}

/**
 * Fixture for the plugin_row_meta_data unit test
 *
 * Expected info for fixture:
 *      (array) $row_meta_list, (string) $file_name, (int) $expected_results
 *
 * @return array[]
 */
function plugin_row_meta_data() {
	return array(
		array( array(), 'class.pmpro-import-members.php', E20R_UNITTEST_ROW_COUNT ),
		array(
			array(
				'settings' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( '/wp-admin/?page=pmpro-import-members' ),
					\__( 'Settings Page', 'pmpro-import-members-from-csv' ),
					\__( 'Settings Page', 'pmpro-import-members-from-csv' )
				),
			),
			'class.pmpro-import-members.php',
			( E20R_UNITTEST_ROW_COUNT + 1 ),
		),
		array( array(), 'class-loader.php', E20R_UNITTEST_ROW_COUNT ),
		array(
			array(
				'donate'        => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url_raw( 'https://www.paypal.me/eighty20results' ),
					\__(
						'Donate to support updates, maintenance and tech support for this plugin',
						'pmpro-import-members-from-csv'
					),
					\__( 'Donate', 'pmpro-import-members-from-csv' )
				),
				'documentation' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/' ),
					\__( 'View the documentation', 'pmpro-import-members-from-csv' ),
					\__( 'Docs', 'pmpro-import-members-from-csv' )
				),
				'help'          => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv' ),
					\__( 'Visit the support forum', 'pmpro-import-members-from-csv' ),
					\__( 'Support', 'pmpro-import-members-from-csv' )
				),
			),
			'class.pmpro-import-members.php',
			E20R_UNITTEST_ROW_COUNT + 3,
		),
		array(
			array(
				'donate'        => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url_raw( 'https://www.paypal.me/eighty20results' ),
					\__(
						'Donate to support updates, maintenance and tech support for this plugin',
						'pmpro-import-members-from-csv'
					),
					\__( 'Donate', 'pmpro-import-members-from-csv' )
				),
				'documentation' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/' ),
					\__( 'View the documentation', 'pmpro-import-members-from-csv' ),
					\__( 'Docs', 'pmpro-import-members-from-csv' )
				),
				'help'          => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv' ),
					\__( 'Visit the support forum', 'pmpro-import-members-from-csv' ),
					\__( 'Support', 'pmpro-import-members-from-csv' )
				),
			),
			'class-pmpro-import-members.php', // Note, not the correct plugin file string
			E20R_UNITTEST_ROW_COUNT + 3,
		),
	);
}