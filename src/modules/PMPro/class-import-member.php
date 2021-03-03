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

namespace E20R\Paid_Memberships_Pro\Import_Members\Modules\PMPro;


use E20R\Paid_Memberships_Pro\Import_Members\Data;
use E20R\Paid_Memberships_Pro\Import_Members\Email_Templates;
use E20R\Paid_Memberships_Pro\Import_Members\Error_Log;
use E20R\Paid_Memberships_Pro\Import_Members\Validate_Data;
use E20R\Paid_Memberships_Pro\Import_Members\Variables;
use E20R\Paid_Memberships_Pro\Import_Members\Import_Members_From_CSV;


class Import_Member {
	
	/**
	 * The instance of the Import_Sponsors class (singleton pattern)
	 *
	 * @var null|Import_Member $instance
	 */
	private static $instance = null;
	
	/**
	 * Import_Member constructor.
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
	 * @return Import_Member|null
	 */
	public static function get_instance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Add action & filter handlers (run early)
	 */
	public function load_actions() {
		add_action( 'e20r-pre-user-import', array( $this, 'pre_member_import' ), - 1 );
		add_action( 'e20r-import-post-user-import', array( $this, 'import_membership_info' ), - 1, 2 );
	}
	
	/**
	 * Delete all import_ meta fields before an import in case the user has been imported in the past.
	 *
	 * @param array $user_data
	 * @param array $user_meta
	 */
	public function pre_member_import( $user_data, $user_meta ) {
		
		// Init variables
		$user   = false;
		$target = null;
		
		//Get user by ID
		if ( isset( $user_data['ID'] ) ) {
			$user = get_user_by( 'ID', $user_data['ID'] );
		}
		
		// That didn't work, now try by login value or email
		if ( empty( $user->ID ) ) {
			
			if ( isset( $user_data['user_login'] ) ) {
				$target = 'login';
				
			} else if ( isset( $user_data['user_email'] ) ) {
				$target = 'email';
			}
			
			if ( ! empty( $target ) ) {
				$user = get_user_by( $target, $user_data["user_{$target}"] );
			} else {
				return; // Exit quietly
			}
		}
		
		// Clean up if we found a user (delete the import_ usermeta)
		if ( ! empty( $user->ID ) ) {
			
			foreach ( $this->pmpro_fields as $field_name => $value ) {
				delete_user_meta( $user->ID, "imported_{$field_name}" );
			}
		}
	}
	
