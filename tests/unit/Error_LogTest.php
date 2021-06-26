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

namespace E20R\Test\Unit;

use E20R\Import_Members\Error_Log;
use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Mocked (namespace specific) error_log function
 *
 * @param        $message
 * @param int    $type
 * @param string $destination
 * @param string $extra_headers
 */
function error_log( $message, $type = 0, $destination = '', $extra_headers = '' ) {

	$severity = 'PHP Notice';

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "{$severity}: {$message}";
}

class Error_LogTest extends \Codeception\Test\Unit {

	use MockeryPHPUnitIntegration;

	private $log_file_path = '/var/log/www/wp-content/uploads/e20r_im_errors.log';

	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(
			new vfsStreamDirectory(
				basename( $this->log_file_path )
			)
		);
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testLog_errors() {

	}

	public function testDebug() {

	}

	public function testAdd_error_msg() {

	}

	public function testDisplay_admin_message() {

	}
}
