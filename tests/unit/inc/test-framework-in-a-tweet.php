<?php
/*
 * Copyright (c) 2018-2021. - Eighty / 20 Results by Wicked Strong Chicks.
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
 */

namespace E20R\TestFrameworkInATweet;

/**
 * The base Test Framework In a Tweet execution function
 *
 * @param string $m - Text to describe the expected test outcome
 * @param mixed $p Unit test function definition
 */
function it( $m, $p ) {
	$d                      = \debug_backtrace( 0 )[0];
	\is_callable( $p ) && $p = $p();
	global $e;
	$e = $e || ! $p;
	$o = \esc_attr__( 'e[3' . ( $p ? '2m✔' : '1m✘' ) . " It ${m}e[0m" );
	echo \esc_attr__( ( $p ? "${o}n" : "${o} FAIL in: {$d['file']} #{$d['line']}n" ) );
}

\register_shutdown_function(
	function() {
		global $e;
		( ! empty( $e ) ) && die( 1 );
	}
);