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

use E20R\Import_Members\Error_Log;

class PMPro {
	/**
	 * Singleton instance of this class (PMPro)
	 *
	 * @var null|PMPro
	 */
	private static $instance = null;

	/**
	 * PMPro specific import fields
	 *
	 * @var array $fields
	 */
	private $fields = array();

	/**
	 * PMPro constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * Get or instantiate and return this class (PMPro)
	 *
	 * @return PMPro|null
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
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
				'membership_id'                          => null,
				'membership_code_id'                     => null,
				'membership_discount_code'               => null,
				'membership_initial_payment'             => null,
				'membership_billing_amount'              => null,
				'membership_cycle_number'                => null,
				'membership_cycle_period'                => null,
				'membership_billing_limit'               => null,
				'membership_trial_amount'                => null,
				'membership_trial_limit'                 => null,
				'membership_status'                      => null,
				'membership_startdate'                   => null,
				'membership_enddate'                     => null,
				'membership_subscription_transaction_id' => null,
				'membership_payment_transaction_id'      => null,
				'membership_gateway'                     => null,
				'membership_affiliate_id'                => null,
				'membership_timestamp'                   => null,
			)
		);

		return apply_filters( 'e20r_import_modules_pmpro_headers', $fields );
	}

	/**
	 * Load PMPro specific functionality
	 */
	public function load_hooks() {
		add_filter( 'e20r_import_supported_fields', array( $this, 'load_fields' ), 1, 1 );
		add_filter( 'e20r_import_default_field_values', array( $this, 'update_field_values' ), 2, 3 );
	}


	/**
	 * Run data checks/validations (business rules, etc)
	 *
	 * @param array $fields
	 *
	 * @return bool|array
	 *
	 * @access private
	 *
	 * @since  2.22 - ENHANCEMENT: Added validate_membership_data() method
	 * @since  2.30 - ENHANCEMENT: Multiple updates for payment gateway integrations, etc
	 */
	public function update_field_values( $fields ) {

		global $e20r_import_err;
		global $active_line_number;

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
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
			$fields['membership_gateway_environment'] = pmpro_getOption( 'gateway_environment' );
		}

		// Doesn't have a supported gateway, so adding it!
		if ( isset( $e20r_import_err[ "supported_gateway_{$active_line_number}" ] ) ) {
			$fields['membership_gateway'] = pmpro_getOption( 'gateway' );
		}

		return $fields;
	}

	/**
	 * Clone the class (Singleton)
	 *
	 * @access private
	 */
	private function __clone() {
		// TODO: Implement __clone() method.
	}
}
