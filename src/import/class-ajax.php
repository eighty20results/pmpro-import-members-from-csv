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

namespace E20R\Import_Members\Import;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Import_Members;
use E20R\Import_Members\Modules\PMPro\Import_Sponsors;

/**
 * Class Ajax
 * @package E20R\Import_Members
 */
class Ajax {

	/**
	 * @var null|Ajax $instance
	 */
	private static $instance = null;

	/**
	 * @var null|string $filename
	 */
	private $filename = null;

	/**
	 * Instance of the Error_Log class
	 *
	 * @var Error_Log|null $error_log
	 */
	private $error_log = null;

	/**
	 * Instance of the CSV class
	 *
	 * @var null|CSV $csv
	 */
	private $csv = null;

	/**
	 * Instance of the Variables class (settings)
	 *
	 * @var Variables|null $variables
	 */
	private $variables = null;

	/**
	 * Ajax_Import constructor.
	 */
	private function __construct() {
		$this->error_log = new Error_Log(); // phpcs:ignore
		$this->variables = new Variables();
		$this->csv       = new CSV( $this->variables );
	}

	/**
	 * @return Ajax|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load Action and Filter hooks for this class
	 */
	public function load_hooks() {

		add_action( 'wp_ajax_import_from_csv', array( $this, 'wp_ajax_import_from_csv' ) );
		add_action( 'wp_ajax_cleanup_csv', array( $this, 'wp_ajax_cleanup_csv' ) );
		add_action( 'wp_ajax_clear_log', array( $this, 'wp_ajax_clear_log' ) );

		add_action( 'wp_ajax_e20r_visitor_clicked_donation', array( $this, 'save_donation_ip' ) );
		add_action( 'wp_ajax_nopriv_e20r_visitor_clicked_donation', array( $this, 'save_donation_ip' ) );
	}

