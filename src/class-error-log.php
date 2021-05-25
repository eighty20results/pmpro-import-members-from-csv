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
	 * The instance of this class
	 *
	 * @var null|Error_Log
	 */
	private static $instance = null;
	
	/**
	 * Error_Log constructor (singleton)
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Get or instantiate and return this singleton class (Error_Log)
	 *
	 * @return Error_Log|null
	 */
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Identify the calling function (used in debug logger
	 *
	 * @return array|string
	 *
	 * @access public
	 */
	private function _who_called_me() {
		
		$trace  = debug_backtrace();
		$caller = $trace[2];
		
		if ( isset( $caller['class'] ) ) {
			$trace = "{$caller['class']}::{$caller['function']}()";
		} else {
			$trace = "Called by {$caller['function']}()";
		}
		
		return $trace;
	}
	
	/**
	 * Save text message to web server error log (if WP_DEBUG is active)
	 *
	 * @param string $msg
	 */
	public function debug( $msg ) {
  
		if ( defined('WP_DEBUG' ) && true === WP_DEBUG ) {
		    $from = $this->_who_called_me();
			$tid  = sprintf( "%08x", abs( crc32( $_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] ) ) );
			$time = date( 'H:m:s', strtotime( get_option( 'timezone_string' ) ) );
			
			error_log( "[{$tid}]({$time}) {$from} - {$msg}\n",E_USER_NOTICE );
		}
	}
	
	/**
	 * Log errors to a file
	 *
	 * @param \WP_Error[] $errors
	 *
	 * @since 1.0
	 **/
	public function log_errors( $errors ) {
		
		if ( empty( $errors ) ) {
			global $e20r_import_err;
			$errors = $e20r_import_err;
		}
		
		// Is there truly nothing to do??
		if ( empty( $errors ) ) {
			$this->debug("No errors to log!");
			return;
		}
		
		$variables = Variables::get_instance();
		$log_file_path = $variables->get( 'logfile_path' );
		$log_file_url = $variables->get( 'logfile_url' );
		
		$this->add_error_msg(
			sprintf(
				__( 'Errors or Warnings found: Please inspect the import %1$serror log%2$s', Import_Members::PLUGIN_SLUG ),
				sprintf(
					'<a href="%1$s" title="%2$s" target="_blank">',
					esc_url_raw( $log_file_url ),
						__( "Link to import error/warning log", Import_Members::PLUGIN_SLUG )
					),
					'</a>'
				),
				'warning'
			);
		
		$log = fopen( $log_file_path, 'a' );
		
		if ( false === $log ) {
			$this->add_error_msg(
				sprintf(
					__( "Unable to write error log to: %s", Import_Members::PLUGIN_SLUG ),
					$log_file_path
				),
				'error'
			);
			fclose( $log );
			return;
		}
		
        foreach ( $errors as $key => $error ) {
            
            if ( is_numeric( $key ) ) {
                $line = $key + 1;
            } else {
                $key_info = explode( '_', $key );
                $line = (int)$key_info[ ( count( $key_info ) -1 ) ] + 1;
            }
            
            
            // Handle weird/unexpected formats for error message(s)
            if ( is_wp_error( $error ) ) {
                $message = $error->get_error_message();
            } else if ( is_string( $error ) ) {
                $message = $error;
            }
            
            if (  !empty( $message ) ) {
                @fwrite( $log, sprintf(
                                   __( '[Line %1$s] %2$s', Import_Members::PLUGIN_SLUG ),
                                   $line,
                                   $message
                               ) . "\n"
                );
            }
        }
        
        fclose( $log );
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
			if ( isset( $msg_info['message'] ) && $msg_info['message'] == $msg ) {
				$skip = true;
				break;
			}
		}
		
		if ( false === $skip && ! empty( $error_msg ) ) {
			$error_msg = array_merge( $error_msg, array( array( 'type' => $type, 'message' => $msg ) ) );
		} else if ( false === $skip && empty( $error_msg ) ) {
			$error_msg = array( array( 'type' => $type, 'message' => $msg ) );
		}
		
		update_option( 'e20r_im_error_msg', $error_msg, 'no' );
	}
	
	/**
	 * Add admin notice to WP Admin backedn
	 */
	public function display_admin_message() {
		
		
		$error_msgs = get_option( 'pmp_im_error_msg', array() );
		
		if ( WP_DEBUG ) {
			error_log("Error info: " . print_r( $error_msgs, true ) );
		}
		
		if ( !empty( $error_msgs ) && is_admin() ) {
			
			foreach( $error_msgs as $msg_info ) {
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
	
	/**
	 * Clone this class (Error_Log)
	 *
	 * @access private
	 */
	private function __clone() {
	}
}