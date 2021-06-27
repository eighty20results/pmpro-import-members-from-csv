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

namespace E20R\Test\Unit\Fixtures;

/**
 * Fixture for the CSV::get_import_file_path() unit test
 *
 * Expected info for fixture:
 *      string full path, (string) $expected_file_name
 *
 * @return array[]
 */
function import_file_names() : array {
	return array(
		array(
			array(
				'members_csv' => array(
					'tmp_name' => 'jklflkkk.csv',
					'name'     => '/home/user1/csv_files/test-error-imports.csv',
				),
			),
			'test-error-imports.csv',
		),
		array(
			array(
				'members_csv' => array(
					'tmp_name' => 'jklflkkk.csv',
					'name'     => '/var/www/html/wp-content/uploads/e20r_import/example_file.csv',
				),
			),
			'example_file.csv',
		),
		array(
			array(
				'members_csv' => array(
					'tmp_name' => '',
					'name'     => '/path/',
				),
			),
			'',
		),
	);
}
