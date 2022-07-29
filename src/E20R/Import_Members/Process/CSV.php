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

use E20R\Exceptions\InvalidProperty;
use E20R\Exceptions\NoHeaderDataFound;
use E20R\Exceptions\NoUserDataFound;
use E20R\Exceptions\NoUserMetadataFound;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Modules\Users\User_Present;
use E20R\Import_Members\Variables;
use SplFileObject;
use WP_Error;

use function \wp_upload_dir;

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
		 * Instance of the User_Present() class
		 *
		 * @var User_Present|null
		 */
		private $user_present = null;

		/**
		 * CSV constructor.
		 *
		 * @param Variables|null $variables Instance of the Request variables (settings) class
		 * @param Error_Log|null $error_log For debug logging and status messages
		 */
		public function __construct( $variables = null, $error_log = null, $user_present = null ) {

			if ( null === $error_log ) {
				$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			$this->error_log = $error_log;

			if ( null === $variables ) {
				$variables = new Variables( $this->error_log );
			}
			$this->variables = $variables;

			if ( null === $user_present ) {
				$user_present = new User_Present( $this->variables, $this->error_log );
			}

			$this->user_present = $user_present;
		}

		/**
		 * Return the path to the Import file (from the $_REQUEST, the $_FILES array, or a transient)
		 *
		 * @param null|string $file_name The file name to check the existence of
		 * @param null|string $import_dir_path The (mocked!) path to the upload directory
		 *
		 * @return bool|string
		 *
		 * @throws InvalidProperty Thrown if 'filename' became an invalid setting
		 */
		public function verify_import_file_path( $file_name = null, $import_dir_path = null ) {

			// Only override the import directory when we're executing integration or unit tests
			if ( null === $import_dir_path ) {
				$upload_dir      = wp_upload_dir();
				$import_dir_path = trailingslashit( $upload_dir['basedir'] ) . 'e20r_imports';
			}

			if ( empty( $file_name ) ) {
				$file_name = $this->variables->get( 'filename' );
			}

			if ( empty( $file_name ) ) {
				$file_name = get_transient( 'e20r_import_filename' );
			}

			if ( empty( $file_name ) ) {
				// Nonce handled in Ajax() class
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$file_name = sanitize_file_name( $_REQUEST['filename'] ?? null );
			}

			// We enforce the location of the uploaded file (shouldn't be possible to fool us
			$file_name = basename( $file_name );

			if ( empty( $file_name ) ) {
				$this->error_log->debug( 'Name of uploaded file is missing!' );
				return false;
			}

			$file_to_find = sprintf( '%1$s/%2$s', $import_dir_path, $file_name );

			if ( empty( $import_dir_path ) || false === file_exists( $file_to_find ) ) {
				$this->error_log->debug( 'File not found!' );
				return false;
			}

			return $file_to_find;
		}

		/**
		 * Process the Import (uploaded) file & set variables as expected
		 *
		 * @param string $tmp_name
		 *
		 * @return false|string
		 * @throws InvalidProperty When 'background_import', 'filename', and 'per_partial' aren't valid Variables() class member properties
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

			//Check for the directory in wp-content where we'll save the uploaded file
			$filename        = $this->verify_import_file_path( $saved_filename );
			$upload_dir      = wp_upload_dir();
			$import_dir      = dirname( $filename );
			$saved_filename  = basename( $filename );
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
			$destination_name = "{$filename}";

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
		 * @throws InvalidProperty Thrown if the filename variable is unset/not present
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
		 * @param array            $options Plugin options (array)
		 * @param SplFileObject|null $file_object Optional SplFileObject() class instance
		 *
		 * @return array
		 *
		 * @throws NoHeaderDataFound Thrown if the CSV file lacks the header row
		 * @throws NoUserDataFound Thrown if one of the filter handlers returns an empty array of user data
		 * @throws NoUserMetadataFound Thrown when one of the filter handlers returns and empty array of user metadata
		 * @throws InvalidProperty Thrown if the property specified in the Variable::get() doesn't exist
		 *
		 * @since 0.5
		 */
		public function process( $file_name, $options, $file_object = null ) {

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

			$headers  = array();
			$user_ids = array();
			$defaults = apply_filters( 'pmp_im_import_default_settings', $this->variables->get_defaults() );
			$defaults = apply_filters( 'e20r_import_default_settings', $defaults );

			// Securely extract variables
			$settings = wp_parse_args( $options, $defaults );

			// Cast variables to expected type
			$suppress_pwdmsg = (bool) $settings['suppress_pwdmsg'];
			$partial         = (bool) $settings['partial'];
			$site_id         = $settings['site_id'];
			$per_partial     = apply_filters( 'pmp_im_import_records_per_scan', intval( $settings['per_partial'] ) );
			$per_partial     = apply_filters( 'e20r_import_records_per_scan', $per_partial );

			// Mac CR+LF fix
			ini_set( 'auto_detect_line_endings', '1' );

			$file = basename( $file_name );

			if ( null === $file_object ) {
				$file_object = new SplFileObject( $file_name, 'r' );
			}

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
						throw new NoHeaderDataFound();
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

				try {
					$this->clean_data(
						$line,
						$user_data,
						$user_meta,
						$headers,
						$this->variables->get( 'user_fields' )
					);
				} catch ( NoHeaderDataFound $e ) {
					$this->error_log->add_error_msg(
						sprintf(
						// translators: %1$s - CSV file name
							esc_attr__( "CSV column headers not found in '%1\$s'", 'pmpro-import-members-from-csv' ),
							$file_name
						)
					);
					break;
				} catch ( InvalidProperty $e ) {
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

				// A plugin may need to filter the data and meta
				$user_data = apply_filters( 'is_iu_import_userdata', $user_data, $user_meta, $settings );
				$user_data = apply_filters( 'pmp_im_import_userdata', $user_data, $user_meta, $settings );
				$user_data = apply_filters( 'e20r_import_userdata', $user_data, $user_meta, $settings );
				$user_meta = apply_filters( 'is_iu_import_usermeta', $user_meta, $user_data, $settings );
				$user_meta = apply_filters( 'pmp_im_import_usermeta', $user_meta, $user_data, $settings );
				$user_meta = apply_filters( 'e20r_import_usermeta', $user_meta, $user_data, $headers );

				// The *_import_userdata filter did something unexpected
				if ( empty( $user_data ) ) {
					$this->error_log->debug( 'Invalid line of CSV data found in import file!' );
					throw new NoUserDataFound();
				}

				// The *_import_usermeta filter did something unexpected
				if ( empty( $user_meta ) ) {
					$this->error_log->debug( 'Empty user metadata from CSV file!?!' );
					throw new NoUserMetadataFound();
				}

				$user_record_valid = apply_filters(
					'e20r_import_users_validate_field_data',
					false,
					null,
					$user_data
				);

				if ( true === $user_record_valid ) {
					// Using a filter to trigger an add, or update for the actual WordPress user
					$user_id = apply_filters( 'e20r_import_wp_user_data', $user_data, $user_meta, $headers, $settings );

					/** BUG FIX: Didn't save the created user's ID and added empty user IDs*/
					if ( ! empty( $user_id ) ) {
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

			// More things to do after the user record was imported?
			do_action( 'is_iu_post_users_import', $user_ids, $e20r_import_err );
			do_action( 'pmp_im_post_members_import', $user_ids, $e20r_import_err );
			do_action( 'e20r_import_post_members', $user_ids, $e20r_import_err );

			// Let's log the warning messages
			$this->error_log->log_errors(
				$e20r_import_warn,
				$this->variables->get( 'logfile_warning_path' ),
				$this->variables->get( 'logfile_warning_url' )
			);

			// Let's log the errors
			$this->error_log->log_errors(
				$e20r_import_err,
				$this->variables->get( 'logfile_error_path' ),
				$this->variables->get( 'logfile_error_url' )
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
		 * Clean CSV data and place at the correct location (column/value pairs)
		 *
		 * @param string[] $line - Line of data from the CSV file we're processing
		 * @param array $user_data - User data to Import (by reference)
		 * @param array $user_meta - User meta data to Import (by reference)
		 * @param array $headers
		 * @param array $user_data_fields
		 *
		 * @throws NoHeaderDataFound
		 */
		private function clean_data( $line, &$user_data, &$user_meta, $headers, $user_data_fields ) {

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

					throw new NoHeaderDataFound();
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
