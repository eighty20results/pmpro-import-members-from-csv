<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
Plugin Name: Import Paid Memberships Pro Members from CSV
Plugin URI: http://wordpress.org/plugins/pmpro-import-members-from-csv/
Description: Import Users and their metadata from a csv file.
Version: 3.1.4
Requires PHP: 7.3
Author: <a href="https://eighty20results.com/thomas-sjolshagen/">Thomas Sjolshagen <thomas@eighty20results.com></a>
License: GPL2
Text Domain: pmpro-import-members-from-csv
Domain Path: languages/
*/

/**
 * Copyright 2017-2021 - Thomas Sjolshagen (https://eighty20results.com/thomas-sjolshagen)
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

use E20R\Import_Members\Import_Members;

use E20R\Utilities\Utilities;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function plugin_dir_path;
use function add_action;
use function error_log; // phpcs:ignore

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
	define( 'E20R_IMPORT_VERSION', '3.1.4' );
}

require_once plugin_dir_path( __FILE__ ) . 'class-activateutilitiesplugin.php';

/**
 * Class Loader - AutoLoad classes/sources for the plugin
 *
 * @package E20R
 */
class Loader {

	/**
	 * Class auto-loader
	 *
	 * @param string $class_name Name of the class to auto-load
	 *
	 * @since  1.0
	 * @access public static
	 *
	 * @since  v2.2 - BUG FIX: Include blocks/ directory to auto_loader() method
	 * @since v3.0 - BUG FIX: Refactored to only contain the autoloader method
	 */
	public static function auto_loader( $class_name ) {

		if ( false === stripos( $class_name, 'e20r' ) ) {
			return;
		}

		$parts      = explode( '\\', $class_name );
		$c_name     = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
		$base_paths = array();

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'src/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'src/';
		}

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'classes/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'classes/';
		}

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'class/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'class/';
		}

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'blocks/' ) ) {
			$base_paths[] = plugin_dir_path( __FILE__ ) . 'blocks/';
		}
		$filename = "class-{$c_name}.php";

		foreach ( $base_paths as $base_path ) {

			try {
				$iterator = new RecursiveDirectoryIterator(
					$base_path,
					RecursiveDirectoryIterator::SKIP_DOTS |
					RecursiveIteratorIterator::SELF_FIRST |
					RecursiveIteratorIterator::CATCH_GET_CHILD |
					RecursiveDirectoryIterator::FOLLOW_SYMLINKS
				);
			} catch ( \Exception $e ) {
				print 'Error: ' . $e->getMessage(); // phpcs:ignore
				return;
			}

			try {
				$filter = new RecursiveCallbackFilterIterator(
					$iterator,
					function ( $current, $key, $iterator ) use ( $filename ) {

						// Skip hidden files and directories.
						if ( '.' === $current->getFilename()[0] || '..' === $current->getFilename() ) {
							return false;
						}

						if ( $current->isDir() ) {
							// Only recurse into intended subdirectories.
							return $current->getFilename() === $filename;
						} else {
							// Only consume files of interest.
							return str_starts_with( $current->getFilename(), $filename );
						}
					}
				);
			} catch ( \Exception $e ) {
				echo 'Autoloader error: ' . $e->getMessage(); // phpcs:ignore
				return;
			}

			foreach ( new RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {

				$class_path = $f_file->getPath() . '/' . $f_file->getFilename();

				if ( $f_file->isFile() && false !== stripos( $class_path, $filename ) ) {

					require_once $class_path;
				}
			}
		}
	}

	/**
	 * Validates that the E20R utilities module is available and active (and attempts to activate it if not)
	 */
	public static function is_utilities_module_active() {
		$for_plugin = 'Import Paid Memberships Pro Members from CSV';

		if ( false === \E20R\Utilities\ActivateUtilitiesPlugin::attempt_activation() ) {
			add_action(
				'admin_notices',
				function () use ( $for_plugin ) {
					\E20R\Utilities\ActivateUtilitiesPlugin::plugin_not_installed( $for_plugin );
				}
			);
		}
	}
}

if ( ! defined( 'E20R_IMPORT_PLUGIN_FILE' ) ) {
	define( 'E20R_IMPORT_PLUGIN_FILE', __FILE__ );
}
# Load the composer autoloader for the 10quality utilities
if ( file_exists( __DIR__ . '/inc/autoload.php' ) ) {
	require_once __DIR__ . '/inc/autoload.php';
}

// Register the auto-loader for this plugin
try {
	spl_autoload_register( '\E20R\Import\Loader::auto_loader' );
} catch ( \Exception $exception ) {
	// phpcs:ignore
	error_log(
		__(
			'Unable to load E20R autoloader for the PMPro Import Members from CSV plugin!',
			'pmpro-import-members-from-csv'
		),
		E_USER_ERROR
	);

	return;
}

\register_deactivation_hook( __FILE__, 'E20R\\Import_Members\\Import_Members::deactivation' );

// Load this plugin
add_action( 'plugins_loaded', array( Import_Members::get_instance(), 'load_hooks' ), 10 );
