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

use \WP_UnitTest_Generator_Sequence;

class E20R_UnitTest_Factory_For_PMProMembers extends E20R_UnitTest_Factory_For_Thing {
	/**
	 * Constructor for the WP_UnitTest_Factory_For_PMProOrder
	 *
	 * @param E20R_UnitTest_Factory $factory
	 */
	public function __construct( $factory = null ) {
		// parent::__construct( $factory );
		$this->default_generation_definitions = array();
	}

	/**
	 * Create a PMPro Membership level in the $wpdb->pmpro_membership_levels table
	 *
	 * @param array $args PMPro Membership Level parameters
	 *
	 * @return int
	 */
	public function create_object( $args ) {
		$user_id       = $args['user_id'];
		$membership_id = $args['membership_id'];
		pmpro_changeMembershipLevel( $membership_id, $user_id, 'admin_changed' );
		return $this->get_membership_id( $user_id, $membership_id );
	}

	/**
	 * Update the PMPro Membership level info
	 *
	 * @param int   $membership_id PMpro Membership Level ID
	 * @param array $fields PMPro Membership Level parameters
	 *
	 * @return mixed
	 */
	public function update_object( $membership_id, $fields ) {
		$user_id       = $fields['user_id'];
		$membership_id = $fields['membership_id'];
		pmpro_changeMembershipLevel( $membership_id, $user_id, 'admin_changed' );

		return $this->get_membership_id( $user_id, $membership_id );
	}

	/**
	 * Return the PMPro membership level data based on supplied level ID (if it exists)
	 *
	 * @param int $membership_record_id PMPro Membership ID
	 *
	 * @return false|object
	 */
	public function get_object_by_id( $membership_record_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->pmpro_memberships_users} WHERE id = %d", $membership_record_id )
		);
	}

	/**
	 * Return the ID of the active membership record
	 *
	 * @param int $user_id
	 * @param int $membership_id
	 * @param string $status Default is 'active'
	 *
	 * @return int
	 */
	private function get_membership_id( $user_id, $membership_id, $status = 'active' ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->pmpro_memberships_users} WHERE status = %s AND user_id = %d AND membership_id = %d ORDER BY id DESC LIMIT 1",
				$status,
				$user_id,
				$membership_id
			)
		);
	}
}