	/**
	 * After the new user was created, import PMPro membership metadata
	 *
	 * @param int   $user_id
	 * @param array $settings
	 *
	 * @since 2.9 BUG FIX: A little too silent when the imported file is mis-configured
	 * @since 2.9 BUG FIX: MS Excel causing trouble w/first column import values (Improved UTF BOM handling)
	 * @since 2.9 ENHANCEMENT: Improved error logging/handling for typical import file errors
	 * @since 2.9 ENHANCEMENT: Send Welcome Email template when adding user to membership level ("Welcome imported
	 *        user" email located in emails/ directory)
	 * @since 2.9 BUG FIX: Typo in warning/error messages
	 * @since 2.22 ENHANCEMENT: Move data checks to own method (validate_membership_data())
	 *
	 */
	public function import_membership_info( $user_id, $settings = array() ) {
		
		global $wpdb;
		global $e20r_import_err;
		global $active_line_number;
		
		$error_log = Error_Log::get_instance();
		$variables = Variables::get_instance();
		$data      = Data::get_instance();
		$sponsors  = Import_Sponsors::get_instance();
		$emails    = Email_Templates::get_instance();
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		$has_error              = false;
		$membership_in_the_past = false;
		$fields                 = $variables->get( 'fields' );
		
		// Define table names
		$pmpro_member_table  = "{$wpdb->prefix}pmpro_memberships_users";
		$pmpro_dc_table      = "{$wpdb->prefix}pmpro_discount_codes";
		$pmpro_dc_uses_table = "{$wpdb->prefix}pmpro_discount_codes_uses";
		
		$current_blog_id = get_current_blog_id();
		
		$error_log->debug( "Current blog ID: {$current_blog_id}" );
		
		wp_cache_delete( $user_id, 'users' );
		$user = get_userdata( $user_id );
		
		if ( empty( $user ) ) {
			$e20r_import_err["no_user_found_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', sprintf( __( "Unable to locate user with expected user ID of %d (Skipped!)", Import_Members_From_CSV::plugin_slug ), $user_id ) );
			$has_error                                              = true;
		}
		
		// Generate PMPro specific member value(s)
		foreach ( $fields as $var_name => $field_value ) {
			$fields[ $var_name ] = isset( $user->{"imported_{$var_name}"} ) ? $user->{"imported_{$var_name}"} : $fields[ $var_name ];
		}
		
		$error_log->debug( "Adding membership info for: {$user_id}" );
		
		// Set site ID and custom table names for the multi site configs
		if ( is_multisite() ) {
			
			$error_log->debug( "Importing user on multi-site..." );
			switch_to_blog( $variables->get( 'site_id' ) );
			
			$pmpro_member_table  = "{$wpdb->base_prefix}pmpro_memberships_users";
			$pmpro_dc_table      = "{$wpdb->base_prefix}pmpro_discount_codes";
			$pmpro_dc_uses_table = "{$wpdb->base_prefix}pmpro_discount_codes_uses";
		}
		
		$has_error = apply_filters( 'e20r-import-members-validate-field-data', $has_error, $user_id, $fields );
		$error_log->debug( "Error while validating data for {$user_id}? " . ( $has_error ? 'Yes' : 'No' ) );
		
		$import_member_data = ( ! $has_error );
		
		if ( true === $has_error ) {
			$import_member_data = apply_filters( 'e20r-import-members-continue-member-import', $import_member_data, $user_id, $fields );
			$error_log->debug(
				"Should we continue importing member data in spite of error? " .
				( $import_member_data ? 'Yes' : 'No' )
			);
		}
		
		$error_log->debug( "Data for {$user_id} has been validated..." );
		
		$welcome_warning = $variables->get( 'welcome_mail_warning' );
		$send_email      = $variables->get( 'send_welcome_email' );
		/**
		 * @since v2.41 - BUG FIX: Didn't warn when membership_status wasn't included but user
		 * wanted to send Welcome email
		 */
		if ( empty( $welcome_warning ) &&
		     true == $send_email &&
		     ( ! isset( $fields['membership_status'] ) ||
		       ( isset( $fields['membership_status'] ) && 'active' != trim( $fields['membership_status'] ) )
		     )
		) {
			$welcome_warning = __(
				'Cannot send Welcome email to members who did not get imported as \'active\' members!',
				Import_Members_From_CSV::plugin_slug
			);
			
			$error_log->debug( $welcome_warning );
			$error_log->add_error_msg( $welcome_warning, 'warning' );
		}
		
		// Proceed to import member data if the data is correct
		if ( false === $import_member_data ) {
			$error_log->debug( "Not going to import member data for {$user_id}!!!" );
			
			return;
		}
		
		//Look up the discount code when included
		if ( ! empty( $fields['membership_discount_code'] ) && empty( $fields['membership_code_id'] ) ) {
			
			$fields['membership_code_id'] = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT dc.id
                              FROM {$pmpro_dc_table} AS dc
                              WHERE dc.code = %s
                              LIMIT 1",
					$fields['membership_discount_code']
				)
			);
		}
		
		//Change membership level
		if ( ( is_numeric( $fields['membership_id'] ) && 0 <= $fields['membership_id'] ) || ! empty( $fields['membership_id'] ) ) {
			
			// Cancel previously existing (active) memberships (Should support MMPU add-on)
			// without triggering cancellation emails, etc
			if ( true === $variables->get( 'deactivate_old_memberships' ) ) {
				
				$error_log->debug( "Attempting to deactivate old membership(s)" );
				
				// Update all currently active memberships with the specified ID for the specified user
				if ( false === ( $updated = $wpdb->update( $pmpro_member_table, array( 'status' => 'admin_cancelled' ), array(
						'user_id'       => $user_id,
						'membership_id' => $fields['membership_id'],
						'status'        => 'active',
					) ) ) ) {
					
					$e20r_import_err["old_membership_error_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', sprintf(
						__( 'Unable to cancel old membership level (ID: %d) for user (ID: %d)', Import_Members_From_CSV::plugin_slug ),
						$fields['membership_id'],
						$user_id
					) );
				}
			}
			
			$custom_level = array(
				'user_id'         => $user_id,
				'membership_id'   => $fields['membership_id'],
				'code_id'         => ! empty( $fields['membership_code_id'] ) ? $fields['membership_code_id'] : 0,
				'initial_payment' => ! empty( $fields['membership_initial_payment'] ) ? $fields['membership_initial_payment'] : '',
				'billing_amount'  => ! empty( $fields['membership_billing_amount'] ) ? $fields['membership_billing_amount'] : '',
				'cycle_number'    => ! empty( $fields['membership_cycle_number'] ) ? $fields['membership_cycle_number'] : '',
				'cycle_period'    => ! empty( $fields['membership_cycle_period'] ) ? $fields['membership_cycle_period'] : 'Month',
				'billing_limit'   => ! empty( $fields['membership_billing_limit'] ) ? $fields['membership_billing_limit'] : '',
				'trial_amount'    => ! empty( $fields['membership_trial_amount'] ) ? $fields['membership_trial_amount'] : '',
				'trial_limit'     => ! empty( $fields['membership_trial_limit'] ) ? $fields['membership_trial_limit'] : '',
				'startdate'       => ! empty( $fields['membership_startdate'] ) ? $fields['membership_startdate'] : null,
			);
			
