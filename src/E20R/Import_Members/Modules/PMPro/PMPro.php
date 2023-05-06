<?php
/**
 * Copyright (c) 2018 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
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
 *
 * @package E20R\Import_Members\Modules\PMPro
 */
namespace E20R\Import_Members\Modules\PMPro;

use E20R\Import_Members\Error_Log;

if ( ! class_exists( 'E20R\Import_Members\Modules\PMPro\PMPro' ) ) {
	/**
	 * Class PMPro
	 */
	class PMPro {

		/**
		 * Load all supported Import field (column) names for the PMPro module
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public function load_fields( $fields ) {

			// Configure fields for PMPro Import
			$fields = array_merge_recursive(
				$fields,
				array(
					'membership_id'                     => null,
					'membership_code_id'                => null,
					'membership_discount_code'          => null,
					'membership_initial_payment'        => null,
					'membership_billing_amount'         => null,
					'membership_cycle_number'           => null,
					'membership_cycle_period'           => null,
					'membership_billing_limit'          => null,
					'membership_trial_amount'           => null,
					'membership_trial_limit'            => null,
					'membership_status'                 => null,
					'membership_startdate'              => null,
					'membership_enddate'                => null,
					'membership_subscription_transaction_id' => null,
					'membership_payment_transaction_id' => null,
					'membership_gateway'                => null,
					'membership_affiliate_id'           => null,
					'membership_timestamp'              => null,
				)
			);

			return apply_filters( 'e20r_import_modules_pmpro_headers', $fields );
		}

		/**
		 * Load PMPro specific functionality
		 */
		public function load_hooks() {
			add_filter( 'e20r_import_supported_fields', array( $this, 'load_fields' ), 1, 1 );
			add_filter( 'e20r_import_default_field_values', array( $this, 'update_field_values' ), 2, 1 );
		}

		/**
		 * Run data checks/validations (business rules, etc)
		 *
		 * @param array $fields
		 *
		 * @return array
		 *
		 * @access private
		 *
		 * @since  2.22 - ENHANCEMENT: Added validate_membership_data() method
		 * @since  2.30 - ENHANCEMENT: Multiple updates for payment gateway integrations, etc
		 */
		public function update_field_values( $fields ) {

			global $e20r_import_err;
			global $e20r_import_warn;
			global $active_line_number;

			if ( ! is_array( $e20r_import_err ) ) {
				$e20r_import_err = array();
			}

			if ( ! is_array( $e20r_import_warn ) ) {
				$e20r_import_warn = array();
			}

			if ( ! function_exists( 'pmpro_getOption' ) ) {
				return $fields;
			}

			$has_error = false;
			$errors    = array();
			$error     = new Error_Log(); // phpcs:ignore

			if (
				isset( $e20r_import_err[ "no_gw_environment_{$active_line_number}" ] ) ||
				isset( $e20r_import_err[ "correct_gw_env_variable_{$active_line_number}" ] )
			) {
				$fields['membership_gateway_environment']                           = pmpro_getOption( 'gateway_environment' );
				$e20r_import_warn[ "setting_default_gw_env_{$active_line_number}" ] = sprintf(
					// translators: %1$s - Environment (production or test) for payment gateway setting
					esc_attr__( 'Forcing Payment Gateway environment setting to %1$s for entry on line %2$d', 'pmpro-import-members-from-csv' ),
					$fields['membership_gateway_environment'],
					$active_line_number
				);

			}

			// Doesn't have a supported gateway, so adding it!
			if ( isset( $e20r_import_err[ "supported_gateway_{$active_line_number}" ] ) ) {
				$fields['membership_gateway']                                   = pmpro_getOption( 'gateway' );
				$e20r_import_warn[ "setting_default_gw_{$active_line_number}" ] = sprintf(
				// translators: %1$s - Environment (production or test) for payment gateway setting
					esc_attr__( 'Forcing Payment Gateway setting to %1$s for entry on line %2$d', 'pmpro-import-members-from-csv' ),
					$fields['membership_gateway'],
					$active_line_number
				);
			}

			return $fields;
		}
	}
}
