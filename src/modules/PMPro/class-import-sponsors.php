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

namespace E20R\Import_Members\Modules\PMPro;


use E20R\Import_Members\Data;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import_Members;
use E20R\Import_Members\Variables;
use E20R\Utilities\Licensing\Licensing;

class Import_Sponsors {
	
	/**
	 * The instance of the Import_Sponsors class (singleton pattern)
	 *
	 * @var null|Import_Sponsors $instance
	 */
	private static $instance = null;
	
	/**
	 * Import_Sponsors constructor.
	 *
	 * Hide/protect the constructor for this class (singleton pattern)
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Return or instantiate and return a single instance of this class (singleton pattern)
	 *
	 * @return Import_Sponsors|null
	 */
	public static function get_instance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load action and filter handlers for the Import_Sponsors class
	 */
	public function load_actions() {
		
		add_action( 'e20r-import-load-licensed-modules', array( $this, 'load_sponsor_import' ) );
	}
	
	/**
	 * Load licensed module(s) if license is active
	 */
	public function load_sponsor_import() {
		
		if ( true === Licensing::is_licensed( 'import_sponsors' ) ) {
			add_action( 'e20r-after-user-import', array( $this, 'maybe_add_sponsor_info' ) );
		}
	}
	
	/**
	 * Add PMPro Sponsored Memberships add-on sponsor ID and info for the (sponsored) user if applicable
	 *
	 * @param int   $user_id
	 * @param array $fields
	 */
	public function maybe_add_sponsor_info( $user_id, $fields ) {
		
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		$data      = Data::get_instance();
		$error     = Error_Log::get_instance();
		$variables = Variables::get_instance();
		
		// Can we import the sponsor info
		if ( ! empty( $fields['pmprosm_sponsor'] ) ) {
			
			$delayed_sponsor_link = $variables->get( '_delayed_sponsor_link' );
			
			if ( ! is_array( $delayed_sponsor_link ) ) {
				$delayed_sponsor_link = array();
			}
			
			$error->debug( "Should we import the sponsor info for this user {$user_id}?" );
			
			try {
				$sponsor = new Sponsor( $fields['pmprosm_sponsor'] );
			} catch ( \Exception $e ) {
				
				$delayed_sponsor_link[ $user_id ]                                      = $fields['pmprosm_sponsor'];
				$e20r_import_err["sponsor_not_found_{$user_id}_{$active_line_number}"] = new \WP_Error( 'e20r_im_sponsor', sprintf( __( "WP_User record not found for user %d's sponsor (key: %s)", 'pmpro-import-members-from-csv' ), $user_id, $fields['pmprosm_sponsor'] ) );
			}
			
			
			if ( ! empty( $sponsor ) ) {
				
				$sponsor->set( 'membership_level', pmpro_getMembershipLevelForUser( $sponsor->get( 'user', 'ID' ) ), null );
				$sponsors_level = $sponsor->get( null, 'membership_level' );
				
				if ( empty( $sponsors_level ) ) {
					$e20r_import_err["sponsor_not_member_{$user_id}_{$active_line_number}"] = new \WP_Error( 'e20r_im_sponsor', sprintf( __( "Error: Sponsor (%s) doesn't have an active membership level and can't sponsor member with ID %d", 'pmpro-import-members-from-csv' ), $sponsor->get( 'user', 'user_email' ), $user_id ) );
				}
				
				$status = $this->maybe_link_to_sponsor( $user_id, $sponsor );
				
				if ( false === $status ) {
					$delayed_sponsor_link[ $user_id ] = $sponsor->get( 'user', 'ID' );
				}
			}
			
			$variables->set( '_delayed_sponsor_link', $delayed_sponsor_link );
		}
	}
	