			// Add the enddate
			$custom_level['enddate'] = ! empty( $fields['membership_enddate'] ) ? $fields['membership_enddate'] : null;
			
			// Set the status of the membership
			if ( ! empty( $fields['membership_status'] ) ) {
				$custom_level['status'] = $fields['membership_status'];
			}
			
			/**
			 * @since v2.50 - BUG FIX: Don't deactivate old levels (here)
			 */
			add_filter( 'pmpro_deactivate_old_levels', '__return_false', 999 );
			add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false', 999 );
			
			/**
			 * @since v2.60 - Fatal error when sponsored members add-on is active
			 */
			remove_action( 'pmpro_after_change_membership_level', 'pmprosm_pmpro_after_change_membership_level', 10 );
			
			// Update the level
			$updated_level = pmpro_changeMembershipLevel( $custom_level, $user_id, 'cancelled' );
			
			remove_filter( 'pmpro_deactivate_old_levels', '__return_false', 999 );
			remove_filter( 'pmpro_cancel_previous_subscriptions', '__return_false', 999 );
			add_action( 'pmpro_after_change_membership_level', 'pmprosm_pmpro_after_change_membership_level', 10, 2 );
			
			if ( false === $updated_level ) {
				
				$e20r_import_err["user_level_{$active_line_number}"] = new \WP_Error(
					'e20r_im_member',
					sprintf( __(
						'Unable to configure membership level (ID: %d ) for user (user id: %d)',
						Import_Members_From_CSV::plugin_slug
					),
						$fields['membership_id'],
						$user_id
					)
				);
				
			}
			
