<?php
/**
 * Copyright (c) 2018-2019. - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Import_Members;

class Error_Log {

	/**
	 * Log errors to a file
	 *
	 * @param \WP_Error[] $errors
	 * @param string $log_file_path
	 * @param string $log_file_url
	 *
	 * @since 1.0
	 **/
	public function log_errors( $errors, $log_file_path, $log_file_url ) {
		if ( empty( $errors ) ) {
			global $e20r_import_err;
			$errors = $e20r_import_err;
		}

		// Is there truly nothing to do??
		if ( empty( $errors ) ) {
			$this->debug( 'No errors to log!' );
			return;
		}

		$this->add_error_msg(
			sprintf(
			// translators: %1$s - HTML for link to log file, %2$s closing HTML for link
				__( 'Errors or Warnings found: Please inspect the import %1$serror log%2$s', 'pmpro-import-members-from-csv' ),
				sprintf(
					'<a href="%1$s" title="%2$s" target="_blank">',
					esc_url_raw( $log_file_url ),
					__( 'Link to import error/warning log', 'pmpro-import-members-from-csv' )
				),
				'</a>'
			),
			'warning'
		);

		try {
			$log = fopen( $log_file_path, 'a' ); // phpcs:ignore
		} catch ( \Exception $e ) {
			$this->add_error_msg(
				__(
					'Import errors/warnings will not be logged. Unable to write to the server file system',
					'pmpro-import-members-from-csv'
				),
				'error'
			);
			return;
		}

		if ( false === $log ) {
			$this->add_error_msg(
				sprintf(
				// translators: %s Path to log file
					__( 'Unable to write error log to: %s', 'pmpro-import-members-from-csv' ),
					$log_file_path
				),
				'error'
			);
			return;
		}

		foreach ( $errors as $key => $error ) {
			if ( is_numeric( $key ) ) {
				$line = $key + 1;
			} else {
				$key_info = explode( '_', $key );
				$line     = (int) $key_info[ ( count( $key_info ) - 1 ) ] + 1;
			}

			// Handle weird/unexpected formats for error message(s)
			if ( is_wp_error( $error ) ) {
				$message = $error->get_error_message();
			} elseif ( is_string( $error ) ) {
				$message = $error;
			}

			if ( ! empty( $message ) ) {
				// phpcs:ignore
				@fwrite(
					$log,
					sprintf(
							// translators: %1$d - Line number, %2$s - Error message to log
						__( "[Line %1\$d] %2\$s\n", 'pmpro-import-members-from-csv' ),
						$line,
						$message
					)
				);
			}
		}

		fclose( $log ); // phpcs:ignore
	}

	/**
	 * Save text message to web server error log (when WP_DEBUG is true)
	 *
	 * @param string $msg
	 * @returns bool
	 */
	public function debug( $msg ) {
		if ( ! defined( 'WP_DEBUG' ) ) {
			return false;
		}

		if ( false === WP_DEBUG ) {
			return false;
		}

		$from        = $this->who_called_me();
		$server_addr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$req_time    = $_SERVER['REQUEST_TIME'] ?? time();
		$tid         = sprintf( '%08x', abs( crc32( "{$server_addr}{$req_time}" ) ) );
		$time        = gmdate( 'H:m:s', strtotime( get_option( 'timezone_string' ) ) );

		// Save to the HTTP server error log as a Notice (not a warning/error)
		// phpcs:ignore
		error_log(
			sprintf( '[%1$s](%2$s) %3$s - %4$s', $tid, $time, $from, $msg ),
			E_USER_NOTICE
		);
		return true;
	}

	/**
	 * Identify the calling function (used in debug logger
	 *
	 * @return string
	 *
	 * @access public
	 */
	private function who_called_me() : string {
		$trace  = debug_backtrace(); // phpcs:ignore
		$caller = $trace[2];

		if ( isset( $caller['class'] ) ) {
			$trace = "{$caller['class']}::{$caller['function']}()";
		} else {
			$trace = "Called by {$caller['function']}()";
		}

		return $trace;
	}

	/**
	 * Add a error/warning/success/info message to /wp-admin/
	 *
	 * @param string $msg
	 * @param string $type
	 */
	public function add_error_msg( $msg, $type = 'info' ) {

		$error_msg = get_option( 'e20r_im_error_msg', array() );
		$skip      = false;

		foreach ( $error_msg as $e_key => $msg_info ) {
			if ( isset( $msg_info['message'] ) && $msg_info['message'] === $msg ) {
				$skip = true;
				break;
			}
		}

		if ( false === $skip && ! empty( $error_msg ) ) {
			$error_msg = array_merge(
				$error_msg,
				array(
					array(
						'type'    => $type,
						'message' => $msg,
					),
				)
			);
		} elseif ( false === $skip && empty( $error_msg ) ) {
			$error_msg = array(
				array(
					'type'    => $type,
					'message' => $msg,
				),
			);
		}

		update_option( 'e20r_im_error_msg', $error_msg, 'no' );
	}

	/**
	 * Add admin notice to WP Admin backedn
	 */
	public function display_admin_message() {

		$error_msgs = get_option( 'pmp_im_error_msg', array() );
		$this->debug( 'Error info: ' . print_r( $error_msgs, true ) ); // phpcs:ignore

		if ( ! empty( $error_msgs ) && is_admin() ) {
			foreach ( $error_msgs as $msg_info ) {
				?>
				<div class="notice notice-<?php esc_attr_e( $msg_info['type'] ); ?> is-dismissible">
					<p><strong><?php esc_html_e( $msg_info['message'] ); ?></strong></p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"><?php __( 'Dismiss this message.', 'pmpro-import-members-from-csv' ); ?></span>
					</button>
				</div>
				<?php
			}

			// Clear error/warning/notice/success messages
			delete_option( 'pmp_im_error_msg' );
		}
	}
}
