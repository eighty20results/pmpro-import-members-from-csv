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

class Sponsor {

	/**
	 * The sponsor's WP_User ID
	 *
	 * @var null|int $ID
	 */
	private $ID = null;

	/**
	 * User Record for the sponsor
	 *
	 * @var null|\WP_User  $user
	 */
	private $user = null;

	/**
	 * The membership level information for the sponsor
	 *
	 * @var null|\stdClass $membership_level
	 */
	private $membership_level = null;

	/**
	 * Sponsor constructor.
	 *
	 * @param int|string $user_id
	 *
	 * @throws \Exception
	 */
	public function __construct( $user_id = null ) {

		$data = new Data();

		if ( ! empty( $user_id ) ) {
			$this->user = $data->get_user_info( $user_id );
		}

		if ( ! empty( $user_id ) && empty( $this->user ) ) {
			throw new \Exception(
				sprintf(
					// translators: %s - Supplied User ID, email or login name
					__( 'No user with that ID (%s) found!', 'pmpro-import-members-from-csv' ),
					$user_id
				)
			);
		}
	}

	/**
	 * Set Sponsor parameter by type
	 *
	 * @param string      $param
	 * @param mixed       $value
	 * @param null|string $type
	 *
	 * @return bool
	 */
	public function set( $param, $value, $type = null ) {

		$attributes = get_class_vars( __CLASS__ );

		if ( ! in_array( $param, $attributes, true ) && ! in_array( $type, $attributes, true ) ) {
			return false;
		}

		switch ( $type ) {

			case 'user':
				if ( isset( $this->user->{$param} ) ) {
					$this->user->{$param} = $value;
				} else {
					return false;
				}
				break;

			case 'membership':
				if ( isset( $this->membership_level->{$param} ) ) {
					$this->membership_level->{$param} = $value;
				} else {
					return false;
				}
				break;

			default:
				$this->{$param} = $value;
		}

		return true;
	}

	/**
	 * Return the type specific parameter (membership info, user info, class variable)
	 *
	 * @param string|null $type
	 * @param string $param
	 *
	 * @return bool|mixed|null
	 */
	public function get( $type, $param ) {

		$attributes = get_class_vars( __CLASS__ );
		$value      = null;

		if ( ! in_array( $param, $attributes, true ) ) {
			return false;
		}

		switch ( $type ) {

			case 'user':
				$value = $this->user->{$param};
				break;

			case 'membership':
				$value = $this->membership_level->{$param};
				break;

			default:
				$value = $this->{$param};
		}

		return $value;
	}
}
