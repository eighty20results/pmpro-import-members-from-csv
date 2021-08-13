<?php
/**
 * Copyright (c) 2018 - 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Import_Members\Email;

use E20R\Import_Members\Error_Log;

if ( ! class_exists( '\E20R\Import_Members\Email\Template_Data' ) ) {
	/**
	 * Class Template_Data
	 * @package E20R\Import_Members\Email
	 */
	class Template_Data {

		/**
		 * User record
		 *
		 * @var \WP_User $user
		 */
		private $user = null;

		/**
		 * PMPro Order object
		 *
		 * @var \MemberOrder $order
		 */
		private $order = null;

		/**
		 * List (array) of Metadata for the $user
		 *
		 * @var array $meta
		 */
		private $meta = array();

		/**
		 * List of field names as used by PMPro and their matching Import header name(s)
		 *
		 * @var array $field_aliases
		 */
		private $field_aliases;

		/**
		 * Array of site specific information used by Email template(s)
		 *
		 * @var array $site_fields
		 */
		private $site_fields = array();

		/**
		 * Error_Log object
		 *
		 * @var Error_Log|null $error_log
		 */
		private $error_log = null;

		/**
		 * Imported_Data constructor.
		 */
		public function __construct() {
			$this->error_log = new Error_Log(); // phpcs:ignore
		}

		/**
		 * Configure the member variables
		 *
		 * @param \WP_User $user
		 * @param \MemberOrder $order
		 * @param array $user_meta
		 */
		public function configure_objects( \WP_User $user, \MemberOrder $order, array $user_meta ) {

			// Save the received object(s)
			$this->user  = $user;
			$this->order = $order;
			$this->meta  = $user_meta;

			if ( null === $this->user && null === $user ) {
				$this->error_log->debug( 'Warning: Using the logged in user\'s information' );
				$this->user = get_current_user();
			}

			if ( null === $this->order && null === $order ) {
				$this->error_log->debug( "Warning: Using the most recent order for user {$user->ID} (ID)" );
				$this->order = new \MemberOrder();
				$this->order->getLastMemberOrder( $user->ID );
			}

			if ( null === $this->meta && null === $user_meta ) {
				$this->error_log->debug( "Warning: Loading all metadata recorded for user {$user->ID} (ID)" );
				$this->meta = get_user_meta( $user->ID );
			}
		}

		/**
		 * Return fields (used in Email substitutions)
		 *
		 * @param array $field_list
		 *
		 * @return array
		 */
		public function default_site_field_values( array $field_list = array() ): array {
			global $current_user;

			/**
			 * Returns the field mapping for non-membership information
			 */
			return apply_filters(
				'e20r_import_message_site_fields',
				array(
					'sitename'    => get_option( 'blogname' ),
					'siteemail'   => pmpro_getOption( 'from_email' ),
					'login_link'  => wp_login_url(),
					'levels_link' => pmpro_url( 'levels' ),
					'name'        => $this->user->display_name ?? ( $current_user->display_name ?? null ),
				)
			);
		}

		/**
		 * Returns the value for the specified field name and type if it exists
		 *
		 * @param string $field_name
		 * @param string $type
		 *
		 * @return mixed|bool|null
		 * @throws \Exception
		 */
		public function get( string $field_name, string $type = null ) {

			$value = null;
			$type  = strtolower( $type );

			// Make sure we use the field name as saved in the imported data
			$field_name = $this->field_aliases[ $field_name ] ?? $field_name;

			switch ( $type ) {
				case 'meta':
					$value = $this->meta[ $field_name ] ?? false;
					break;
				case 'user':
					$value = $this->user->{$field_name} ?? false;
					break;
				case 'order':
					// Return order field value or an empty string
					$value = $this->order->{$field_name} ?? false;
					break;
				default:
					// Raise exception if the field isn't found
					if ( ! isset( $this->{$field_name} ) ) {
						$msg = sprintf(
						// translators: %1$s - Name of the requested field
							esc_attr__( 'Error: Field name not found (%1$s)', 'pmpro-import-members-from-csv' ),
							$field_name
						);
						throw new \Exception( $msg );
					}

					$value = $this->{$field_name};
			}

			return $value;
		}

		/**
		 * Generate list of fields to process for Import message
		 *
		 * @param array $fields
		 */
		public function load_field_aliases( $fields = array() ) {

			foreach ( $this->meta as $meta_key => $meta_value ) {
				$field_name = $meta_key;
			}

			$this->mapped_fields += $fields;

			/*
			if ( 'membership_id' !== strtolower( $full_field_name ) && 'user_id' !== strtolower( $full_field_name ) ) {
				$field_name = preg_replace( '/membership_/', '', strtolower( $full_field_name ) );
			} else {
				$field_name = strtolower( $full_field_name );
			}
			*/
		}
	}
}