			if ( ! empty( $custom_level['status'] ) && $custom_level['status'] != 'inactive' ) {
				// Get the most recently added column
				$record_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT mt.id
                                      FROM {$pmpro_member_table} AS mt
                                      WHERE mt.user_id = %d AND mt.membership_id = %d AND mt.status = %s
                                      ORDER BY mt.id DESC LIMIT 1",
						$user_id,
						$custom_level['membership_id'],
						$custom_level['status']
					)
				);
			} else {
				$record_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT mt.id
                                      FROM {$pmpro_member_table} AS mt
                                      WHERE mt.user_id = %d AND mt.membership_id = %d
                                      ORDER BY mt.id DESC LIMIT 1",
						$user_id,
						$custom_level['membership_id']
					)
				);
			}
			
			// If membership ended in the past, make it inactive for now
			if ( ( "inactive" == strtolower( $fields['membership_status'] ) && ! empty( $record_id ) ) ||
			     ( ! empty( $fields['membership_enddate'] ) &&
			       strtoupper( $fields['membership_enddate'] ) != "NULL" &&
			       strtotime( $fields['membership_enddate'], current_time( 'timestamp' ) ) < current_time( 'timestamp' )
			     )
			) {
				
				/**
				 * @since 2.11 - BUG FIX: Didn't handle 'inactive' status with no membership_enddate supplied
				 */
				if ( empty( $fields['membership_enddate'] ) ) {
					$fields['membership_enddate'] = date( 'Y-m-d h:i:s', current_time( 'timestamp' ) );
				}
				
				if ( false !== $wpdb->update(
						$pmpro_member_table,
						array( 'status' => 'inactive', 'enddate' => $fields['membership_enddate'] ),
						array(
							'id'            => $record_id,
							'user_id'       => $user_id,
							'membership_id' => $fields['membership_id'],
						),
						array( '%s', '%s' ),
						array( '%d', '%d', '%d' )
					)
				) {
					$membership_in_the_past = true;
				} else {
					$e20r_import_err["upd_error_status_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', sprintf( __( 'Unable to set \'inactive\' membership status/date for user (ID: %d) with membership level ID %d', Import_Members_From_CSV::plugin_slug ), $user_id, $fields['membership_id'] ) );
				}
			}
			
			if ( 'active' == strtolower( $fields['membership_status'] ) &&
			     ( empty( $fields['membership_enddate'] ) ||
			       'NULL' == strtoupper( $fields['membership_enddate'] ) ||
			       strtotime( $fields['membership_enddate'], current_time( 'timestamp' ) ) >= current_time( 'timestamp' ) )
			) {
				
				if ( false === $wpdb->update(
						$pmpro_member_table,
						array( 'status' => 'active', 'enddate' => $fields['membership_enddate'] ),
						array(
							'id'            => $record_id,
							'user_id'       => $user_id,
							'membership_id' => $fields['membership_id'],
						),
						array( '%s', '%s' ),
						array( '%d', '%d', '%d' )
					) ) {
					$e20r_import_err["import_status_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', sprintf( __( 'Unable to activate membership for user (ID: %d) with membership level ID %d', Import_Members_From_CSV::plugin_slug ), $user_id, $fields['membership_id'] ) );
				}
			}
		}
		
		$this->maybe_add_order( $user_id, $fields, $membership_in_the_past );
		
		do_action( 'e20r-import-trigger-membership-module-imports', $user_id, $fields );
		
		$error_log->debug( "Should we send welcome email ({$send_email})? " . ( $send_email ? 'Yes' : 'No' ) );
		$emails->maybe_send_email( $user );
		
		// Log errors to log file
		if ( ! empty( $e20r_import_err ) ) {
			$error_log->log_errors( $e20r_import_err );
		}
		
		// Update the error status
		if ( true === $has_error ) {
			update_option( 'e20r_import_errors', $has_error );
		}
		
		if ( is_multisite() ) {
			switch_to_blog( $current_blog_id );
		}
	}
	
	/**
	 * Add order for the user if applicable
	 *
	 * @param int   $user_id
	 * @param array $fields
	 * @param bool  $membership_in_the_past
	 */
	public function maybe_add_order( $user_id, $fields, $membership_in_the_past ) {
		
		global $wpdb;
		global $e20r_import_err;
		global $active_line_number;
		
		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}
		
		$variables = Variables::get_instance();
		$validate  = Validate_Data::get_instance();
		$error_log = Error_Log::get_instance();
		$data      = Data::get_instance();
		$user      = get_user_by( 'ID', $user_id );
		
		$pmpro_dc_uses_table = "{$wpdb->base_prefix}pmpro_discount_codes_uses";
		
		// Add a PMPro order record so integration with gateway doesn't cause surprises
		if ( true === $variables->get( 'create_order' ) || (
				! empty( $fields['membership_subscription_transaction_id'] ) && ! empty( $fields['membership_gateway'] )
			)
		) {
			
			$error_log->debug( "Adding PMPro order for {$fields['membership_id']}/{$user_id}" );
			
			$default_gateway     = Import_Members_From_CSV::is_pmpro_active() ? pmpro_getGateway() : $fields['membership_gateway'];
			$default_environment = Import_Members_From_CSV::is_pmpro_active() ? pmpro_getOption( "gateway_environment" ) : $fields['membership_gateway_environment'];
			
			if ( false === $data->does_table_exist( 'pmpro_membership_orders' ) ) {
				$error_log->add_error_msg(
					sprintf(
						__( 'Error: table %s does not exists in the database!', Import_Members_From_CSV::plugin_slug ),
						'pmpro_membership_orders'
					)
				);
				
				return false;
			}
			
			/**
			 * Load order table columns for processing
			 */
			$order_fields = $data->get_table_info( 'pmpro_membership_orders' );
			
			if ( empty( $fields['membership_initial_payment'] ) && empty( $fields['membership_billing_amount'] ) && ! empty( $fields['membership_id'] ) ) {
				
				$default_level = pmpro_getLevel( $fields['membership_id'] );
				
				if ( ! empty( $default_level ) ) {
					$fields['membership_initial_payment'] = $default_level->initial_payment;
					$fields['membership_billing_amount']  = $default_level->billing_amount;
					$fields['membership_billing_limit']   = $default_level->billing_limit;
					$fields['membership_cycle_number']    = $default_level->cycle_number;
					$fields['membership_cycle_period']    = $default_level->cycle_period;
				}
			}
			
			$order                 = new \MemberOrder();
			$order->user_id        = $user_id;
			$order->membership_id  = $fields['membership_id'];
			$order->InitialPayment = ! empty( $fields['membership_initial_payment'] ) ? $fields['membership_initial_payment'] : null;
			
			/**
			 * Dynamically provide data for all configured Order fields...
			 */
			foreach ( $order_fields as $full_field_name => $default_value ) {
				
				$process_billing_info = false;
				
				if ( 'membership_id' !== strtolower( $full_field_name ) && 'user_id' !== strtolower( $full_field_name ) ) {
					$field_name = preg_replace( '/membership_/', '', strtolower( $full_field_name ) );
				} else {
					$field_name = strtolower( $full_field_name );
				}
				
				/**
				 * Add billing info (if/when we can)
				 */
				if ( 1 === preg_match( "/billing_(.*)/", $field_name, $matches ) ) {
					
					if ( ! isset( $order->billing ) ) {
						$order->billing = new \stdClass();
					}
					
					if ( ! isset( $order->billing->{$matches[1]} ) ) {
						
						$meta_key = $data->map_billing_field_to_meta( $field_name );
						
						if ( ! empty( $meta_key ) ) {
							$order->billing->{$matches[1]} = get_user_meta( $user_id, $meta_key, true );
							$process_billing_info          = true;
						}
					}
				}
				
				if ( false === $process_billing_info && ( ! isset( $order->{$field_name} ) || ( isset( $order->{$field_name} ) && empty( $order->{$field_name} ) ) ) ) {
					
					// Process payment (amount)
					if ( 'total' === $field_name && ! empty( $fields['membership_initial_payment'] ) ) {
						
						$order->total = $fields['membership_initial_payment'];
						
					} else if ( 'total' === $field_name && (
							empty( $fields['membership_initial_payment'] ) &&
							! empty( $fields['membership_billing_amount'] )
						) ) {
						
						$order->total = $fields['membership_billing_amount'];
						
					} else if ( 'total' !== $field_name ) {
						
						if ( 'status' === $field_name ) {
							$order->{$field_name} = ( $fields[ $full_field_name ] = 'active' ) ? 'success' : 'cancelled';
						} else {
							$order->{$field_name} = ! empty( $fields[ $full_field_name ] ) ? $fields[ $full_field_name ] : null;
						}
					} else {
						
						$error_log->debug( "Warning: {$field_name} will not be processed!!" );
					}
				}
			}
			
			if ( strtolower( $default_gateway ) !== strtolower( $fields['membership_gateway'] ) ) {
				$order->setGateway( $fields['membership_gateway'] );
			}
			
			if ( strtolower( $default_environment ) !== strtolower( $fields['membership_gateway_environment'] ) ) {
				$order->gateway_environment = strtolower( $fields['membership_gateway_environment'] );
			}
			
			if ( true === $membership_in_the_past ) {
				$order->status = "cancelled";
			}
			
			/**
			 * Add MemberOrder billing info if possible
			 */
			if ( ! empty( $order->billing ) ) {
				$order->billing->name = "{$user->first_name} {$user->last_name}";
			}
			
			if ( false === $order->saveOrder() ) {
				$msg = sprintf( __( 'Unable to save order object for user (ID: %d).', 'pmpro-import-members-from-csv' ), $user_id );
				
				$e20r_import_err["order_save_{$user_id}_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', $msg );
				$error_log->debug( $msg );
				
			}
			
			//update timestamp of order?
			if ( ! empty( $fields['membership_timestamp'] ) ) {
				
				if ( true === $validate->date( $fields['membership_timestamp'], 'Y-m-d H:i:s' ) ) {
					$timestamp = strtotime( $fields['membership_timestamp'], current_time( 'timestamp' ) );
				} else {
					$timestamp = is_numeric( $fields['membership_timestamp'] ) ? $fields['membership_timestamp'] : null;
					
					if ( is_null( $timestamp ) ) {
						$e20r_import_err["timestamp_{$user_id}_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', sprintf( __( 'Could not decode timezone value (%s)', Import_Members_From_CSV::plugin_slug ), $fields['membership_timestamp'] ) );
					}
				}
				
				$order->updateTimeStamp(
					date( "Y", $timestamp ),
					date( "m", $timestamp ),
					date( "d", $timestamp ),
					date( "H:i:s", $timestamp )
				);
			}
		}
		
		// Add any Discount Code use for this user
		if ( ! empty( $fields['membership_code_id'] ) && ! empty( $order ) && ! empty( $order->id ) ) {
			
			if ( false === $wpdb->insert(
					$pmpro_dc_uses_table,
					array(
						'code_id'   => $fields['membership_code_id'],
						'user_id'   => $user_id,
						'order_id'  => $order->id,
						'timestamp' => 'CURRENT_TIMESTAMP',
					),
					array( '%d', '%d', '%d', '%s' )
				) ) {
				$e20r_import_err["dc_usage_{$user_id}_{$active_line_number}"] = new \WP_Error( 'e20r_im_member', sprintf( __( 'Unable to set update discount code usage for code (ID: %d ) for user (user/order id: %d/%s)', Import_Members_From_CSV::plugin_slug ), $fields['membership_code_id'], $user_id, $order->id ) );
			}
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