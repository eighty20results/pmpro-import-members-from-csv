<?php
/**
 * Copyright (c) 2018-2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Import_Members\Modules\PMPro;

use E20R\Import_Members\Data;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import_Members;
use E20R\Import_Members\Variables;
use E20R\Utilities\Licensing\Licensing;
use WP_Error;

class Import_Sponsors {

	/**
	 * Instance of the Error_Log class
	 *
	 * @var Error_Log|null $error_log
	 */
	private $error_log = null;

	/**
	 * Instance of the Data class
	 *
	 * @var Data|null $data
	 */
	private $data = null;

	/**
	 * Instance of the Variables class
	 *
	 * @var Variables|null $variables
	 */
	private $variables = null;

	/**
	 * Import_Sponsors constructor.
	 *
	 * Hide/protect the constructor for this class (singleton pattern)
	 *
	 * @access private
	 */
	public function __construct() {
		$this->error_log = new Error_Log(); // phpcs:ignore
		$this->data      = new Data();
		$this->variables = new Variables();
	}

	/**
	 * Load action and filter handlers for the Import_Sponsors class
	 */
	public function load_actions() {
		add_action( 'e20r_import_load_licensed_modules', array( $this, 'load_sponsor_import' ) );
		add_filter( 'e20r_import_supported_fields', array( $this, 'load_fields' ), 1, 1 );
	}

	/**
	 * Load all supported import field (column) names for the PMPro module
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function load_fields( $fields ) {

		// Configure fields for PMPro import
		$fields = array_merge_recursive(
			$fields,
			array(
				'pmprosm_sponsor' => null,
				'pmprosm_seats'   => null,
			)
		);

		return apply_filters( 'e20r_import_modules_pmpro_headers', $fields );
	}

	/**
	 * Load licensed module(s) if license is active
	 */
	public function load_sponsor_import() {
		$check = new \ReflectionMethod( 'E20R\Utilities\Licensing\Licensing', '__construct' );

		if ( false === $check->isPrivate() ) {
			$licensing   = new Licensing( Import_Members::E20R_LICENSE_SKU );
			$is_licensed = $licensing->is_licensed( Import_Members::E20R_LICENSE_SKU, false );
		} else {
			// @phpstan-ignore-next-line
			$is_licensed = Licensing::is_licensed( Import_Members::E20R_LICENSE_SKU, false );
		}

		if ( true === $is_licensed ) {
			add_action( 'e20r_after_user_import', array( $this, 'maybe_add_sponsor_info' ), 100, 3 );
		}
	}

	/**
	 * Add PMPro Sponsored Memberships add-on sponsor ID and info for the (sponsored) user if applicable
	 *
	 * @param int   $user_id
	 * @param array $user_data
	 * @param array $user_meta
	 */
	public function maybe_add_sponsor_info( $user_id, $user_data, $user_meta ) {

		global $e20r_import_err;
		global $active_line_number;

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		// Is there sponsor info to import?
		if ( empty( $user_data['pmprosm_sponsor'] ) ) {
			return;
		}

		$delayed_sponsor_link = $this->variables->get( 'delayed_sponsor_link' );

		if ( ! is_array( $delayed_sponsor_link ) ) {
			$delayed_sponsor_link = array();
		}

		$this->error_log->debug( "Should we import the sponsor info for this user {$user_id}?" );

		try {
			$sponsor = new Sponsor( $user_meta['pmprosm_sponsor'] );
		} catch ( \Exception $e ) {

			$delayed_sponsor_link[ $user_id ]                                        = $user_meta['pmprosm_sponsor'];
			$e20r_import_err[ "sponsor_not_found_{$user_id}_{$active_line_number}" ] = new WP_Error(
				'e20r_im_sponsor',
				sprintf(
					__(
						// translators: %d - WP_User ID for the sponsor (user), %2$s - Sponsored user's key (email or ID)
						"WP_User record not found for user %1\$d's sponsor (key: %2\$s)",
						'pmpro-import-members-from-csv'
					),
					$user_id,
					$user_data['pmprosm_sponsor']
				)
			);
		}

		if ( ! empty( $sponsor ) ) {
			$sponsor->set(
				'membership_level',
				pmpro_getMembershipLevelForUser(
					$sponsor->get( 'user', 'ID' )
				),
				null
			);

			$sponsors_level = $sponsor->get( null, 'membership_level' );

			if ( empty( $sponsors_level ) ) {
				$e20r_import_err[ "sponsor_not_member_{$user_id}_{$active_line_number}" ] = new WP_Error(
					'e20r_im_sponsor',
					sprintf(
						__(
							"Error: Sponsor (%1\$s) doesn't have an active membership level and can't sponsor member with ID %1\$d",
							'pmpro-import-members-from-csv'
						),
						$sponsor->get(
							'user',
							'user_email'
						),
						$user_id
					)
				);
			}

			$status = $this->maybe_link_to_sponsor( $user_id, $sponsor );

			if ( false === $status ) {
				$delayed_sponsor_link[ $user_id ] = $sponsor->get( 'user', 'ID' );
			}
		}

		$this->variables->set( 'delayed_sponsor_link', $delayed_sponsor_link );
	}

	/**
	 * Attempt to save the user ID's sponsor info
	 *
	 * @param int     $sponsored_user_id
	 * @param Sponsor|\WP_User $sponsor
	 * @param bool    $last_try
	 *
	 * @return WP_Error|bool
	 */
	public function maybe_link_to_sponsor( $sponsored_user_id, $sponsor, $last_try = false ) {

		global $e20r_import_err;
		global $wpdb;

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		if ( ! function_exists( 'pmprosm_getCodeByUserID' ) ) {
			$e20r_import_err['pmpro_plugin_not_installed'] = new WP_Error(
				'pmp_im_sponsor',
				sprintf(
					__(
						'The PMPro Sponsored Members add-on is not active! To import sponsored members, this add-on needs to be installed and activated.',
						'pmpro-import-members-from-csv'
					)
				)
			);
		}

		// Get user info and ensure they have a current membership level
		$sponsored_user = get_userdata( $sponsored_user_id );

		// @phpstan-ignore-next-line
		$sponsored_user->membership_level = pmpro_getMembershipLevelForUser( $sponsored_user_id, true );
		$delayed_sponsor_link             = $this->variables->get( 'delayed_sponsor_link' );
		$status                           = true;

		// Have a sponsor to process
		if ( empty( $sponsor ) ) {
			$e20r_import_err[ "sponsor_not_found_{$sponsored_user_id}" ] = new WP_Error(
				'pmp_im_sponsor',
				sprintf(
					// translators: %1$d - ID of sponsored user
					__( 'Sponsor not found for ID %1$d', 'pmpro-import-members-from-csv' ),
					$sponsored_user_id
				)
			);
		}

		$sponsor_id       = $sponsor->get( 'user', 'ID' );
		$sponsor_level_id = $sponsor->get( 'membership', 'id' );

		$this->error_log->debug( "Found sponsor {$sponsor_id} for user {$sponsored_user_id}" );

		if ( empty( $sponsor_level_id ) ) {
			$e20r_import_err[ "invalid_sponsor_level_{$sponsored_user_id}" ] = new WP_Error(
				'pmp_im_sponsor',
				sprintf(
					// translators: %d - The user ID of the sponsored user
					__(
						'Error: The Sponsor for user with ID %d doesn\'t have a valid Membership level!',
						'pmpro-import-members-from-csv'
					),
					$sponsored_user_id
				)
			);

			return false;
		}

		//Make sure the sponsor has a discount code
		$code_id = pmprosm_getCodeByUserID( $sponsor_id ); // @phpstan-ignore-line

		$this->error_log->debug( "Got sponsor code {$code_id} for sponsor {$sponsor_id}" );

		// Get # of seats for this sponsor ( saved in user meta )
		$seats = get_user_meta( $sponsor_id, 'pmprosm_seats', true );

		$this->error_log->debug( 'Found ' . ( ! empty( $seats ) ? $seats : 'unlimited (?!?)' ) . " seats for {$sponsor_id}" );

		$uses = null;

		if ( ! empty( $seats ) ) {
			$uses = $seats;
		} else {

			if ( ! $last_try ) {

				$this->error_log->debug( "Sponsor doesn't have seats defined yet... Try again later (in case the sponsor gets imported later)" );
				$delayed_sponsor_link[ $sponsored_user_id ] = $sponsor_id;
				$this->variables->set( 'delayed_sponsor_link', $delayed_sponsor_link );

				return false;
			} else {

				$this->error_log->add_error_msg(
					__( 'Sponsor(s) may have unlimited seats assigned... Check for warnings in the log!', 'pmpro-import-members-from-csv' ),
					'warning'
				);

				$sponsor_email = $sponsor->get( 'user', 'user_email' );

				$e20r_import_err[ "unlimited_seats_{$sponsor_id}" ] = new WP_Error(
					'pmp_im_sponsor',
					sprintf(
						// translators: %s - the email-address for the PMPro Sponsored members sponsor
						__( 'Warning: Sponsor (%s) may have been given an unlimited number of seats.', 'pmpro-import-members-from-csv' ),
						! empty( $sponsor_email ) ? $sponsor_email : __( 'Not found!', 'pmpro-import-members-from-csv' )
					)
				);
			}
		}

		//Make sure the code is still around in the DB
		if ( ! empty( $code_id ) ) {

			$this->error_log->debug( "Looking for {$code_id} in DB" );

			$code_exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT dc.id FROM {$wpdb->pmpro_discount_codes} AS dc WHERE dc.id = %d", $code_id )
			);

			if ( empty( $code_exists ) ) {
				$code_id = false;
			}
		}

		//if no code, create a new one
		if ( empty( $code_id ) ) {
			$this->error_log->debug( "Have to create a sponsor code for {$sponsor_id}" );

			// @phpstan-ignore-next-line
			$code_id = pmprosm_createSponsorCode( $sponsor_id, $sponsor_level_id, $uses );
		}

		$this->error_log->debug( "Importing with Code {$code_id} for user {$sponsored_user_id}" );

		if ( empty( $code_id ) ) {

			$e20r_import_err[ "no_valid_sponsor_code_{$sponsor_id}" ] = new WP_Error(
				'pmp_im_sponsor',
				sprintf(
					// translators: %d - User ID of the sponsored user
					__( "Sponsor doesn't have a sponsor code (for ID %d)", 'pmpro-import-members-from-csv' ),
					$sponsored_user_id
				)
			);

			return false;
		}

		$update = array(
			'code_id'       => $code_id,
			'user_id'       => $sponsored_user_id,
			'membership_id' => $sponsored_user->membership_level->id, // @phpstan-ignore-line
			'status'        => 'active',
		);

		$this->error_log->debug( 'Will update: ' . print_r( $update, true ) ); // phpcs:ignore

		//update code for sponsored user
		if ( false === $wpdb->replace(
			$wpdb->pmpro_memberships_users,
			$update,
			array( '%d', '%d', '%d', '%s' )
		)
		) {
			$e20r_import_err[ "update_code_{$sponsored_user_id}" ] = new WP_Error(
				'pmp_im_sponsor',
				sprintf(
					// translators: %d - User ID of the sponsored user record
					__( 'Could not update sponsor code info for %d', 'pmpro-import-members-from-csv' ),
					$sponsored_user_id
				)
			);
		}

		$this->error_log->debug( "Updated member record for {$sponsored_user_id} with sponsor info" );

		// @phpstan-ignore-next-line
		pmprosm_addDiscountCodeUse( $sponsored_user_id, $sponsored_user->membership_level->ID, $code_id );

		$this->error_log->debug( "Updated the usage of {$code_id}" );

		if ( empty( $e20r_import_err ) ) {
			$status = true;

			// Clear the user meta
			delete_user_meta( $sponsored_user_id, 'pmprosm_sponsor' );

		} else {
			$this->error_log->log_errors(
				$e20r_import_err,
				$this->variables->get( 'logfile_path' ),
				$this->variables->get( 'logfile_url' )
			);
			$status = false;
		}

		return $status;
	}

	/**
	 * Attempt to re-link sponsored users after everything is done...
	 */
	public function trigger_sponsor_updates() {

		$error_loading_sponsor = false;

		if ( false === Import_Members::is_pmpro_active() ) {
			$errors_loading_sponsor = true;
			$this->error_log->add_error_msg( 'The PMPro plugin is inactive for this WordPress instance. Cannot import sponsor info!' );

			return false;
		}

		$delayed_sponsor_link = $this->variables->get( 'delayed_sponsor_link' );

		$this->error_log->debug( 'Attempt to link sponsors for ' . count( $delayed_sponsor_link ) . ' users' );

		if ( empty( $delayed_sponsor_link ) ) {
			return true;
		}

		foreach ( $delayed_sponsor_link as $user_id => $sponsor_id ) {

			$sponsor = $this->data->get_user_info( $sponsor_id );

			if ( empty( $sponsor ) ) {

				$this->error_log->debug( "Sponsor's user account (Sponsor ID: {$sponsor_id}) not found!" );

				$error_loading_sponsor = true;
				continue;
			}

			// @phpstan-ignore-next-line
			$sponsor->membership_level = pmpro_getMembershipLevelForUser( $sponsor_id, true );

			if ( empty( $sponsor->membership_level ) ) {
				$this->error_log->debug( "Sponsor's membership level not found!" );
				$error_loading_sponsor = true;
				continue;
			}

			$status = $this->maybe_link_to_sponsor( $user_id, $sponsor, true );

			if ( is_wp_error( $status ) ) {
				$this->error_log->add_error_msg( $status->get_error_message(), 'warning' );
			}

			unset( $delayed_sponsor_link[ $user_id ] );
		}

		if ( false === $error_loading_sponsor ) {
			$this->variables->set( 'delayed_sponsor_link', array() );
			delete_option( 'e20r_link_for_sponsor' );
		} else {
			$this->variables->set( 'delayed_sponsor_link', $delayed_sponsor_link );
		}

		return true;
	}

	/**
	 * Hide/protect the __clone() magic method for this class (singleton pattern)
	 *
	 * @access private
	 */
	private function __clone() {
	}
}

add_action( 'e20r_import_load_licensed_modules', array( new Import_Sponsors(), 'load_actions' ) );
