<?php
/**
Plugin Name: Import Paid Memberships Pro Members from CSV
Plugin URI: http://wordpress.org/plugins/pmpro-import-members-from-csv/
Description: Import Users and their metadata from a csv file.
Version: 4.0
Requires PHP: 7.3
Author: <a href="https://eighty20results.com/thomas-sjolshagen/">Thomas Sjolshagen <thomas@eighty20results.com></a>
License: GPL2
Text Domain: pmpro-import-members-from-csv
Domain Path: languages/
*/

/**
 * Copyright 2017 - 2022 - Thomas Sjolshagen (https://eighty20results.com/thomas-sjolshagen)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @credit http://wordpress.org/plugins/import-users-from-csv/ - Ulich Sossou -  https://github.com/sorich87
 * @credit https://github.com/strangerstudios/pmpro-import-users-from-csv - Jason Coleman - https://github.com/ideadude
 */

namespace E20R\Import;

use E20R\Import_Members\Import;
use E20R\Utilities\ActivateUtilitiesPlugin;
use function add_action;
use function register_deactivation_hook;

// Make sure Composer (and thus the autoloader exists)
if ( ! file_exists( __DIR__ . '/composer.phar' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Missing autoloader: Import Members for PMPro plugin cannot be loaded' );
	return;
}

// Load the PSR-4 Autoloader
global $e20r_import_loader;
$e20r_import_loader = require_once __DIR__ . '/inc/autoload.php';

if ( ! defined( 'E20R_IMPORT_PLUGIN_FILE' ) ) {
	define( 'E20R_IMPORT_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'E20R_IM_CSV_DELIMITER' ) ) {
	define( 'E20R_IM_CSV_DELIMITER', ',' );
}
if ( ! defined( 'E20R_IM_CSV_ESCAPE' ) ) {
	define( 'E20R_IM_CSV_ESCAPE', '\\' );
}
if ( ! defined( 'E20R_IM_CSV_ENCLOSURE' ) ) {
	define( 'E20R_IM_CSV_ENCLOSURE', '"' );
}

if ( ! defined( 'E20R_IMPORT_VERSION' ) ) {
	define( 'E20R_IMPORT_VERSION', '3.2' );
}

if ( ! class_exists( '\E20R\Import\Loader' ) ) {
	/**
	 * Class Loader - AutoLoad classes/sources for the plugin
	 *
	 * @returns bool
	 *
	 * @package E20R
	 */
	class Loader {

		/**
		 * Validates that the E20R utilities module is available and active (and attempts to activate it if not)
		 */
		public static function is_utilities_module_active() {
			$for_plugin = 'Import Paid Memberships Pro Members from CSV';

			if ( false === ActivateUtilitiesPlugin::attempt_activation() ) {
				add_action(
					'admin_notices',
					function () use ( $for_plugin ) {
						ActivateUtilitiesPlugin::plugin_not_installed( $for_plugin );
					}
				);
				return false;
			}
			return true;
		}
	}
}

if ( defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) && true === Loader::is_utilities_module_active() ) {
	register_deactivation_hook( __FILE__, 'E20R\\Import_Members\\Import::deactivation' );
	// Load this plugin
	add_action( 'plugins_loaded', array( new Import(), 'load_hooks' ), 10 );
}
