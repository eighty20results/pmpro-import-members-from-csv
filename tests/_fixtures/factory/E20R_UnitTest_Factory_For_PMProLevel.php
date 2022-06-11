<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 Luca Tumedei
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package E20R\Tests\Fixtures\Factory\E20R_UnitTest_Generator_Sequence
 */

namespace E20R\Tests\Fixtures\Factory;

use E20R\Tests\Fixtures\Factory\E20R_UnitTest_Factory;
use E20R\Tests\Fixtures\Factory\E20R_UnitTest_Factory_For_Thing;
use E20R\Tests\Fixtures\Factory\E20R_UnitTest_Generator_Sequence;

class E20R_UnitTest_Factory_For_PMProLevel extends E20R_UnitTest_Factory_For_Thing {
	/**
	 * Constructor for the WP_UnitTest_Factory_For_PMProLevel
	 *
	 * @param E20R_UnitTest_Factory $factory
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'id'              => new E20R_UnitTest_Generator_Sequence( '%s' ),
			'level_name'      => new E20R_UnitTest_Generator_Sequence( 'Test PMPro Level %s' ),
			'description'     => new E20R_UnitTest_Generator_Sequence( 'Test PMPro level # %s' ),
			'confirmation'    => new E20R_UnitTest_Generator_Sequence( 'Confirmed member of site using PMPro membership level %s' ),
			'initial_payment' => 10.00,
			'billing_amount'  => 5.00,
			'cycle_number'    => 1,
			'cycle_period'    => 'Month',
			'allow_signups'   => 1,
		);
	}

	/**
	 * Create a PMPro Membership level in the $wpdb->pmpro_membership_levels table
	 *
	 * @param array $args PMPro Membership Level parameters
	 *
	 * @return int
	 */
	public function create_object( $args ) {
		global $wpdb;
		pmpro_insert_or_replace(
			$wpdb->pmpro_membership_levels,
			array(
				'id'                => $args['id'],
				'name'              => $args['level_name'],
				'description'       => $args['description'],
				'confirmation'      => $args['confirmation'],
				'initial_payment'   => $args['initial_payment'] ?? 10.00,
				'billing_amount'    => $args['billing_amount'] ?? '',
				'cycle_number'      => $args['cycle_number'] ?? '',
				'cycle_period'      => $args['cycle_period'] ?? 'Month',
				'billing_limit'     => $args['billing_limit'] ?? '',
				'trial_amount'      => $args['trial_amount'] ?? '',
				'trial_limit'       => $args['trial_limit'] ?? '',
				'expiration_number' => $args['expiration_number'] ?? '',
				'expiration_period' => $args['expiration_period'] ?? '',
				'allow_signups'     => 1,
			),
			array(
				'%d',       //id
				'%s',       //name
				'%s',       //description
				'%s',       //confirmation
				'%f',       //initial_payment
				'%f',       //billing_amount
				'%d',       //cycle_number
				'%s',       //cycle_period
				'%d',       //billing_limit
				'%f',       //trial_amount
				'%d',       //trial_limit
				'%d',       //expiration_number
				'%s',       //expiration_period
				'%d',       //allow_signups
			)
		);

		return $args['id'];
	}

	/**
	 * Update the PMPro Membership level info
	 *
	 * @param int $level_id PMpro Membership Level ID
	 * @param array $fields PMPro Membership Level parameters
	 *
	 * @return mixed
	 */
	public function update_object( $level_id, $fields ) {
		$fields['id'] = $level_id;
		global $wpdb;
		pmpro_insert_or_replace(
			$wpdb->pmpro_membership_levels,
			array(
				'id'                => $fields['id'],
				'name'              => $fields['level_name'],
				'description'       => $fields['description'],
				'confirmation'      => $fields['confirmation'],
				'initial_payment'   => $fields['initial_payment'] ?? 10.00,
				'billing_amount'    => $fields['billing_amount'] ?? '',
				'cycle_number'      => $fields['cycle_number'] ?? '',
				'cycle_period'      => $fields['cycle_period'] ?? 'Month',
				'billing_limit'     => $fields['billing_limit'] ?? '',
				'trial_amount'      => $fields['trial_amount'] ?? '',
				'trial_limit'       => $fields['trial_limit'] ?? '',
				'expiration_number' => $fields['expiration_number'] ?? '',
				'expiration_period' => $fields['expiration_period'] ?? '',
				'allow_signups'     => 1,
			),
			array(
				'%d',       //id
				'%s',       //name
				'%s',       //description
				'%s',       //confirmation
				'%f',       //initial_payment
				'%f',       //billing_amount
				'%d',       //cycle_number
				'%s',       //cycle_period
				'%d',       //billing_limit
				'%f',       //trial_amount
				'%d',       //trial_limit
				'%d',       //expiration_number
				'%s',       //expiration_period
				'%d',       //allow_signups
			)
		);

		return $fields['id'];
	}

	/**
	 * Return the PMPro membership level data based on supplied level ID (if it exists)
	 *
	 * @param int $level_id PMPro Membership Level ID
	 *
	 * @return false|object
	 */
	public function get_object_by_id( $level_id ) {
		return pmpro_getLevel( $level_id );
	}
}
