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


require_once __DIR__ . '/../../../inc/autoload.php';
require_once __DIR__ . '/test-framework-in-a-tweet.php';

use Brain\Monkey;
use E20R\TestFrameworkInATweet;
use function E20R\TestFrameworkInATweet\it;

/**
 * Unit Test harness using the test-framework-in-a-tweet
 *
 * @credit https://inpsyde.com/en/php-unit-tests-without-wordpress/
 *
 * @param string $m - "Tweet" like message describing the expected outcome
 * @param mixed $p - Function that will perform the test
 */
function bm_it( $m, $p ) {
	$d = debug_backtrace( 0 )[0];
	try {
		Monkey\setUp();
		ob_start();
		\E20R\TestFrameworkInATweet\it( $m, $p );
		$output = \ob_get_clean();
		Monkey\tearDown();
		echo "${output}";
	} catch ( \Throwable $t ) {
		$GLOBALS['e'] = true;
		$msg          = $t->getMessage();
		echo  "\e[31m✘ It ${m} \e[0m";
		echo "FAIL in: {$d['file']} #{$d['line']}. $msg\n";
	}
}