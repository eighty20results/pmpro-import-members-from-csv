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

use MemberOrder;
use \WP_UnitTest_Factory;
use \WP_UnitTest_Generator_Sequence;

class E20R_UnitTest_Factory_For_PMProOrders extends E20R_UnitTest_Factory_For_Thing {
	/**
	 * Constructor for the WP_UnitTest_Factory_For_PMProOrder
	 *
	 * @param WP_UnitTest_Factory $factory
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'id'           => new WP_UnitTest_Generator_Sequence( '%s' ),
			'level_name'   => new WP_UnitTest_Generator_Sequence( 'Test PMPro Level %s' ),
			'description'  => new WP_UnitTest_Generator_Sequence( 'Test PMPro level # %s' ),
			'confirmation' => new WP_UnitTest_Generator_Sequence( 'Confirmed member of site using PMPro membership level %s' ),
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
		$order = new MemberOrder();

		foreach ( $args as $field_name => $value ) {
			$order->set( $field_name, $value );
		}

		$order->saveOrder();
		return $order->get( 'id' );
	}

	/**
	 * Update the PMPro Membership level info
	 *
	 * @param int $level_id PMpro Membership Level ID
	 * @param array $fields PMPro Membership Level parameters
	 *
	 * @return mixed
	 */
	public function update_object( $order_id, $fields ) {
		$fields['id'] = $order_id;
		$order        = new MemberOrder();
		$order->getMemberOrderByID( $order_id );

		foreach ( $fields as $name => $field_value ) {
			$order->set( $name, $field_value );
		}

		$order->saveOrder();
		return $order->get( 'id' );
	}

	/**
	 * Return the PMPro order data based on supplied order ID (if it exists)
	 *
	 * @param int $order_id PMPro MemberOrder ID
	 *
	 * @return false|object
	 */
	public function get_object_by_id( $order_id ) {
		$order = new MemberOrder();
		$order->getMemberOrderByID( $order_id );
		return $order->get( 'id' );
	}
}