	/**
	 * Attempt to save the user ID's sponsor info
	 *
	 * @param int     $sponsored_user_id
	 * @param Sponsor $sponsor
	 * @param bool    $last_try
	 *
	 * @return \WP_Error|bool
	 */
	public function maybe_link_to_sponsor( $sponsored_user_id, $sponsor, $last_try = false ) {
		
		global $e20r_import_err;
		global $wpdb;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		$error     = Error_Log::get_instance();
		$variables = Variables::get_instance();
		
		// Get user info and ensure they have a current membership level
		$sponsored_user                   = get_userdata( $sponsored_user_id );
		$sponsored_user->membership_level = pmpro_getMembershipLevelForUser( $sponsored_user_id, true );
		$error_info                       = null;
		$delayed_sponsor_link             = $variables->get( '_delayed_sponsor_link' );
		$status                           = true;
		
		// Have a sponsor to process
		if ( empty( $sponsor ) ) {
			$e20r_import_err["sponsor_not_found_{$sponsored_user_id}"] = new \WP_Error( 'pmp_im_sponsor', sprintf( __( "Sponsor not found for ID %d", 'pmpro-import-members-from-csv' ), $sponsored_user_id ) );
		}
		
		$sponsor_ID       = $sponsor->get( 'user', 'ID' );
		$sponsor_level_id = $sponsor->get( 'membership', 'id' );
		
		$error->debug( "Found sponsor {$sponsor_ID} for user {$sponsored_user_id}" );
		
		if ( empty( $sponsor_level_id ) ) {
			$e20r_import_err["invalid_sponsor_level_{$sponsored_user_id}"] = new \WP_Error(
				'pmp_im_sponsor',
				sprintf(
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
		$code_id = pmprosm_getCodeByUserID( $sponsor_ID );
		
		$error->debug( "Got sponsor code {$code_id} for sponsor {$sponsor_ID}" );
		
		// Get # of seats for this sponsor ( saved in user meta )
		$seats = get_user_meta( $sponsor_ID, 'pmprosm_seats', true );
		
		$error->debug( "Found " . ( ! empty( $seats ) ? $seats : 'unlimited (?!?)' ) . " seats for {$sponsor_ID}" );
		
		$uses = null;
		
		if ( ! empty( $seats ) ) {
			$uses = $seats;
		} else {
			
			if ( ! $last_try ) {
				
				$error->debug( "Sponsor doesn't have seats defined yet... Try again later (in case the sponsor gets imported later)" );
				$delayed_sponsor_link[ $sponsored_user_id ] = $sponsor_ID;
				$variables->set( '_delayed_sponsor_link', $delayed_sponsor_link );
				
				return false;
			} else {
				
				$error->add_error_msg( __( "Sponsor(s) may have unlimited seats assigned... Check for warnings in the log!", 'pmpro-import-members-from-csv' ), 'warning' );
				
				$sponsor_email = $sponsor->get( 'user', 'user_email' );
				
				$e20r_import_err["unlimited_seats_{$sponsor_ID}"] = new \WP_Error(
					'pmp_im_sponsor',
					sprintf(
						__( "Warning: Sponsor (%s) may have been given an unlimited number of seats.", 'pmpro-import-members-from-csv' ),
						! empty( $sponsor_email ) ? $sponsor_email : __( 'Not found!', 'pmpro-import-members-from-csv' )
					)
				);
			}
		}
		
		//Make sure the code is still around in the DB
		if ( ! empty( $code_id ) ) {
			
			$error->debug( "Looking for {$code_id} in DB" );
			
			$code_exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT dc.id FROM {$wpdb->pmpro_discount_codes} AS dc WHERE dc.id = %d", $code_id )
			);
			
			if ( empty( $code_exists ) ) {
				$code_id = false;
			}
		}
		
		//if no code, create a new one
		if ( empty( $code_id ) ) {
			
			$error->debug( "Have to create a sponsor code for {$sponsor_ID}" );
			
			$code_id = pmprosm_createSponsorCode( $sponsor_ID, $sponsor_level_id, $uses );
		}
		
		$error->debug( "Importing with Code {$code_id} for user {$sponsored_user_id}" );
		
		if ( empty( $code_id ) ) {
			
			$e20r_import_err["no_valid_sponsor_code_{$sponsor_ID}"] = new \WP_Error( 'pmp_im_sponsor', sprintf( __( "Sponsor doesn't have a sponsor code (for ID %d)", 'pmpro-import-members-from-csv' ), $sponsored_user_id ) );
			
			return false;
		}
		
		$update = array(
			'code_id'       => $code_id,
			'user_id'       => $sponsored_user_id,
			'membership_id' => $sponsored_user->membership_level->id,
			'status'        => 'active',
		);
		
		$error->debug( "Will update: " . print_r( $update, true ) );
		
		//update code for sponsored user
		if ( false === $wpdb->replace(
				$wpdb->pmpro_memberships_users,
				$update,
				array( '%d', '%d', '%d', '%s' )
			)
		) {
			$e20r_import_err["update_code_{$sponsored_user_id}"] = new \WP_Error( 'pmp_im_sponsor', sprintf( __( "Could not update sponsor code info for %d", 'pmpro-import-members-from-csv' ), $sponsored_user_id ) );
		}
		
		$error->debug( "Updated member record for {$sponsored_user_id} with sponsor info" );
		
		pmprosm_addDiscountCodeUse( $sponsored_user_id, $sponsored_user->membership_level->ID, $code_id );
		
		$error->debug( "Updated the usage of {$code_id}" );
		
		if ( empty( $error ) ) {
			$status = true;
			
			// Clear the user meta
			delete_user_meta( $sponsored_user_id, 'pmprosm_sponsor' );
			
		} else {
			$error->log_errors( array( $error ) );
			$status = false;
		}
		
		return $status;
	}
	
	/**
	 * Attempt to re-link sponsored users after everything is done...
	 */
	public function trigger_sponsor_updates() {
		
		$error_loading_sponsor = false;
		$errors                = Error_Log::get_instance();
		
		if ( false === Import_Members::is_pmpro_active() ) {
			$errors_loading_sponsor = true;
			$errors->add_error_msg( 'The PMPro plugin is inactive for this WordPress instance. Cannot import sponsor info!' );
			
			return false;
		}
		
		$variables            = Variables::get_instance();
		$data                 = Data::get_instance();
		$delayed_sponsor_link = $variables->get( '_delayed_sponsor_link' );
		
		$errors->debug( "Attempt to link sponsors for " . count( $delayed_sponsor_link ) . " users" );
		
		if ( empty( $delayed_sponsor_link ) ) {
			return;
		}
		
		foreach ( $delayed_sponsor_link as $user_id => $sponsor_id ) {
			
			$sponsor = $data->get_user_info( $sponsor_id );
			
			if ( empty( $sponsor ) ) {
				
				$errors->debug( "Sponsor's user account (Sponsor ID: {$sponsor_id}) not found!" );
				
				$error_loading_sponsor = true;
				continue;
			}
			
			$sponsor->membership_level = pmpro_getMembershipLevelForUser( $sponsor_id, true );
			
			if ( empty( $sponsor->membership_level ) ) {
				
				$errors->debug( "Sponsor's membership level not found!" );
				$error_loading_sponsor = true;
				continue;
			}
			
			$status = $this->maybe_link_to_sponsor( $user_id, $sponsor, true );
			
			if ( is_wp_error( $status ) ) {
				$errors->add_error_msg( $status->get_error_message(), 'warning' );
			}
			
			unset( $delayed_sponsor_link[ $user_id ] );
		}
		
		if ( false === $error_loading_sponsor ) {
			$variables->set( '_delayed_sponsor_link', array() );
			delete_option( 'e20r_link_for_sponsor' );
		} else {
			$variables->set( '_delayed_sponsor_link', $delayed_sponsor_link );
		}
	}
	
	/**
	 * Hide/protect the __clone() magic method for this class (singleton pattern)
	 *
	 * @access private
	 */
	private function __clone() {
	}
}