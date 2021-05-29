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

namespace E20R\Test\Unit\Includes;

if ( ! defined( 'BASE_SRC_PATH' ) ) {
	define( 'BASE_SRC_PATH', '/../..' );
}

require_once __DIR__ . BASE_SRC_PATH . '/../inc/autoload.php';
require_once __DIR__ . '/test-framework-in-a-tweet.php';

use Brain\Monkey;
use Codeception\Test\Unit;
use Brain\Monkey\Functions;
use E20R\TestFrameworkInATweet;
use function E20R\TestFrameworkInATweet\runner;

abstract class BM_Base extends Unit {
	
	/**
	 * Unit Test harness using the test-framework-in-a-tweet
	 *
	 * @credit https://inpsyde.com/en/php-unit-tests-without-wordpress/
	 *
	 * @param string $message - "Tweet" like message describing the expected outcome
	 * @param mixed $function - Function that will perform the test
	 */
	public function it( string $message, $function ): void {
		$d = debug_backtrace( 0 )[0];
		try {
			Monkey\setUp();
			$this->loadMocks();
			$this->loadStubs();
			$this->loadTestSources();
			ob_start();
			TestFrameworkInATweet\runner( $message, $function );
			$output = \ob_get_clean();
			Monkey\tearDown();
			echo "${output}";
		} catch ( \Throwable $t ) {
			$GLOBALS['e'] = true;
			$msg          = $t->getMessage();
			echo  "\e[31m✘ It {$message} \e[0m";
			echo "FAIL in: {$d['file']} #{$d['line']}. $msg\n";
		}
	}
	
	/**
	 * Define function stubs for the unit test
	 */
	public abstract function loadStubs() : void;
	
	/**
	 * Define mocked functions to be used by the called function of the unit test
	 */
	public abstract function loadMocks() : void;
	
	/**
	 * require_once()runner all needed source files for the unit test
	 */
	public abstract function loadTestSources() : void;
}