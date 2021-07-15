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
use E20R\Import_Members\Email_Templates;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Validate_Data;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Import_Members;
use WP_Error;


class Import_Member {

	/**
	 * The instance of the Import_Sponsors class (singleton pattern)
	 *
	 * @var null|Import_Member $instance
	 */
	private static $instance = null;

	/**
	 * List of fields that are PMPro specific
	 *
	 * @var array $pmpro_fields
	 */
	private $pmpro_fields = array();

	/**
	 * Instance of Error_Log class
	 *
	 * @var null|Error_Log $error_log
	 */
	private $error_log = null;

	/**
	 * Instance of Data class
	 *
	 * @var null|Data $data
	 */
	private $data = null;

	/**
	 * Instance of Variables class
	 *
	 * @var null|Variables $variables
	 */
	private $variables = null;

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
			self::$instance            = new self();
			self::$instance->error_log = new Error_Log(); // phpcs:ignore
			self::$instance->data      = new Data();
			self::$instance->variables = new Variables();
		}

		return self::$instance;
	}

	/**
	 * Add action & filter handlers (run early)
	 */
	public function load_actions() {
		add_action( 'e20r_before_user_import', array( $this, 'clean_up_old_import_data' ), - 1 );
		add_action( 'e20r_after_user_import', array( $this, 'import_membership_info' ), - 1, 3 );
	}

	/**
	 * Delete all import_ meta fields before an import in case the user has been imported in the past.
	 *
	 * @since v3.0 - Renamed function and removed duplicate code in CSV()
	 *
	 * @param array $user_data
	 * @param array $user_meta
	 */
	public function clean_up_old_import_data( $user_data, $user_meta ) {

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

			} elseif ( isset( $user_data['user_email'] ) ) {
				$target = 'email';
			}

			if ( ! empty( $target ) ) {
				$user = get_user_by( $target, $user_data[ "user_{$target}" ] );
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
	 * @param array $user_data
	 * @param array $user_meta
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
	public function import_membership_info( $user_id, $user_data, $user_meta ) {

		global $wpdb;
		global $e20r_import_err;
		global $active_line_number;

		$emails = Email_Templates::get_instance();

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		$has_error              = false;
		$membership_in_the_past = false;
		$current_blog_id        = get_current_blog_id();

		$this->error_log->debug( "Current blog ID: {$current_blog_id}" );

		wp_cache_delete( $user_id, 'users' );
		$user = get_userdata( $user_id );

		if ( empty( $user ) ) {
			$e20r_import_err[ "no_user_found_$active_line_number" ] = new WP_Error(
				'e20r_im_member',
				sprintf(
					// translators: %d the user id we're skipping
					__(
						'Unable to locate user with expected user ID of %d (Skipped!)',
						'pmpro-import-members-from-csv'
					),
					$user_id
				)
			);
			$has_error = true;
		}

		// Generate PMPro specific member value(s)
		foreach ( $user_meta as $var_name => $field_value ) {
			$user_meta[ $var_name ] = $user->{"imported_$var_name"} ?? $field_value;
		}

		$this->error_log->debug( "Adding membership info for: {$user_id}" );

		// Set site ID and custom table names for the multi site configs
		if ( is_multisite() ) {

			$this->error_log->debug( 'Importing user on multi-site...' );
			switch_to_blog( $this->variables->get( 'site_id' ) );

			$pmpro_member_table = "{$wpdb->base_prefix}pmpro_memberships_users";
			$pmpro_dc_table     = "{$wpdb->base_prefix}pmpro_discount_codes";
		}

		$has_error = apply_filters(
			'e20r_import_members_validate_field_data',
			$has_error,
			$user_id,
			$user_meta
		);

		$this->error_log->debug( "Error while validating data for {$user_id}? " . ( $has_error ? 'Yes' : 'No' ) );
		$import_member_data = ( ! $has_error );

		$this->error_log->debug( "Data for {$user_id} has been validated..." );

		$welcome_warning = $this->variables->get( 'welcome_mail_warning' );
		$send_email      = $this->variables->get( 'send_welcome_email' );
		/**
		 * @since v2.41 - BUG FIX: Didn't warn when membership_status wasn't included but user
		 * wanted to send Welcome email
		 */
		if (
			empty( $welcome_warning ) &&
			true === $send_email &&
			( ! isset( $user_data['membership_status'] ) ||
			( isset( $user_data['membership_status'] ) && 'active' !== trim( $user_data['membership_status'] ) )
			)
		) {
			$welcome_warning = __(
				'Cannot send Welcome email to members who did not get imported as \'active\' members!',
				'pmpro-import-members-from-csv'
			);

			$this->error_log->debug( $welcome_warning );
			$this->error_log->add_error_msg( $welcome_warning, 'warning' );
		}

		// Proceed to import member data if the data is correct
		if ( false === $import_member_data ) {
			$this->error_log->debug( "Not going to import member data for {$user_id}!!!" );
			return;
		}

		//Look up the discount code when included
		if ( ! empty( $user_meta['membership_discount_code'] ) && empty( $user_meta['membership_code_id'] ) ) {
			$user_meta['membership_code_id'] = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT dc.id
                              FROM {$wpdb->prefix}pmpro_discount_codes AS dc
                              WHERE dc.code = %s
                              LIMIT 1",
					$user_data['membership_discount_code']
				)
			);
		}

		//Change membership level
		if (
			isset( $user_meta['membership_id'] ) &&
			(
				( is_numeric( $user_meta['membership_id'] ) && 0 <= $user_meta['membership_id'] )
				|| ! empty( $user_meta['membership_id'] )
			)
		) {

			// Cancel previously existing (active) memberships (Should support MMPU add-on)
			// without triggering cancellation emails, etc
			if ( true === $this->variables->get( 'deactivate_old_memberships' ) ) {

				$this->error_log->debug( 'Attempting to deactivate old membership(s)' );

				// Update all currently active memberships with the specified ID for the specified user
				$updated = $wpdb->update(
					"{$wpdb->prefix}pmpro_memberships_users",
					array( 'status' => 'admin_cancelled' ),
					array(
						'user_id'       => $user_id,
						'membership_id' => $user_meta['membership_id'],
						'status'        => 'active',
					)
				);

				if ( false === $updated ) {

					$e20r_import_err[ "old_membership_error_$active_line_number" ] = new WP_Error(
						'e20r_im_member',
						sprintf(
							// translators: %1$d Membership level id, %2$d User id
							__(
								'Unable to cancel old membership level (ID: %1$d) for user (ID: %2$d)',
								'pmpro-import-members-from-csv'
							),
							$user_meta['membership_id'],
							$user_id
						)
					);
				}
			}

			$custom_level = array(
				'user_id'         => $user_id,
				'membership_id'   => $user_meta['membership_id'],
				'code_id'         => ! empty( $user_meta['membership_code_id'] ) ? $user_meta['membership_code_id'] : 0,
				'initial_payment' => ! empty( $user_meta['membership_initial_payment'] ) ? $user_meta['membership_initial_payment'] : '',
				'billing_amount'  => ! empty( $user_meta['membership_billing_amount'] ) ? $user_meta['membership_billing_amount'] : '',
				'cycle_number'    => ! empty( $user_meta['membership_cycle_number'] ) ? $user_meta['membership_cycle_number'] : '',
				'cycle_period'    => ! empty( $user_meta['membership_cycle_period'] ) ? $user_meta['membership_cycle_period'] : 'Month',
				'billing_limit'   => ! empty( $user_meta['membership_billing_limit'] ) ? $user_meta['membership_billing_limit'] : '',
				'trial_amount'    => ! empty( $user_meta['membership_trial_amount'] ) ? $user_meta['membership_trial_amount'] : '',
				'trial_limit'     => ! empty( $user_meta['membership_trial_limit'] ) ? $user_meta['membership_trial_limit'] : '',
				'startdate'       => ! empty( $user_meta['membership_startdate'] ) ? $user_meta['membership_startdate'] : null,
			);

			// Add the enddate
			$custom_level['enddate'] = ! empty( $user_meta['membership_enddate'] ) ? $user_meta['membership_enddate'] : null;

			// Set the status of the membership
			if ( ! empty( $user_meta['membership_status'] ) ) {
				$custom_level['status'] = $user_meta['membership_status'];
			}

			// Apply any/all default values we need after validation is completed
			$user_meta = apply_filters( 'e20r_import_default_field_values', $user_meta );

			/**
			 * @since v2.50 - BUG FIX: Don't deactivate old levels (here)
			 */
			add_filter( 'pmpro_deactivate_old_levels', '__return_false', 999 );
			add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false', 999 );

			/**
			 * @since v2.60 - Fatal error when sponsored members add-on is active
			 */
			remove_action(
				'pmpro_after_change_membership_level',
				'pmprosm_pmpro_after_change_membership_level',
				10
			);

			// Update the level
			$updated_level = pmpro_changeMembershipLevel( $custom_level, $user_id, 'cancelled' );

			remove_filter( 'pmpro_deactivate_old_levels', '__return_false', 999 );
			remove_filter( 'pmpro_cancel_previous_subscriptions', '__return_false', 999 );
			add_action(
				'pmpro_after_change_membership_level',
				'pmprosm_pmpro_after_change_membership_level',
				10,
				2
			);

			if ( false === $updated_level ) {

				$e20r_import_err[ "user_level_{$active_line_number}" ] = new WP_Error(
					'e20r_im_member',
					sprintf(
						// translators: %1$d PMPro membership level id, %2$d user id, %3$d line number in CSV file
						__(
							'Unable to configure membership level (ID: %1$d ) for user (user id: %2$d on line # %3$d)',
							'pmpro-import-members-from-csv'
						),
						$user_meta['membership_id'],
						$user_id,
						$active_line_number
					)
				);

			}

			if ( ! empty( $custom_level['status'] ) && 'inactive' !== $custom_level['status'] ) {
				// Get the most recently added column
				$record_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT mt.id
                                      FROM {$wpdb->prefix}pmpro_memberships_users AS mt
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
                                      FROM {$wpdb->prefix}pmpro_memberships_users AS mt
                                      WHERE mt.user_id = %d AND mt.membership_id = %d
                                      ORDER BY mt.id DESC LIMIT 1",
						$user_id,
						$custom_level['membership_id']
					)
				);
			}

			// If membership ended in the past, make it inactive for now
			if (
				( 'inactive' === strtolower( $user_meta['membership_status'] ) && ! empty( $record_id ) ) ||
				( ! empty( $user_meta['membership_enddate'] ) &&
				'NULL' !== strtoupper( $user_meta['membership_enddate'] ) &&
				strtotime( $user_meta['membership_enddate'], time() ) < time()
				)
			) {

				/**
				 * @since 2.11 - BUG FIX: Didn't handle 'inactive' status with no membership_enddate supplied
				 */
				if ( empty( $user_meta['membership_enddate'] ) ) {
					$user_meta['membership_enddate'] = date_i18n( 'Y-m-d h:i:s', time() );
				}

				if ( false !== $wpdb->update(
					"{$wpdb->prefix}pmpro_memberships_users",
					array(
						'status'  => 'inactive',
						'enddate' => $user_meta['membership_enddate'],
					),
					array(
						'id'            => $record_id,
						'user_id'       => $user_id,
						'membership_id' => $user_meta['membership_id'],
					),
					array( '%s', '%s' ),
					array( '%d', '%d', '%d' )
				)
				) {
					$membership_in_the_past = true;
				} else {
					$e20r_import_err[ "upd_error_status_{$active_line_number}" ] = new WP_Error(
						'e20r_im_member',
						sprintf(
							// translators: %1$d user id, %2$d PMPro membership level id
							__(
								'Unable to set \'inactive\' membership status/date for user (ID: %1$d) with membership level ID %2$d',
								'pmpro-import-members-from-csv'
							),
							$user_id,
							$user_meta['membership_id']
						)
					);
				}
			}

			if (
				'active' === strtolower( $user_meta['membership_status'] ) &&
				( empty( $user_meta['membership_enddate'] ) ||
				'NULL' === strtoupper( $user_meta['membership_enddate'] ) ||
				strtotime( $user_meta['membership_enddate'], time() ) >= time() )
			) {

				if ( false === $wpdb->update(
					"{$wpdb->prefix}pmpro_memberships_users",
					array(
						'status'  => 'active',
						'enddate' => $user_meta['membership_enddate'],
					),
					array(
						'id'            => $record_id,
						'user_id'       => $user_id,
						'membership_id' => $user_meta['membership_id'],
					),
					array( '%s', '%s' ),
					array( '%d', '%d', '%d' )
				) ) {
					$e20r_import_err[ "import_status_{$active_line_number}" ] = new WP_Error(
						'e20r_im_member',
						sprintf(
							// translators: %1$d user id, %2$d PMPro membership level id
							__(
								'Unable to activate membership for user (ID: %1$d) with membership level ID %2$d',
								'pmpro-import-members-from-csv'
							),
							$user_id,
							$user_meta['membership_id']
						)
					);
				}
			}
		}

		$add_status = $this->maybe_add_order( $user_id, $user_meta, $membership_in_the_past );

		if ( false === $add_status ) {
			// Add error message for failing to add order for $user_id
			$msg = sprintf(
			// translators: %2$d user id
				__(
					'Cannot create order data for user (user id: %1$d, CSV file line #: %2$s)',
					'pmpro-import-members-from-csv'
				),
				$user_id,
				$active_line_number
			);

			$e20r_import_err[ "member_order_{$user_id}_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );
			$this->error_log->debug( $msg );
		}

		do_action( 'e20r_import_trigger_membership_module_imports', $user_id, $user_data, $user_meta );

		$this->error_log->debug( "Should we send welcome email ({$send_email})? " . ( $send_email ? 'Yes' : 'No' ) );
		$emails->maybe_send_email( $user, $user_meta );

		// Log errors to log file
		if ( ! empty( $e20r_import_err ) ) {
			$this->error_log->log_errors(
				$e20r_import_err,
				$this->variables->get( 'logfile_path' ),
				$this->variables->get( 'logfile_url' )
			);
		}

		// Update the error status
		update_option( 'e20r_import_errors', $has_error );

		if ( is_multisite() ) {
			switch_to_blog( $current_blog_id );
		}
	}

	/**
	 * Add order for the user if applicable
	 *
	 * @param int   $user_id
	 * @param array $record
	 * @param bool  $membership_in_the_past
	 *
	 * @return bool
	 */
	public function maybe_add_order( $user_id, $record, $membership_in_the_past ) {

		global $wpdb;
		global $e20r_import_err;
		global $active_line_number;

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		// If we don't need to create the order record, we'll exit here.
		if ( false === (bool) $this->variables->get( 'create_order' ) ) {
			$this->error_log->debug( "Will not attempt to add order for {$user_id}" );
			return true;
		}

		if ( ! isset( $record['membership_id'] ) ) {
			$msg = sprintf(
			// translators: %1$d - User ID
				__( 'No membership ID header found for (ID: %1$d).', 'pmpro-import-members-from-csv' ),
				$user_id
			);

			$e20r_import_err[ "membership_id_missing_{$user_id}_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );
			return false;
		}

		if ( isset( $record['membership_id'] ) && empty( $record['membership_id'] ) ) {
			$msg = sprintf(
			// translators: %1$d - User ID
				__( 'Membership ID header found, but no ID value for (ID: %1$d).', 'pmpro-import-members-from-csv' ),
				$user_id
			);

			$e20r_import_err[ "membership_id_missing_{$user_id}_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );
			return false;
		}

		$pmpro_dc_uses_table = "{$wpdb->base_prefix}pmpro_discount_codes_uses";

		if ( false === $this->data->does_table_exist( 'pmpro_membership_orders' ) ) {
			$msg = sprintf(
			// translators: %s PMPro table name (order table)
				__( 'Error: table \'%1$s\' does not exists in the database!', 'pmpro-import-members-from-csv' ),
				$pmpro_dc_uses_table
			);

			$this->error_log->add_error_msg( $msg );
			$this->error_log->debug( $msg );

			return false;
		}
			// BUG: If we don't have a membership_subscription_transaction_id _and_ a membership_gateway defined then
		// we won't add a payment record (that's just not right!)

		$this->error_log->debug( "Adding PMPro order for {$user_id}..?" );
		$default_level = pmpro_getLevel( $record['membership_id'] );

		foreach ( $default_level as $property => $value ) {
			$header_name = "membership_${property}";

			if (
				! isset( $record[ $header_name ] ) ||
				( isset( $record[ $header_name ] ) && empty( $record[ $header_name ] ) )
			) {
				$this->error_log->debug( "Saving default level value {$value} to {$header_name}" );
				$record[ $header_name ] = $value;
			}
		}

		// Add a PMPro order record so integration with gateway doesn't cause surprises
		$order = $this->create_order( $user_id, $record, $membership_in_the_past );

		if ( empty( $record['membership_subscription_transaction_id'] ) && ! empty( $record['membership_gateway'] ) ) {
			$msg = sprintf(
				// translators: %1$s - The supplied gateway name, %2$s - The header we'd expect, %3$d - User ID
				__(
					'FYI: This record has a payment gateway (%1$s) but no subscription identifier to link (\'%2$s\') for %3$d (User ID).',
					'pmpro-import-members-from-csv'
				),
				$record['membership_gateway'],
				'membership_subscription_transaction_id',
				$user_id
			);

			$e20r_import_err[ "transaction_link_{$user_id}_{$active_line_number}" ] = new WP_Error(
				'e20r_im_member',
				$msg
			);
		}

		// Add any provided Discount Code use for this user
		if ( ! empty( $record['membership_code_id'] ) && ! empty( $order ) && ! empty( $order->id ) ) {

			if ( false === $wpdb->insert(
				$pmpro_dc_uses_table,
				array(
					'code_id'   => $record['membership_code_id'],
					'user_id'   => $user_id,
					'order_id'  => $order->id,
					'timestamp' => 'CURRENT_TIMESTAMP',
				),
				array( '%d', '%d', '%d', '%s' )
			) ) {
				$e20r_import_err[ "dc_usage_{$user_id}_{$active_line_number}" ] = new WP_Error(
					'e20r_im_member',
					sprintf(
						// translators: %1$d membership discount code id, %2$d user id, %3$s order ID
						__(
							'Unable to set update discount code usage for code (ID: %1$d ) for user (user/order id: %2$d/%3$s)',
							'pmpro-import-members-from-csv'
						),
						$record['membership_code_id'],
						$user_id,
						$order->id
					)
				);
			}
		}

		return true;
	}

	/**
	 * Create the order object and save the record
	 *
	 * @param int $user_id
	 * @param array $record
	 * @param bool $membership_in_the_past
	 *
	 * @return \MemberOrder
	 */
	public function create_order( $user_id, $record, $membership_in_the_past ) {

		global $active_line_number;
		global $e20r_import_err;

		$gw_env  = null;
		$gw_name = null;

		if ( Import_Members::is_pmpro_active() ) {
			$gw_name = pmpro_getGateway();
			$gw_env  = pmpro_getOption( 'gateway_environment' );
		}

		$gateway_name   = $record['membership_gateway'] ?? $gw_name;
		$gw_environment = $record['membership_gateway_environment'] ?? $gw_env;
		$user           = get_user_by( 'ID', $user_id );
		$validate       = Validate_Data::get_instance();

		/**
		 * Load order table columns for processing
		 */
		$order_fields = $this->data->get_table_info( 'pmpro_membership_orders' );

		$order                = new \MemberOrder();
		$order->user_id       = $user_id; // @phpstan-ignore-line
		$order->membership_id = $record['membership_id'] ?? null; // @phpstan-ignore-line

		// phpcs:ignore
		$order->InitialPayment = $record['membership_initial_payment'] ?? null; // @phpstan-ignore-line

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
			if ( 1 === preg_match( '/billing_(.*)/', $field_name, $matches ) ) {

				if ( ! isset( $order->billing ) ) {
					$order->billing = new \stdClass();  // @phpstan-ignore-line
				}

				if ( ! isset( $order->billing->{$matches[1]} ) ) {

					$meta_key = $this->data->map_billing_field_to_meta( $field_name );

					if ( ! empty( $meta_key ) ) {
						$order->billing->{$matches[1]} = get_user_meta( $user_id, $meta_key, true );
						$process_billing_info          = true;
					}
				}
			}

			if ( false === $process_billing_info && ( ! isset( $order->{$field_name} ) || ( isset( $order->{$field_name} ) && empty( $order->{$field_name} ) ) ) ) {

				// Process payment (amount)
				if ( 'total' === $field_name && ! empty( $record['membership_initial_payment'] ) ) {
					$order->total = $record['membership_initial_payment']; // @phpstan-ignore-line
				} elseif ( 'total' === $field_name && (
						empty( $record['membership_initial_payment'] ) &&
						! empty( $record['membership_billing_amount'] )
					) ) {

					$order->total = $record['membership_billing_amount']; // @phpstan-ignore-line

				} elseif ( 'total' !== $field_name ) {
					if ( 'status' === $field_name ) {
						// @phpstan-ignore-next-line
						$order->{$field_name} = ( isset( $record[ $full_field_name ] ) && 'active' === $record[ $full_field_name ] ? 'success' : 'cancelled' );
					} else {
						$order->{$field_name} = ! empty( $record[ $full_field_name ] ) ? $record[ $full_field_name ] : null;
					}
				} else {

					$this->error_log->debug( "Warning: {$field_name} will not be processed!!" );
				}
			}
		}

		// TODO BUG: Have to set the membership_gateway and membership_gateway_environment variables!
		if ( isset( $record['membership_gateway'] ) && strtolower( $gateway_name ) !== strtolower( $record['membership_gateway'] ) ) {
			$order->setGateway( $record['membership_gateway'] );
		}

		if (
			! isset( $record['membership_gateway'] ) ||
			( isset( $record['membership_gateway'] ) && empty( isset( $record['membership_gateway'] ) ) )
		) {
			$order->setGateway( $gateway_name );
		}

		if ( isset( $record['membership_gateway_environment'] ) && strtolower( $gw_environment ) !== strtolower( $record['membership_gateway_environment'] ) ) {
			$order->gateway_environment = strtolower( $record['membership_gateway_environment'] ); // @phpstan-ignore-line
		}

		if (
			! isset( $record['membership_gateway_environment'] ) ||
			( isset( $record['membership_gateway_environment'] ) && empty( isset( $record['membership_gateway_environment'] ) ) )
		) {
			$order->gateway_environment = $gw_environment; // @phpstan-ignore-line
		}
		if ( true === $membership_in_the_past ) {
			$order->status = 'cancelled'; // @phpstan-ignore-line
		}

		/**
		 * Add MemberOrder billing info if possible
		 */
		if ( ! empty( $order->billing ) ) {
			$order->billing->name = "{$user->first_name} {$user->last_name}";
		}

		if ( false === $order->saveOrder() ) {
			$msg = sprintf(
				// translators: %d - User ID
				__( 'Unable to save order object for user (ID: %d).', 'pmpro-import-members-from-csv' ),
				$user_id
			);

			$e20r_import_err[ "order_save_{$user_id}_{$active_line_number}" ] = new WP_Error( 'e20r_im_member', $msg );
			$this->error_log->debug( $msg );
		}

		// Update order timestamp?
		if ( ! empty( $record['membership_timestamp'] ) ) {
			if ( true === $validate->date( $record['membership_timestamp'], 'Y-m-d H:i:s' ) ) {
				$timestamp = strtotime( $record['membership_timestamp'], time() );
			} else {
				$timestamp = is_numeric( $record['membership_timestamp'] ) ? $record['membership_timestamp'] : null;
				if ( is_null( $timestamp ) ) {
					$e20r_import_err[ "timestamp_{$user_id}_{$active_line_number}" ] = new WP_Error(
						'e20r_im_member',
						sprintf(
						// translators: %s PMPro Order timestamp value from CSV file
							__( 'Could not decode timezone value (%s)', 'pmpro-import-members-from-csv' ),
							$record['membership_timestamp']
						)
					);
				}
			}

			$order->updateTimeStamp(
				date_i18n( 'Y', $timestamp ),
				date_i18n( 'm', $timestamp ),
				date_i18n( 'd', $timestamp ),
				date_i18n( 'H:i:s', $timestamp )
			);
		}

		return $order;
	}
	/**
	 * Hide/protect the __clone() magic method for this class (singleton pattern)
	 *
	 * @access private
	 */
	private function __clone() {
	}
}
