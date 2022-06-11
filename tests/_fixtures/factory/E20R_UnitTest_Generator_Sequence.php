<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 Luca Tumedei
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package E20R\Tests\Fixtures\Factory\E20R_UnitTest_Generator_Sequence
 */
namespace E20R\Tests\Fixtures\Factory;

/**
 * Generator sequence for the factory class
 */
class E20R_UnitTest_Generator_Sequence {
	public static $incr = -1;
	public $next;
	public $template_string;

	/**
	 * Generates paramter templates for the Factory
	 *
	 * @param string $template_string
	 * @param int|null $start
	 */
	public function __construct( $template_string = '%s', $start = null ) {
		if ( $start ) {
			$this->next = $start;
		} else {
			self::$incr++;
			$this->next = self::$incr;
		}
		$this->template_string = $template_string;
	}

	/**
	 * Generate next value based on template
	 *
	 * @return string
	 */
	public function next() {
		$generated = sprintf( $this->template_string, $this->next );
		$this->next++;
		return $generated;
	}

	/**
	 * Get the incrementor.
	 *
	 * @return int
	 */
	public function get_incr() {
		return self::$incr;
	}

	/**
	 * Get the template string.
	 *
	 * @return string
	 */
	public function get_template_string() {
		return $this->template_string;
	}
}
