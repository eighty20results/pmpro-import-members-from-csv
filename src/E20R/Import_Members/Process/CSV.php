<?php
/**
 * Copyright (c) 2018 - 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Import_Members\Process;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\Import_User;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;
use SplFileObject;
use WP_Error;

if ( ! class_exists( '\E20R\Import_Members\Process\CSV' ) ) {
	/**
	 * Class CSV
	 * @package E20R\Import_Members\Process
	 */
	class CSV {
		/**
		 * Instance of this class (CSV)
		 *
		 * @var null|CSV
		 */
		private static $instance = null;

		/**
		 * Error log class
		 * @var Error_Log|null $error_log = null;
		 */
		private $error_log = null;

		/**
		 * Instance of the Variables class
		 * @var Variables|null $variables
		 */
		private $variables = null;

		/**
		 * CSV constructor.
		 *
		 * @param Variables|null $variables Instance of the Request variables (settings) class
		 * @param Error_Log|null $error_log For debug logging and status messages
		 */
		public function __construct( $variables = null, $error_log = null ) {

			if ( null === $error_log ) {
				$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			$this->error_log = $error_log;

			if ( null === $variables ) {
				$variables = new Variables( $this->error_log );
			}
			$this->variables = $variables;
		}

		/**
		 * Return the path to the Import file (from the $_REQUEST, the $_FILES array, or a transient)
		 *
		 * @param null|string $file_name
		 *
		 * @return bool|string
		 *
		 * @throws InvalidSettingsKey Thrown if 'filename' became an invalid setting
		 */
		public function get_import_file_path( $file_name = null ) {

			if ( empty( $file_name ) ) {
				$this->error_log->debug( 'Loading file name from Variables()' );
				$file_name = $this->variables->get( 'filename' );
			}

			if ( empty( $file_name ) ) {
				$this->error_log->debug( 'Loading file name from transient' );
				$file_name = get_transient( 'e20r_import_filename' );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $file_name ) && ! empty( $_REQUEST['filename'] ) ) {
				$this->error_log->debug( "Loading '{$_REQUEST['filename']}' from REQUEST variable" );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$file_name = sanitize_file_name( $_REQUEST['filename'] );
			}

			if ( empty( $file_name ) ) {
				$this->error_log->debug( 'Name of uploaded file is missing!' );
				return false;
			}

			$upload_dir = wp_upload_dir();
			$import_dir = trailingslashit( $upload_dir['basedir'] ) . 'e20r_imports';
			$file       = basename( $file_name );
			$file_name  = "{$import_dir}/{$file}";

			$this->error_log->debug( "Looking for '{$file_name}' and it exists? " . ( file_exists( $file_name ) ? 'Yes' : 'No' ) );

			if ( false === file_exists( $file_name ) ) {
				$this->error_log->debug( 'File not found!' );
				return false;
			}

			return $file_name;
		}

		/**
		 * Process the Import (uploaded) file & set variables as expected
		 *
		 * @param string $tmp_name
		 *
		 * @return false|string
		 */
		public function pre_process_file( $tmp_name = null ) {

			$saved_filename = get_transient( 'e20r_import_filename' );

			if ( ! isset( $_FILES['members_csv']['tmp_name'] ) && empty( $saved_filename ) && empty( $tmp_name ) ) {
				$this->error_log->debug( 'Nothing to do without an Import file!' );

				return false;
			}

			if ( ! empty( $tmp_name ) ) {
				$saved_filename = $tmp_name;
			}

			$this->error_log->debug( "Before processing FILES array: {$saved_filename} vs {$tmp_name}" );

			$saved_filename = $_FILES['members_csv']['name'] ?? $saved_filename;
			$saved_filename = $_REQUEST['filename'] ?? $saved_filename; //phpcs:ignore

			//Check for a imports directory in wp-content
			// $file_name       = self::get_import_file_path( $saved_filename );
			$upload_dir      = wp_upload_dir();
			$import_dir      = $upload_dir['basedir'] . '/e20r_imports';
			$directory_error = false;
			$no_file_error   = false;

			$background_import = (bool) $this->variables->get( 'background_import' );

			// Save the filename for the option
			$this->variables->set( 'filename', $saved_filename );

			if ( empty( $saved_filename ) && true === $background_import ) {

				$this->error_log->add_error_msg(
					esc_attr__( 'CSV file not selected. Nothing to Import!', 'pmpro-import-members-from-csv' ),
					'error'
				);

				$no_file_error = true;
			}

			$clean_file_error = $this->clean_files( $saved_filename );
			$destination_name = "{$import_dir}/{$saved_filename}";

			if ( ! is_dir( $import_dir ) && false === wp_mkdir_p( $import_dir ) ) {

				$this->error_log->add_error_msg(
					sprintf(
					// translators: %s Directory for the CSV Import file
						esc_attr__(
							'Unable to create directory on your server. Directory: %s',
							'pmpro-import-members-from-csv'
						),
						$import_dir
					),
					'error'
				);

				$this->error_log->debug( "Unable to create or find the Import directory: {$import_dir}" );

				return false;
			}

			// Save the uploaded file
			if ( false !== strpos( $_FILES['members_csv']['tmp_name'], $upload_dir['basedir'] ) ) {

				// Was uploaded and saved to $_SESSION
				rename( $_FILES['members_csv']['tmp_name'], $destination_name );
				$this->error_log->debug( ( "Renamed {$_FILES['members_csv']['tmp_name']} to {$destination_name}" ) );
			} else {
				// Just uploaded in this request operation
				move_uploaded_file( $_FILES['members_csv']['tmp_name'], $destination_name );
				$this->error_log->debug( ( "Moved {$_FILES['members_csv']['tmp_name']} to $destination_name" ) );
			}

			// @phpstan-ignore-next-line
			$this->error_log->debug( 'Directory error? ' . ( $directory_error ? 'Yes' : 'No' ) );
			$this->error_log->debug( 'File upload not selected error? ' . ( $no_file_error ? 'Yes' : 'No' ) );
			$this->error_log->debug( 'File limit error? ' . ( $clean_file_error ? 'Yes' : 'No' ) );

			// @phpstan-ignore-next-line
			if ( ( true === $no_file_error || true === $directory_error || true === $clean_file_error ) ) {
				$this->error_log->debug( 'Error: Problem with uploaded file...' );

				return false;
			}

			$this->error_log->debug( 'Calculating the transient timeout value' );

			$transient_timeout = (
				( (int) $this->variables->get( 'per_partial' ) * $this->variables->calculate_per_record_time() ) +
				apply_filters( 'e20r_import_set_transient_time_buffer', 20 )
			);

			$this->error_log->debug( "Using transient timeout value of {$transient_timeout}" );
			set_transient( 'e20r_import_filename', $saved_filename, $transient_timeout );

			return $saved_filename;
		}

		/**
		 * Clean up the uploaded file names/rename them
		 *
		 * @param string $file_name
		 *
		 * @return bool
		 * @throws InvalidSettingsKey Thrown if the filename variable is unset/not present
		 */
		private function clean_files( $file_name ) {
			$dir_name  = dirname( $file_name );
			$file_arr  = explode( '.', $this->variables->get( 'filename' ) );
			$file_type = $file_arr[ ( count( $file_arr ) - 1 ) ];
			$count     = 0;

			while ( file_exists( $file_name ) ) {

				if ( ! empty( $count ) ) {
					$file_name = $this->str_lreplace( "-{$count}.{$file_type}", '-' . strval( $count + 1 ) . ".{$file_type}", $file_name );
				} else {
					$file_name = $this->str_lreplace( ".{$file_type}", "-1.{$file_type}", $file_name );
				}

				$this->variables->set( 'filename', $file_name );
				$count ++;

				//let's not expect more than 50 files with the same name
				if ( $count > 50 ) {

					$this->error_log->add_error_msg(
						sprintf(
						// translators: %1$s CSV file name, %2$s upload directory for CSV file name
							esc_attr__(
								'Error uploading file: The %1$s file has been uploaded too many times. Please clean out the %2$s directory on your server.',
								'pmpro-import-members-from-csv'
							),
							basename( $file_name ),
							$dir_name
						),
						'error'
					);

					return true;
				}
			}

			return false;
		}

		/**
		 * Replace leftmost instance of string
		 *
		 * @param string $search
		 * @param string $replace
		 * @param string $subject
		 *
		 * @return string
		 *
		 * @access private
		 */
		private function str_lreplace( $search, $replace, $subject ) {

			$pos = strrpos( $subject, $search );

			if ( false !== $pos ) {
				$subject = substr_replace( $subject, $replace, $pos, strlen( $search ) );
			}

			return $subject;
		}

		/**
		 * Import a csv file
		 *
		 * @param string           $file_name Name of the CSV file we're processing
		 * @param array            $args File arguments supplied
		 * @param Import_User|null $import_user  Optional Import_User)_ class instance
		 *
		 * @return array
		 *
		 * @since 0.5
		 */
		public function process( $file_name, $args, $import_user = null ) {

			global $active_line_number;
			global $e20r_import_err;
			global $e20r_import_warn;

			$current_blog_id = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 1;

			if ( ! is_array( $e20r_import_err ) ) {
				$e20r_import_err = array();
			}

			if ( ! is_array( $e20r_import_warn ) ) {
				$e20r_import_warn = array();
			}

			$headers = array();

			if ( null === $import_user ) {
				$import_user = new Import_User();
			}

			$user_ids = array();
			$defaults = apply_filters( 'pmp_im_import_default_settings', $this->variables->get_defaults() );
			$defaults = apply_filters( 'e20r_import_default_settings', $defaults );

			// Securely extract variables
			$settings = wp_parse_args( $args, $defaults );

			// Cast variables to expected type
			$suppress_pwdmsg = (bool) $settings['suppress_pwdmsg'];
			$partial         = (bool) $settings['partial'];
			$site_id         = $settings['site_id'];
			$per_partial     = apply_filters( 'pmp_im_import_records_per_scan', intval( $settings['per_partial'] ) );
			$per_partial     = apply_filters( 'e20r_import_records_per_scan', $per_partial );

			// Mac CR+LF fix
			ini_set( 'auto_detect_line_endings', '1' );

			$file        = basename( $file_name );
			$file_object = new SplFileObject( $file_name, 'r' );

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

			// Loop through the file lines
			$first               = true;
			$current_line_number = 0;
			$active_line_number  = 0;

			if ( is_multisite() && ! empty( $site_id ) ) {
				switch_to_blog( $site_id );
			}

			// Suppress the Email message to the user that their password was/may have been changed
			if ( true === $suppress_pwdmsg ) {
				add_filter( 'send_email_change_email', '__return_false', 99 );
				add_filter( 'send_password_change_email', '__return_false', 99 );
			}

			while ( ( ! $file_object->eof() ) && ( ! ( true === $partial ) || $current_line_number <= $per_partial ) ) {
				$user_id = null;
				$active_line_number++;
				$error_key = null;

				// Read a line from the file and remove the BOM character
				$line = $file_object->fgetcsv();

				// If the first line is empty, abort
				// If another line is empty, just skip it
				if ( empty( $line ) ) {
					if ( true === $first ) {
						$msg = esc_attr__( 'The expected header line in the Import file is missing?!?', 'pmpro-import-members-from-csv' );
						$this->error_log->add_error_msg( $msg, 'error' );
						$e20r_import_err[ "header_missing_{$active_line_number}" ] = new WP_Error( 'e20r_im_header', $msg );
						break;
					}
					$this->error_log->debug( "Line # {$active_line_number} is empty" );
					continue;
				}

				// If we are on the first line, the columns are the headers
				if ( true === $first ) {
					$headers = $this->process_header( $line );
					$first   = false;

					// Skip ahead ?
					if ( ! empty( $partial ) ) {

						// Get filename only
						$active_line_number = get_option( "e20rcsv_{$file}", null );

						// Skip to the proper line (during AJAX operations)
						if ( ! empty( $active_line_number ) ) {
							$file_object->seek( $active_line_number );
						}
					}

					// On to the next line in the file
					continue;
				} else {
					$active_line_number = $file_object->key();
				}

				// Separate user data from meta
				$user_data = array();
				$user_meta = array();

				$this->error_log->debug( "Processing next user data. (previous line #: {$active_line_number})" );
				try {
					$this->extract_data(
						$line,
						$user_data,
						$user_meta,
						$headers,
						$this->variables->get( 'user_fields' )
					);
				} catch ( InvalidSettingsKey $e ) {
					$this->error_log->add_error_msg(
						sprintf(
							// translators: %1$d - Line number being imported, %2$s - CSV file name
							esc_attr__( 'Cannot extract data from line %1$d in file: %2$s', 'pmpro-import-members-from-csv' ),
							$active_line_number,
							$file_name
						)
					);
					continue;
				}

				$this->error_log->debug( "Processed line #{$active_line_number}..." );
				$user_id_status = apply_filters( 'e20r_import_users_validate_field_data', false, null, $user_data );

				// TODO: Process cached warning messages as well

				switch ( $user_id_status ) {
					case Status::E20R_ERROR_NO_USER_ID:
						$msg = sprintf(
						// translators: %1$d - Line number in the CSV file being imported
							esc_attr__(
								'Missing ID, user_login and/or user_email information column at row %1$d',
								'pmpro-import-members-from-csv'
							),
							$active_line_number
						);

						$error_key = "user_id_missing_{$active_line_number}";
						break;
					case Status::E20R_ERROR_USER_NOT_FOUND:
						$msg = sprintf(
						// translators: %1$d - User ID, %2$d - Current line number in the CSV file being imported
							esc_attr__(
								'WP User ID %1$d not found in database (from CSV file line: %2$d)',
								'pmpro-import-members-from-csv'
							),
							$user_data['ID'],
							$active_line_number
						);
						$error_key = "user_missing_{$active_line_number}";
						break;

					case Status::E20R_ERROR_NO_UPDATE_FROM_LOGIN:
						$msg = sprintf(
						// translators: %1$d - User's user_login info, %2$d - Current line number in the CSV file being imported
							esc_attr__(
								'User login %1$d already exists, updates are disallowed (from CSV file line: %2$d)',
								'pmpro-import-members-from-csv'
							),
							$user_data['user_login'],
							$active_line_number
						);
						$warning_key = "existing_user_login_{$active_line_number}";
						break;

					case Status::E20R_ERROR_NO_UPDATE_FROM_EMAIL:
						$msg = sprintf(
						// translators: %1$d - User's user_email, %2$d - Current line number in the CSV file being imported
							esc_attr__(
								'User (%1$d) already exists, updates are disallowed (from CSV file line: %2$d)',
								'pmpro-import-members-from-csv'
							),
							$user_data['user_email'],
							$active_line_number
						);
						$warning_key = "existing_user_email_{$active_line_number}";
						break;

					case Status::E20R_ERROR_NO_EMAIL_OR_LOGIN:
						$msg = sprintf(
						// translators: %1$d - User's user_email, %2$d - Current line number in the CSV file being imported
							esc_attr__(
								'Neither user_email, nor user_login information provided (from CSV file line: %1$d)',
								'pmpro-import-members-from-csv'
							),
							$active_line_number
						);
						$error_key = "user_email_missing_{$active_line_number}";
						break;
				}

				if ( true !== $user_id_status && ! empty( $msg ) ) {
					if ( ! empty( $error_key ) ) {
						$e20r_import_err[ $error_key ] = new WP_Error( 'e20r_im_missing_data', $msg );
					}
					if ( ! empty( $warning_key ) ) {
						$e20r_import_warn[ $error_key ] = new WP_Error( 'e20r_im_missing_data', $msg );
					}
					$this->error_log->debug( $msg );
					$msg = null;
				}

				if ( ! empty( $msg ) ) {
					$this->error_log->add_error_msg( $msg, 'error' );
				}

				// A plugin may need to filter the data and meta
				$user_data = apply_filters( 'is_iu_import_userdata', $user_data, $user_meta, $settings );
				$user_data = apply_filters( 'pmp_im_import_userdata', $user_data, $user_meta, $settings );
				$user_data = apply_filters( 'e20r_import_userdata', $user_data, $user_meta, $settings );
				$user_meta = apply_filters( 'is_iu_import_usermeta', $user_meta, $user_data, $settings );
				$user_meta = apply_filters( 'pmp_im_import_usermeta', $user_meta, $user_data, $settings );
				$user_meta = apply_filters( 'e20r_import_usermeta', $user_meta, $user_data, $headers );

				// If no user data, bailout!
				if ( empty( $user_data ) ) {
					$msg = sprintf(
					// translators: %d - Line number in the CSV file being imported
						esc_attr__( 'No user data found at row #%d', 'pmpro-import-members-from-csv' ),
						( $active_line_number + 1 )
					);

					$e20r_import_warn[ "warning_userdata_{$active_line_number}" ] = new WP_Error( 'e20r_im_nodata', $msg );

					$this->error_log->debug( $msg );
				} else {

					$this->error_log->debug( 'Importing user data' );
					// Try to Import user record and trigger other Import Modules
					try {
						$user_id = $import_user->import( $user_data, $user_meta, $headers );
					} catch ( InvalidSettingsKey $e ) {
						$msg = sprintf(
						// translators: %1$d - Line number in the CSV file being imported
							esc_attr__( 'Cannot import user data from row %1$d', 'pmpro-import-members-from-csv' ),
							$active_line_number
						);

						$this->error_log->add_error_msg( $msg, 'error' );
						$this->error_log->debug( $msg );
						$e20r_import_err[ "user_data_missing_{$active_line_number}" ] = new WP_Error( 'e20r_im_missing_data', $msg );
					}

					/** BUG FIX: Didn't save the created user's ID and added empty user IDs*/
					if ( null !== $user_id ) {
						$user_ids[] = $user_id;
					}
				}

				if ( ! empty( $partial ) && ! empty( $active_line_number ) ) {
					// Go to next line in case we have to restart the operation
					$active_line_number = ( $file_object->key() + 1 );
					update_option( "e20rcsv_{$file}", $active_line_number, 'no' );
				}

				$current_line_number ++;
			}

			// Close the file (done by the destructor for the SplFileObject() class)
			$file_object = null;
			ini_set( 'auto_detect_line_endings', '1' );

			// One more thing to do after all imports?
			do_action( 'is_iu_post_users_import', $user_ids, $e20r_import_err );
			do_action( 'pmp_im_post_members_import', $user_ids, $e20r_import_err );
			do_action( 'e20r_import_post_members', $user_ids, $e20r_import_err );

			// Let's log the errors
			$this->error_log->log_errors(
				array_merge( $e20r_import_err, $e20r_import_warn ),
				$this->variables->get( 'logfile_path' ),
				$this->variables->get( 'logfile_url' )
			);

			// Return to the active (pre Import) site
			if ( is_multisite() ) {
				switch_to_blog( $current_blog_id );
			}

			$member_error = (bool) get_option( 'e20r_import_errors', false );

			if ( true === $member_error ) {
				$this->error_log->add_error_msg(
					esc_attr__(
						'Data format error(s) detected during the Import. Some records may not have been imported!',
						'pmpro-import-members-from-csv'
					),
					'error'
				);
				delete_option( 'e20r_import_errors' );
			}

			return array(
				'user_ids' => $user_ids,
				'errors'   => $e20r_import_err,
				'warnings' => $e20r_import_warn,
			);
		}

		/**
		 * Process the header line for the .csv file
		 *
		 * @param string[] $line
		 *
		 * @return string[]
		 */
		public function process_header( $line ) {

			$headers = $this->strip_bom( $line );

			// Remove empty/blank headers
			foreach ( $headers as $hk => $hdr ) {
				if ( empty( $hdr ) ) {
					unset( $headers[ $hk ] );
				}
			}

			return $headers;
		}

		/**
		 * Strip UTF BOM character from line of text
		 *
		 * @param string[] $text_array
		 *
		 * @return string[]
		 *
		 * @since 2.9 - ENHANCEMENT: Strip away any BOM characters
		 */
		private function strip_bom( $text_array ) {
			// Clear the old (possible) BOM 'infected' key
			$bom           = pack( 'H*', 'EFBBBF' );
			$text_array[0] = preg_replace( "/^{$bom}/", '', $text_array[0] );
			reset( $text_array );

			return $text_array;
		}

		/**
		 * Extract CSV data into correct location (column/value pairs)
		 *
		 * @param string[] $line - Line of data from the CSV file we're processing
		 * @param array $user_data - User data to Import (by reference)
		 * @param array $user_meta - User meta data to Import (by reference)
		 * @param array $headers
		 * @param array $user_data_fields
		 */
		private function extract_data( $line, &$user_data, &$user_meta, $headers, $user_data_fields ) {

			global $e20r_import_err;
			global $active_line_number;

			foreach ( $line as $ckey => $column ) {
				if ( ! isset( $headers[ $ckey ] ) ) {
					$msg = sprintf(
					// translators: %s - The column key (header)
						esc_attr__( 'Cannot find header (column) %s!', 'pmpro-import-members-from-csv' ),
						$ckey
					);
					$this->error_log->add_error_msg( $msg, 'error' );
					$e20r_import_err[ "column_{$ckey}_missing" ] = new WP_Error( 'e20r_im_header', $msg );

					$this->error_log->debug( $msg );
					$active_line_number ++;

					return;
				}

				$column_name = $headers[ $ckey ];
				$column      = trim( $column );

				if ( in_array( $column_name, $user_data_fields, true ) ) {
					$user_data[ $column_name ] = $column;
				} else {
					$user_meta[ $column_name ] = $column;
				}
			}

			$active_line_number ++;
		}
	}
}