	/**
	 * Save IP address of client computer if the user clicks the "donate" button
	 */
	public function save_donation_ip() {
		// phpcs:ignore
		$this->error_log->debug( "Visitor clicked the 'Donate' button: " . print_r( $_REQUEST, true ) );

		check_admin_referer( 'e20r-im-import-members', 'e20r-im-import-members-wpnonce' );

		$this->error_log->debug( 'Nonce is good' );

		$client_ip    = $this->get_client_ip();
		$do_not_track = apply_filters( 'e20r_import_donation_tracking_disabled', false );

		if ( ! empty( $client_ip ) && false === $do_not_track ) {

			$donated               = get_option( 'e20r_import_has_donated', array() );
			$donated[ $client_ip ] = time();

			if ( true === update_option( 'e20r_import_has_donated', $donated ) ) {

				$this->error_log->debug( "Visitor ({$client_ip}) clicked the 'Donate' button" );
				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Get the IP address for the client/viewer of the page
	 *
	 * @return null|string
	 */
	public function get_client_ip() {

		$client    = $_SERVER['HTTP_CLIENT_IP'] ?? null;
		$forward   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
		$remote    = $_SERVER['REMOTE_ADDR'];
		$client_ip = null;

		if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
			$client_ip = $client;
		} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
			$client_ip = $forward;
		} else {
			$client_ip = $remote;
		}

		return $client_ip;
	}

	/**
	 * Clear (remove) the error log
	 */
	public function wp_ajax_clear_log() {
		$logfile_path = $this->variables->get( 'logfile_path' );

		check_admin_referer( 'e20r-im-import-members', 'e20r-im-import-members-wpnonce' );

		$this->error_log->debug( "Nonce is good. Deleting -> {$logfile_path}" );

		if ( false === $this->delete_file( $logfile_path ) ) {
			wp_send_json_error();
		}

		// Return success
		wp_send_json_success();
	}

	/**
	 * Remove the import source file upon successful/complete import
	 */
	public function wp_ajax_cleanup_csv() {

		$sponsors = new Import_Sponsors();

		$this->error_log->debug( 'Import is complete... ' );

		check_admin_referer( 'e20r-im-import-members', 'e20r-im-import-members-wpnonce' );

		$file_name = $this->csv->get_import_file_path( null );

		if ( empty( $file_name ) ) {
			$this->error_log->debug( 'File not found/not available. Nothing to clean!' );
			wp_send_json_success();
		}

		$file = basename( $file_name );

		$this->error_log->debug( "Nonce is good. Cleaning up: {$file_name}" );
		$this->error_log->debug( 'Does the file exist??? ' . ( file_exists( $file_name ) ? 'Yes' : 'No' ) );

		if ( false === $this->delete_file( $file_name ) ) {

			wp_send_json_error();
		}

		delete_option( "e20rcsv_{$file}" );
		delete_transient( 'e20r_import_filename' );

		$this->error_log->debug( 'Do we have sponsors to link..?' );

		$sponsors->trigger_sponsor_updates();

		wp_send_json_success();
	}

	/**
	 * Unlink/delete the specified file (if found)
	 *
	 * @param null|string $file_name
	 *
	 * @return bool
	 */
	private function delete_file( $file_name = null ) {

		if ( ! empty( $file_name ) && file_exists( $file_name ) ) {

			$this->error_log->debug( "Removing {$file_name}" );

			return unlink( $file_name );
		}

		// Nothing to unlink so we're successful.
		return true;
	}

	/**
	 * AJAX service that does the heavy loading to import a CSV file
	 *
	 * @since 2.0
	 */
	public function wp_ajax_import_from_csv() {

		$this->error_log->debug( 'Processing AJAX request to import data?' );

		check_admin_referer( 'e20r-im-import-members', 'e20r-im-import-members-wpnonce' );

		$this->error_log->debug( 'Nonce verified in import_members_from_csv()' );

		/* @codingStandardsIgnoreStart
		 *
		if ( false === wp_verify_nonce( $_REQUEST['e20r-im-import-members-wpnonce'], 'e20r-im-import-members' ) ) {

			$msg = __( 'Insecure connection attempted!', 'pmpro-import-members-from-csv' );

			wp_send_json_error(
				array(
					'status'  => - 1,
					'message' => $msg,
				)
			);
		}
		 * @codingStandardsIgnoreEnd
		 */

		// Get our settings
		$this->variables->load_settings();
		$filename = basename( $this->variables->get( 'filename' ) );

		// Error message to return
		if ( empty( $filename ) ) {
			wp_send_json_error(
				array(
					'status'  => - 1,
					'message' => __( 'No import file provided!', 'pmpro-import-members-from-csv' ),
				)
			);
		}

		//figure out upload dir
		$upload_dir = wp_upload_dir();
		$import_dir = $upload_dir['basedir'] . '/e20r_imports';

		//make sure file exists
		if ( ! file_exists( "{$import_dir}/{$filename}" ) ) {
			wp_send_json_error(
				array(
					'status'  => - 1,
					'message' => sprintf(
						__( "File (%1\$s) not found in %2\$s\nIs the directory writable by the web server software?", 'pmpro-import-members-from-csv' ),
						$filename,
						$import_dir
					),
				)
			);
		}

		//import next few lines of file
		$args = array(
			'partial'                     => true,
			'filename'                    => $this->variables->get( 'filename' ),
			'password_nag'                => $this->variables->get( 'password_nag' ),
			'password_hashing_disabled'   => $this->variables->get( 'password_hashing_disabled' ),
			'update_users'                => $this->variables->get( 'update_users' ),
			'new_user_notification'       => $this->variables->get( 'new_user_notification' ),
			'new_member_notification'     => $this->variables->get( 'new_member_notification' ),
			'admin_new_user_notification' => $this->variables->get( 'admin_new_user_notification' ),
			'suppress_pwdmsg'             => $this->variables->get( 'suppress_pwdmsg' ),
			'send_welcome_email'          => $this->variables->get( 'send_welcome_email' ),
			'deactivate_old_memberships'  => $this->variables->get( 'deactivate_old_memberships' ),
			'background_import'           => $this->variables->get( 'background_import' ),
			'per_partial'                 => $this->variables->get( 'per_partial' ),
			'site_id'                     => $this->variables->get( 'site_id' ),
			'create_order'                => $this->variables->get( 'create_order' ),
		);

		$args = apply_filters( 'e20r_import_arguments', $args );

		$this->error_log->debug( "Path to import file: {$import_dir}/{$filename}" );

		try {
			$results = $this->csv->process( "{$import_dir}/{$filename}", $args );
		} catch ( \Exception $e ) {
			$this->error_log->debug( 'Import Error: ' . $e->getMessage() );
			$this->error_log->add_error_msg( sprintf( 'Error: %s', $e->getMessage() ) );
		}

		$status        = null;
		$error_log_msg = null;

		if ( file_exists( $this->variables->get( 'logfile_path' ) ) ) {
			$error_log_msg = sprintf(
				// translators: %1$s - HTML, %2$s - HTML
				__( ', please %1$scheck the error log%2$s', 'pmpro-import-members-from-csv' ),
				sprintf( '<a href="%s">', esc_url_raw( $this->variables->get( 'logfile_url' ) ) ),
				'</a>'
			);
		}

		if ( isset( $_REQUEST['import'] ) ) {
			$error_log_msg = '';

			if ( file_exists( $this->variables->get( 'logfile_path' ) ) ) {
				$error_log_msg = sprintf(
					// translators: %1$s - HTML, %2$s - HTML
					__( ', please %1$scheck the error log%2$s', 'pmpro-import-members-from-csv' ),
					sprintf( '<a href="%s">', esc_url_raw( $this->variables->get( 'logfile_url' ) ) ),
					'</a>'
				);
			}

			switch ( $_REQUEST['import'] ) {
				case 'file':
					$status = sprintf(
						'<div class="error"><p><strong>%s</strong></p></div>',
						__( 'Error during file upload.', 'pmpro-import-members-from-csv' )
					);
					break;
				case 'data':
					$status = sprintf(
						'<div class="error"><p><strong>%s</strong></p></div>',
						__(
							'Cannot extract data from uploaded file or no file was uploaded.',
							'pmpro-import-members-from-csv'
						)
					);
					break;
				case 'success':
					$status = sprintf(
						'<div class="updated"><p><strong>%s</strong></p></div>',
						__( 'Member import was successful.', 'pmpro-import-members-from-csv' )
					);
					break;
				default:
					$status = null;
			}
		}

		$buffered_text  = ob_get_clean();
		$display_errors = $this->variables->get( 'display_errors' );

		// No users imported (or done)
		if ( empty( $results['user_ids'] ) ) {

			$file = basename( $filename );

			//Clear the file
			unlink( "{$import_dir}/{$filename}" );

			//Clear position
			delete_option( "e20rcsv_{$file}" );

			// Delete the transient storing the file name
			delete_transient( 'e20r_import_filename' );
			// phpcs:ignore
			$this->error_log->debug('Display Errors = ' . print_r( $display_errors, true ) );

			wp_send_json_success(
				array(
					'status'         => true,
					'message'        => $status,
					'display_errors' => ( ! empty( $display_errors ) ? $display_errors : null ),
				)
			);

		} elseif ( ! empty( $results['errors'] ) ) {

			/**
			 * @var string[] $msgs
			 */
			$msgs = array();

			/**
			 * @var \WP_Error $error
			 */
			foreach ( $results['errors'] as $error ) {

				if ( ! empty( $error ) ) {
					// phpcs:ignore
					$this->error_log->debug( 'Type of error info: ' . print_r( $error, true ) );
					$msgs[] = $error->get_error_message();
				}
			}

			wp_send_json_error(
				array(
					'status'         => false,
					'message'        => sprintf( __( "Error during import (# of errors: %1\$d):\n%2\$s", 'pmpro-import-members-from-csv' ), count( $msgs ), implode( "\n", $msgs ) ),
					'display_errors' => ( ! empty( $display_errors ) ? $display_errors : null ),
				)
			);
		} else {

			/**
			 * @param string[] $msgs
			 */
			$msgs = array();

			if ( ! empty( $results['warnings'] ) ) {

				/**
				 * @var \WP_Error $error
				 */
				foreach ( $results['warnings'] as $error ) {
					$msgs[] = $error->get_error_message();
				}
				// phpcs:ignore
				$this->error_log->debug( 'Warnings: ' . print_r( $msgs, true ) );
			}

			$status_msg = sprintf(
				// translators: %s - generated string of '.'s
				__( "Imported %s\n", 'pmpro-import-members-from-csv' ),
				str_pad( '', count( $results['user_ids'] ), '.' )
			);

			if ( ! empty( $msgs ) ) {
				$status_msg .= implode( "\n", $msgs ) . "\n";
			}

			wp_send_json_success(
				array(
					'status'         => true,
					'message'        => $status_msg,
					'display_errors' => ( ! empty( $display_errors ) ? $display_errors : null ),
				)
			);
		}
	}

	private function remove_file( $file_name ) {

	}
}
