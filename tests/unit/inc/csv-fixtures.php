<?php
/**
 * Copyright (c) 2022. - Eighty / 20 Results by Wicked Strong Chicks.
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

/**
 * Read test user data to use
 *
 * @param int $line_id Line # to read data from (positive integer values)
 *
 * @return array
 */
function fixture_read_from_user_csv( $line_id ) {
	list( $header, $user_info ) = read_from_csv(
		dirname( E20R_IMPORT_PLUGIN_FILE ) . '/tests/_data/csv_files/user_data.csv',
		$line_id
	);
	return array( $header, $user_info );
}

/**
 * Read test metadata to use
 *
 * @param int $line_id Line # to read data from (positive integer values)
 *
 * @return array
 */
function fixture_read_from_meta_csv( $line_id ) {
	list( $header, $meta_info ) = read_from_csv(
		dirname( E20R_IMPORT_PLUGIN_FILE ) . '/tests/_data/csv_files/user_meta.csv',
		$line_id
	);
	return array( $header, $meta_info );
}

/**
 * Read from the specific CSV file (with path)
 *
 * @param string $file_name Name of the CSV file to read from
 * @param int    $line_id The Line # to read from (line # 0 = header)
 *
 * @return mixed
 */
function read_from_csv( $file_name, $line_id ) {

	$file_object = new SplFileObject( $file_name, 'r' );
	$data_array  = array();

	// Use the expected delimiters, enclosures and escape characters
	$file_object->setCsvControl(
		E20R_IM_CSV_DELIMITER,
		E20R_IM_CSV_ENCLOSURE,
		E20R_IM_CSV_ESCAPE
	);
	$file_object->setFlags(
		SplFileObject::READ_AHEAD |
		SplFileObject::DROP_NEW_LINE |
		SplFileObject::SKIP_EMPTY
	);

	// Read the CSV header and turn it into an array of keys
	// $file_object->seek( 0 );

	$line    = $file_object->fgetcsv();
	$headers = make_header_array( $line );

	$file_object->seek( $line_id );
	$line = $file_object->fgetcsv();

	foreach ( $line as $key => $value ) {
		$column_name = $headers[ $key ];
		$column      = trim( $value );
		if ( ! empty( $column_name ) ) {
			$data_array[ $column_name ] = $column;
		}
	}

	return array( $headers, $data_array );
}

/**
 * Create the header array (skip empty header entries)
 *
 * @param string[] $line The CSV file entry as an array
 *
 * @return array
 */
function make_header_array( $line ) {
	$headers = fixture_strip_bom( $line );
	return $headers;
}

/**
 * Remove the BOM character from the array entry (string)
 *
 * @param string[] $text_array
 *
 * @return string[]
 */
function fixture_strip_bom( $text_array ) {
	// Clear the old (possible) BOM 'infected' key
	$bom           = pack( 'H*', 'EFBBBF' );
	$text_array[0] = preg_replace( "/^{$bom}/", '', $text_array[0] );
	reset( $text_array );

	return $text_array;
}
