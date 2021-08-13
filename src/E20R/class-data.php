<?php
/**
 * Copyright (c) 2018-2021 - Eighty / 20 Results by Wicked Strong Chicks.
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

use E20R\Import_Members\Import\CSV;
use E20R\Import_Members\Import_Members;
use Exception;

class Data {

	/**
	 * Instance of the Error_Log class
	 *
	 * @var null|Error_Log $this->error_log
	 */
	private $error_log = null;

	/**
	 * The REQUEST variables/settings for the plugin
	 *
	 * @var null|Variables $this->variables
	 */
	private $variables = null;

	/**
	 * The CSV class instance
	 *
	 * @var null|CSV $csv
	 */
	private $csv = null;

	/**
	 * Singleton Data constructor.
	 *
	 * @access private
	 */
	public function __construct() {
		$this->error_log = new Error_Log(); // phpcs:ignore
		$this->variables = new Variables();
		$this->csv       = new CSV( $this->variables );
	}

	/**
	 * Get the WP_User() object if available
	 *
	 * @param mixed $user_key
	 *
	 * @return bool|null|\WP_User
	 */
	public function get_user_info( $user_key ) {
		$user = null;

		if ( is_email( $user_key ) ) {
			$user = get_user_by( 'email', $user_key );
		}

		if ( is_int( $user_key ) ) {
			$user = get_user_by( 'ID', $user_key );
		}

		if ( ! is_email( $user_key ) && ! is_int( $user_key ) && is_string( $user_key ) ) {
			$user = get_user_by( 'login', $user_key );
		}

		// Add PMPro info as applicable
		if ( ! empty( $user ) ) {
			// @phpstan-ignore-next-line
			$user->membership_level = (
			function_exists( 'pmpro_getMembershipLevelForUser' ) ?
				pmpro_getMembershipLevelForUser( $user->ID, true ) :
				false
			);

			// @phpstan-ignore-next-line
			$user->membership_levels = (
			function_exists( 'pmpro_getMembershipLevelsForUser' ) ?
				pmpro_getMembershipLevelsForUser( $user->ID, true ) :
				false
			);
		}

		return $user;
	}

	/**
	 * Clean up by removing unused imported_* fields
	 *
	 * @param int   $user_id
	 * @param array $settings
	 *
	 * @since 2.9 ENHANCEMENT: Clean up unneeded user metadata
	 */
	public function cleanup( $user_id, $settings ) {
		$fields = $this->variables->get( 'fields' );

		foreach ( $fields as $field_name => $value ) {
			delete_user_meta( $user_id, "imported_{$field_name}" );
		}
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 1.0
	 **/
	public function process_csv() {

		if ( ! ! ( defined( 'DOING_AJAX' ) && DOING_AJAX !== true ) ) {
			return false;
		}

		if ( ! isset( $_REQUEST['e20r-im-import-members-wpnonce'] ) ) {
			return false;
		}

		if ( isset( $_REQUEST['action'] ) && 'import_members_from_csv' !== $_REQUEST['action'] ) {
			return false;
		}

		if ( false === wp_verify_nonce( $_REQUEST['e20r-im-import-members-wpnonce'], 'e20r-im-import-members' ) ) {

			$msg = __( 'Insecure connection attempted!', 'pmpro-import-members-from-csv' );

			$this->error_log->debug( $msg );
			$this->error_log->add_error_msg( $msg, 'error' );

			wp_safe_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );
			exit();
		}

		$this->error_log->debug( 'Processing import request' );

		$settings = $this->variables->get_request_settings();

		$this->error_log->debug( 'Settings from class: ' . print_r( $settings, true ) ); // phpcs:ignore

		if ( ! isset( $_FILES['members_csv']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'import', 'file', wp_get_referer() ) );
			exit();
		}

		// Use AJAX to import?
		if ( true === (bool) $this->variables->get( 'background_import' ) ) {
			$this->error_log->debug( 'Background processing for import' );

			$processed_file_name = $this->csv->pre_process_file();

			if ( false === $processed_file_name ) {
				$this->error_log->debug( 'Error processing CSV file...' );
			}

			$this->variables->set( 'filename', $processed_file_name );

			if ( empty( $processed_file_name ) ) {
				$this->error_log->add_error_msg( __( 'CSV file not selected. Nothing to import!', 'import-members-from-csv' ), 'error' );
				wp_safe_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );
				exit();
			}

			// Redirect to the page to run AJAX
			$url = add_query_arg(
				$settings + array(
					'page'              => 'pmpro-import-members-from-csv',
					'import'            => 'resume',
					'background_import' => true,
					'partial'           => true,
				),
				admin_url( 'admin.php' )
			);

			$this->error_log->debug( "Redirecting to: {$url}" );
			wp_safe_redirect( $url );

		} else {
			try {
				$results = $this->csv->process(
					$this->variables->get( 'filename' ),
					$settings + array(
						'partial'           => false,
						'background_import' => false,
					)
				);
			} catch ( Exception $exception ) {
				// phpcs:ignore
				$this->error_log->debug( 'Caught exception: ' . $exception->getMessage() );
				return false;
			}

			// No users imported?
			if ( ! isset( $results['user_ids'] ) || empty( $results['user_ids'] ) ) {
				wp_safe_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );
				exit();
			}

			// Some users imported?
			if ( isset( $results['errors'] ) || ! empty( $results['errors'] ) ) {
				wp_safe_redirect( add_query_arg( 'import', 'errors', wp_get_referer() ) );
				exit();
			}

			// All users imported? :D
			wp_safe_redirect( add_query_arg( 'import', 'success', wp_get_referer() ) );
		}
		exit();
	}

	/**
	 * @param string $table_name
	 *
	 * @return bool
	 */
	public function does_table_exist( $table_name = 'pmpro_memmberships_users' ) {

		global $wpdb;

		$db_name = defined( 'DB_NAME' ) ? DB_NAME : false;

		if ( false === $db_name ) {
			return false;
		}

		if ( empty( $table_name ) ) {
			return false;
		}

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				$db_name,
				sprintf( '%1$s%2$s', $wpdb->prefix, $table_name )
			)
		);
	}

	/**
	 * Fetch all columns for the specified table name.
	 *
	 * @param string $table_name
	 *
	 * @return array
	 *
	 * @since v2.20 - ENHANCEMENT: Extract data dynamically from PMPro's custom membership users/orders tables
	 */
	public function get_table_info( $table_name = 'pmpro_memberships_users' ) {

		global $wpdb;
		$prefix     = $wpdb->get_blog_prefix();
		$columns    = $wpdb->get_results( "SHOW COLUMNS FROM {$prefix}{$table_name}" ); // phpcs:ignore
		$db_columns = array();

		if ( empty( $columns ) ) {
			return array();
		}

		foreach ( $columns as $col ) {
			$prefix = null;
			switch ( $col->Field ) { // phpcs:ignore
				// Ignore/skip these.
				case 'id':
				case 'modified':
				case 'session_id':
				case 'accountnumber': // Don't risk it (in case users try to import a full card #)
				case 'code': // The order code (need to ignore)
				case 'billing_name':
					//case 'billing_street':
					//case 'billing_city':
					//case 'billing_state':
					//case 'billing_zip':
					//case 'billing_country':
					//case 'billing_phone':
					break; // Skip

				case 'membership_id':
				case 'user_id':
					$db_columns[ $col->Field ] = null; // phpcs:ignore
					break;

				default:
					$db_columns["membership_{$col->Field}"] = null; // phpcs:ignore
			}
		}

		return $db_columns;

	}

	/**
	 * @param string $billing_field_name
	 *
	 * @return string|bool
	 */
	public function map_billing_field_to_meta( $billing_field_name ) {

		$billing_fields = apply_filters(
			'e20r_import_wc_pmpro_billing_field_map',
			array(
				'billing_street'  => 'pmpro_baddress1',
				'billing_city'    => 'pmpro_bcity',
				'billing_state'   => 'pmpro_bstate',
				'billing_zip'     => 'pmpro_bzipcode',
				'billing_country' => 'pmpro_bcountry',
				'billing_phone'   => 'pmpro_bphone',
			)
		);

		/**
		 * Compatibility with PMPro's Import Members from CSV Integration add-on
		 */
		$billing_fields = apply_filters( 'pmpro_import_members_billing_field_map', $billing_fields );

		if ( ! in_array( $billing_field_name, array_keys( $billing_fields ), true ) ) {
			return false;
		}

		return $billing_fields[ $billing_field_name ];
	}
}
