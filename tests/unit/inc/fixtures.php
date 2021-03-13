<?php
namespace E20R\Test\Fixtures;

use E20R\Import_Members\Import_Members;
use Brain\Monkey\Functions;

if ( ! defined( 'E20R_UNITTEST_ROW_COUNT' ) ) {
	define( 'E20R_UNITTEST_ROW_COUNT', 3 );
}

/**
 * Fixture for the plugin_row_meta_data unit test
 *
 * Expected info for fixture:
 *      (array) $row_meta_list, (string) $file_name, (int) $expected_results
 *
 * @return array[]
 */
function fixture_plugin_row_meta_data() {
	Functions\stubs(
		array(
			'\\esc_url'                    => 'https://localhost:7537',
			'\\esc_url_raw'                => 'https://localhost:7537',
			'\\plugin_dir_path'            => '/var/www/html/wp-content/plugins/pmpro-import-members-from-csv',
			'\\register_deactivation_hook' => true,
			'\\__',
		)
	);

	require_once __DIR__ . '/../../../class.pmpro-import-members.php';

	return array(
		array( array(), 'class.pmpro-import-members.php', E20R_UNITTEST_ROW_COUNT ),
		array(
			array(
				'settings' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( '/wp-admin/?page=pmpro-import-members' ),
					__( 'Settings Page', Import_Members::PLUGIN_SLUG ),
					__( 'Settings Page', Import_Members::PLUGIN_SLUG )
				),
			),
			'class.pmpro-import-members.php',
			( E20R_UNITTEST_ROW_COUNT + 1 ),
		),
		array( array(), 'class-loader.php', 0 ),
		array(
			array(
				'donate'        => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url_raw( 'https://www.paypal.me/eighty20results' ),
					__(
						'Donate to support updates, maintenance and tech support for this plugin',
						Import_Members::PLUGIN_SLUG
					),
					__( 'Donate', Import_Members::PLUGIN_SLUG )
				),
				'documentation' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/' ),
					__( 'View the documentation', Import_Members::PLUGIN_SLUG ),
					__( 'Docs', Import_Members::PLUGIN_SLUG )
				),
				'help'          => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv' ),
					__( 'Visit the support forum', Import_Members::PLUGIN_SLUG ),
					__( 'Support', Import_Members::PLUGIN_SLUG )
				),
			),
			'class.pmpro-import-members.php',
			E20R_UNITTEST_ROW_COUNT,
		),
		array(
			array(
				'donate'        => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url_raw( 'https://www.paypal.me/eighty20results' ),
					__(
						'Donate to support updates, maintenance and tech support for this plugin',
						Import_Members::PLUGIN_SLUG
					),
					__( 'Donate', Import_Members::PLUGIN_SLUG )
				),
				'documentation' => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/plugins/pmpro-import-members-from-csv/' ),
					__( 'View the documentation', Import_Members::PLUGIN_SLUG ),
					__( 'Docs', Import_Members::PLUGIN_SLUG )
				),
				'help'          => sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv' ),
					__( 'Visit the support forum', Import_Members::PLUGIN_SLUG ),
					__( 'Support', Import_Members::PLUGIN_SLUG )
				),
			),
			'class-pmpro-import-members.php', // Note, not the correct plugin file string
			E20R_UNITTEST_ROW_COUNT,
		),
	);
}