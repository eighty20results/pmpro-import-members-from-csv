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

/**
 * The following snippets uses `PLUGIN` to prefix
 * the constants and class names. You should replace
 * it with something that matches your plugin name.
 */
// define test environment
define( 'PLUGIN_PHPUNIT', true );

// define fake ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() );
}
// define fake PLUGIN_ABSPATH
if ( ! defined( 'PLUGIN_ABSPATH' ) ) {
	define( 'PLUGIN_ABSPATH', sys_get_temp_dir() . '/wp-content/plugins/pmpro-import-members-from-csv/' );
}

if ( ! defined( 'PLUGIN_PATH' ) ) {
	define( 'PLUGIN_PATH', __DIR__ . '/../pmpro-import-members-from-csv/' );
}


// Include the class for PluginTestCase
//if ( file_exists( __DIR__ . '/wpunit/inc/PluginTestCase.php' ) ) {
//	require_once __DIR__ . '/wpunit/inc/PluginTestCase.php';
//}

use AspectMock\Kernel;

$loader = require __DIR__ . '/../inc/autoload.php';
$loader->add( 'AspectMock', __DIR__ . '/../src' );
$loader->add( 'Import_Members', __DIR__ . '/../class.pmpro-import-members.php' );
$loader->register();

$kernel = AspectMock\Kernel::getInstance();
$kernel->init(
	array(
		'debug'              => true,
		'cacheDir'           => __DIR__ . '/_data/cache',
		'includePaths'       => array(
			__DIR__ . '/../inc/autoload.php',
			__DIR__ . '/../class.pmpro-import-members.php',
			__DIR__ . '/../src',
		),
		'interceptFunctions' => true,
	)
);

return $loader;

