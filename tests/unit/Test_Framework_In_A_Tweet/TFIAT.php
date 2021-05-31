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

namespace E20R\Test\Unit\Test_In_A_Tweet;

if ( ! defined( 'BASE_SRC_PATH' ) ) {
	define( 'BASE_SRC_PATH', '/../..' );
}

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit_Framework_TestCase;
use function Brain\Monkey\Functions\stubEscapeFunctions;

abstract class TFIAT extends PHPUnit_Framework_TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Unit Test harness using the test-framework-in-a-tweet
	 *
	 * @credit https://inpsyde.com/en/php-unit-tests-without-wordpress/
	 *
	 * @param string $message - "Tweet" like message describing the expected outcome
	 * @param mixed $function - Function that will perform the test
	 */
	public function it( string $message, $function ): void {
		// @codingStandardsIgnoreStart
		$d = debug_backtrace( 0 )[0];
		try {
			$this->loadMocks();
			$this->loadStubs();
			Monkey\setUp();
			$this->loadTestSources();
			ob_start();
			$this->runner( $message, $function );
			$output = \ob_get_clean();
			Monkey\tearDown();
			printf( '%s', $output );
		} catch ( \Throwable $t ) {
			$GLOBALS['e'] = true;
			printf( '\e[31m✘ It %s \e[0m', $message );
			printf(
				'FAIL in: %s #%d. %s\n',
				$d['file'],
				$d['line'],
				$t->getMessage()
			);
		}
		// @codingStandardsIgnoreEnd
	}

	/**
	 * The base Test Framework In a Tweet execution function
	 *
	 * @param string $m - Text to describe the expected test outcome
	 * @param mixed $p Unit test function definition
	 */
	function runner( string $m, $p ) {
		$d                      = \debug_backtrace( 0 )[0];
		\is_callable( $p ) && $p = $p();
		global $e;
		$e = $e || ! $p;
		$o = \esc_attr__( 'e[3' . ( $p ? '2m✔' : '1m✘' ) . " It ${m}e[0m" );
		echo \esc_attr__( ( $p ? "${o}n" : "${o} FAIL in: {$d['file']} #{$d['line']}n" ) );
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

\register_shutdown_function(
	function() {
		global $e;
		( ! empty( $e ) ) && die( 1 );
	}
);