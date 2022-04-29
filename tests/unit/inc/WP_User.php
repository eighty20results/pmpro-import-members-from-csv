<?php
/**
 * Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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

if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		/**
		 * User data container.
		 *
		 * @since 2.0.0
		 * @var stdClass
		 */
		public $data;

		/**
		 * The user's ID.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		public $ID = 0;

		/**
		 * Capabilities that the individual user has been granted outside of those inherited from their role.
		 *
		 * @since 2.0.0
		 * @var bool[] Array of key/value pairs where keys represent a capability name
		 *             and boolean values represent whether the user has that capability.
		 */
		public $caps = array();

		/**
		 * User metadata option name.
		 *
		 * @since 2.0.0
		 * @var string
		 */
		public $cap_key;

		/**
		 * The roles the user is part of.
		 *
		 * @since 2.0.0
		 * @var string[]
		 */
		public $roles = array();

		/**
		 * All capabilities the user has, including individual and role based.
		 *
		 * @since 2.0.0
		 * @var bool[] Array of key/value pairs where keys represent a capability name
		 *             and boolean values represent whether the user has that capability.
		 */
		public $allcaps = array();

		/**
		 * The filter context applied to user data fields.
		 *
		 * @since 2.9.0
		 * @var string
		 */
		public $filter = null;

		/**
		 * The site ID the capabilities of this user are initialized for.
		 *
		 * @since 4.9.0
		 * @var int
		 */
		private $site_id = 0;

		/**
		 * @since 3.3.0
		 * @var array
		 */
		private static $back_compat_keys;

		/**
		 * Constructor.
		 *
		 * Retrieves the userdata and passes it to WP_User::init().
		 *
		 * @param int|string|stdClass|WP_User $id User's ID, a WP_User object, or a user object from the DB.
		 * @param string $name Optional. User's username
		 * @param int $site_id Optional Site ID, defaults to current site.
		 *
		 * @since 2.0.0
		 *
		 */
		public function __construct( $id = 0, $name = '', $site_id = '' ) {
			if ( ! isset( self::$back_compat_keys ) ) {
				$prefix                 = 'wptest_';
				self::$back_compat_keys = array(
					'user_firstname'             => 'first_name',
					'user_lastname'              => 'last_name',
					'user_description'           => 'description',
					'user_level'                 => $prefix . 'user_level',
					$prefix . 'usersettings'     => $prefix . 'user-settings',
					$prefix . 'usersettingstime' => $prefix . 'user-settings-time',
				);
			}

			$this->data                      = new stdClass();
			$this->data->user_login          = '';
			$this->data->user_pass           = '';
			$this->data->user_nicename       = '';
			$this->data->user_url            = '';
			$this->data->user_registered     = gmdate( 'Y-m-d h:i:s' );
			$this->data->user_activation_key = '';
			$this->data->user_status         = 1;
			$this->data->display_name        = '';
			$this->data->first_name          = '';
			$this->data->last_name           = '';
			$this->data->description         = '';
			$this->data->user_firstname      = '';
			$this->data->user_lastname       = '';
			$this->data->user_descriptio     = '';
		}


		/**
		 * Magic method for checking the existence of a certain custom field.
		 *
		 * @param string $key User meta key to check if set.
		 *
		 * @return bool Whether the given user meta key is set.
		 * @since 3.3.0
		 *
		 */
		public function __isset( $key ) {
			if ( 'id' === $key ) {
				_deprecated_argument(
					'WP_User->id',
					'2.1.0',
					sprintf(
					/* translators: %s: WP_User->ID */
						esc_attr__( 'Use %s instead.' ),
						'<code>WP_User->ID</code>'
					)
				);
				$key = 'ID';
			}

			if ( isset( $this->data->$key ) ) {
				return true;
			}

			if ( isset( self::$back_compat_keys[ $key ] ) ) {
				$key = self::$back_compat_keys[ $key ];
			}

			return metadata_exists( 'user', $this->ID, $key );
		}

		/**
		 * Magic method for accessing custom fields.
		 *
		 * @param string $key User meta key to retrieve.
		 *
		 * @return mixed Value of the given user meta key (if set). If `$key` is 'id', the user ID.
		 * @since 3.3.0
		 *
		 */
		public function __get( $key ) {
			if ( 'id' === $key ) {
				_deprecated_argument(
					'WP_User->id',
					'2.1.0',
					sprintf(
					/* translators: %s: WP_User->ID */
						esc_attr__( 'Use %s instead.' ),
						'<code>WP_User->ID</code>'
					)
				);

				return $this->ID;
			}

			if ( isset( $this->data->$key ) ) {
				$value = $this->data->$key;
			} else {
				if ( isset( self::$back_compat_keys[ $key ] ) ) {
					$key = self::$back_compat_keys[ $key ];
				}
				$value = get_user_meta( $this->ID, $key, true );
			}

			if ( $this->filter ) {
				$value = sanitize_user_field( $key, $value, $this->ID, $this->filter );
			}

			return $value;
		}

		/**
		 * Magic method for setting custom user fields.
		 *
		 * This method does not update custom fields in the database. It only stores
		 * the value on the WP_User instance.
		 *
		 * @param string $key User meta key.
		 * @param mixed $value User meta value.
		 *
		 * @since 3.3.0
		 *
		 */
		public function __set( $key, $value ) {
			if ( 'id' === $key ) {
				_deprecated_argument(
					'WP_User->id',
					'2.1.0',
					sprintf(
					/* translators: %s: WP_User->ID */
						esc_attr__( 'Use %s instead.' ),
						'<code>WP_User->ID</code>'
					)
				);
				$this->ID = $value;

				return;
			}

			if ( ! isset( $this->data ) && empty( $this->data ) ) {
				$this->data = new stdClass();
			}

			$this->data->$key = $value;
		}

		/**
		 * Magic method for unsetting a certain custom field.
		 *
		 * @param string $key User meta key to unset.
		 *
		 * @since 4.4.0
		 *
		 */
		public function __unset( $key ) {
			if ( 'id' === $key ) {
				_deprecated_argument(
					'WP_User->id',
					'2.1.0',
					sprintf(
					/* translators: %s: WP_User->ID */
						esc_attr__( 'Use %s instead.' ),
						'<code>WP_User->ID</code>'
					)
				);
			}

			if ( isset( $this->data->$key ) ) {
				unset( $this->data->$key );
			}

			if ( isset( self::$back_compat_keys[ $key ] ) ) {
				unset( self::$back_compat_keys[ $key ] );
			}
		}

		/**
		 * Determine whether the user exists in the database.
		 *
		 * @return bool True if user exists in the database, false if not.
		 * @since 3.4.0
		 *
		 */
		public function exists() {
			return ! empty( $this->ID );
		}

		/**
		 * Retrieve the value of a property or meta key.
		 *
		 * Retrieves from the users and usermeta table.
		 *
		 * @param string $key Property
		 *
		 * @return mixed
		 * @since 3.3.0
		 *
		 */
		public function get( $key ) {
			return $this->__get( $key );
		}

		/**
		 * Determine whether a property or meta key is set
		 *
		 * Consults the users and usermeta tables.
		 *
		 * @param string $key Property
		 *
		 * @return bool
		 * @since 3.3.0
		 *
		 */
		public function has_prop( $key ) {
			return $this->__isset( $key );
		}

		/**
		 * Return an array representation.
		 *
		 * @return array Array representation.
		 * @since 3.5.0
		 *
		 */
		public function to_array() {
			return get_object_vars( $this->data );
		}

		/**
		 * Makes private/protected methods readable for backward compatibility.
		 *
		 * @param string $name Method to call.
		 * @param array $arguments Arguments to pass when calling.
		 *
		 * @return mixed|false Return value of the callback, false otherwise.
		 * @since 4.3.0
		 *
		 */
		public function __call( $name, $arguments ) {
			if ( '_init_caps' === $name ) {
				return $this->_init_caps( ...$arguments );
			}

			return false;
		}

		/**
		 * Set up capability object properties.
		 *
		 * Will set the value for the 'cap_key' property to current database table
		 * prefix, followed by 'capabilities'. Will then check to see if the
		 * property matching the 'cap_key' exists and is an array. If so, it will be
		 * used.
		 *
		 * @param string $cap_key Optional capability key
		 *
		 * @deprecated 4.9.0 Use WP_User::for_site()
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 *
		 * @since 2.1.0
		 */
		protected function _init_caps( $cap_key = '' ) {
			global $wpdb;

			_deprecated_function( __METHOD__, '4.9.0', 'WP_User::for_site()' );

			if ( empty( $cap_key ) ) {
				$this->cap_key = $wpdb->get_blog_prefix( $this->site_id ) . 'capabilities';
			} else {
				$this->cap_key = $cap_key;
			}

			$this->caps = $this->get_caps_data();

			$this->get_role_caps();
		}

		/**
		 * Retrieves all of the capabilities of the user's roles, and merges them with
		 * individual user capabilities.
		 *
		 * All of the capabilities of the user's roles are merged with the user's individual
		 * capabilities. This means that the user can be denied specific capabilities that
		 * their role might have, but the user is specifically denied.
		 *
		 * @return bool[] Array of key/value pairs where keys represent a capability name
		 *                and boolean values represent whether the user has that capability.
		 * @since 2.0.0
		 *
		 */
		public function get_role_caps() {
			$switch_site = false;
			if ( is_multisite() && get_current_blog_id() !== $this->site_id ) {
				$switch_site = true;

				switch_to_blog( $this->site_id );
			}

			$wp_roles = wp_roles();

			// Filter out caps that are not role names and assign to $this->roles.
			if ( is_array( $this->caps ) ) {
				$this->roles = array_filter( array_keys( $this->caps ), array( $wp_roles, 'is_role' ) );
			}

			// Build $allcaps from role caps, overlay user's $caps.
			$this->allcaps = array();
			foreach ( (array) $this->roles as $role ) {
				$the_role      = $wp_roles->get_role( $role );
				$this->allcaps = array_merge( (array) $this->allcaps, (array) $the_role->capabilities );
			}
			$this->allcaps = array_merge( (array) $this->allcaps, (array) $this->caps );

			if ( $switch_site ) {
				restore_current_blog();
			}

			return $this->allcaps;
		}

		/**
		 * Add role to user.
		 *
		 * Updates the user's meta data option with capabilities and roles.
		 *
		 * @param string $role Role name.
		 *
		 * @since 2.0.0
		 *
		 */
		public function add_role( $role ) {
			if ( empty( $role ) ) {
				return;
			}

			$this->caps[ $role ] = true;
			// update_user_meta( $this->ID, $this->cap_key, $this->caps );
			$this->get_role_caps();
			$this->update_user_level_from_caps();

			/**
			 * Fires immediately after the user has been given a new role.
			 *
			 * @param int $user_id The user ID.
			 * @param string $role The new role.
			 *
			 * @since 4.3.0
			 *
			 */
			do_action( 'add_user_role', $this->ID, $role );
		}

		/**
		 * Remove role from user.
		 *
		 * @param string $role Role name.
		 *
		 * @since 2.0.0
		 *
		 */
		public function remove_role( $role ) {
			if ( ! in_array( $role, $this->roles, true ) ) {
				return;
			}
			unset( $this->caps[ $role ] );
			// update_user_meta( $this->ID, $this->cap_key, $this->caps );
			$this->get_role_caps();
			$this->update_user_level_from_caps();

			/**
			 * Fires immediately after a role as been removed from a user.
			 *
			 * @param int $user_id The user ID.
			 * @param string $role The removed role.
			 *
			 * @since 4.3.0
			 *
			 */
			do_action( 'remove_user_role', $this->ID, $role );
		}

		/**
		 * Set the role of the user.
		 *
		 * This will remove the previous roles of the user and assign the user the
		 * new one. You can set the role to an empty string and it will remove all
		 * of the roles from the user.
		 *
		 * @param string $role Role name.
		 *
		 * @since 2.0.0
		 *
		 */
		public function set_role( $role ) {
			if ( 1 === count( $this->roles ) && current( $this->roles ) === $role ) {
				return;
			}

			foreach ( (array) $this->roles as $oldrole ) {
				unset( $this->caps[ $oldrole ] );
			}

			$old_roles = $this->roles;
			if ( ! empty( $role ) ) {
				$this->caps[ $role ] = true;
				$this->roles         = array( $role => true );
			} else {
				$this->roles = false;
			}
			// update_user_meta( $this->ID, $this->cap_key, $this->caps );
			$this->get_role_caps();
			$this->update_user_level_from_caps();

			/**
			 * Fires after the user's role has changed.
			 *
			 * @param int $user_id The user ID.
			 * @param string $role The new role.
			 * @param string[] $old_roles An array of the user's previous roles.
			 *
			 * @since 3.6.0 Added $old_roles to include an array of the user's previous roles.
			 *
			 * @since 2.9.0
			 */
			do_action( 'set_user_role', $this->ID, $role, $old_roles );
		}

		/**
		 * Choose the maximum level the user has.
		 *
		 * Will compare the level from the $item parameter against the $max
		 * parameter. If the item is incorrect, then just the $max parameter value
		 * will be returned.
		 *
		 * Used to get the max level based on the capabilities the user has. This
		 * is also based on roles, so if the user is assigned the Administrator role
		 * then the capability 'level_10' will exist and the user will get that
		 * value.
		 *
		 * @param int $max Max level of user.
		 * @param string $item Level capability name.
		 *
		 * @return int Max Level.
		 * @since 2.0.0
		 *
		 */
		public function level_reduction( $max, $item ) {
			if ( preg_match( '/^level_(10|[0-9])$/i', $item, $matches ) ) {
				$level = (int) $matches[1];

				return max( $max, $level );
			} else {
				return $max;
			}
		}

		/**
		 * Update the maximum user level for the user.
		 *
		 * Updates the 'user_level' user metadata (includes prefix that is the
		 * database table prefix) with the maximum user level. Gets the value from
		 * the all of the capabilities that the user has.
		 *
		 * @since 2.0.0
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		public function update_user_level_from_caps() {
			global $wpdb;
			$this->user_level = array_reduce( array_keys( $this->allcaps ), array( $this, 'level_reduction' ), 0 );
			// update_user_meta( $this->ID, $wpdb->get_blog_prefix() . 'user_level', $this->user_level );
		}

		/**
		 * Add capability and grant or deny access to capability.
		 *
		 * @param string $cap Capability name.
		 * @param bool $grant Whether to grant capability to user.
		 *
		 * @since 2.0.0
		 *
		 */
		public function add_cap( $cap, $grant = true ) {
			$this->caps[ $cap ] = $grant;
			// update_user_meta( $this->ID, $this->cap_key, $this->caps );
			$this->get_role_caps();
			$this->update_user_level_from_caps();
		}

		/**
		 * Remove capability from user.
		 *
		 * @param string $cap Capability name.
		 *
		 * @since 2.0.0
		 *
		 */
		public function remove_cap( $cap ) {
			if ( ! isset( $this->caps[ $cap ] ) ) {
				return;
			}
			unset( $this->caps[ $cap ] );
			// update_user_meta( $this->ID, $this->cap_key, $this->caps );
			$this->get_role_caps();
			$this->update_user_level_from_caps();
		}

		/**
		 * Remove all of the capabilities of the user.
		 *
		 * @since 2.1.0
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		public function remove_all_caps() {
			global $wpdb;
			$this->caps = array();
			// delete_user_meta( $this->ID, $this->cap_key );
			// delete_user_meta( $this->ID, $wpdb->get_blog_prefix() . 'user_level' );
			$this->get_role_caps();
		}

		/**
		 * Returns whether the user has the specified capability.
		 *
		 * This function also accepts an ID of an object to check against if the capability is a meta capability. Meta
		 * capabilities such as `edit_post` and `edit_user` are capabilities used by the `map_meta_cap()` function to
		 * map to primitive capabilities that a user or role has, such as `edit_posts` and `edit_others_posts`.
		 *
		 * Example usage:
		 *
		 *     $user->has_cap( 'edit_posts' );
		 *     $user->has_cap( 'edit_post', $post->ID );
		 *     $user->has_cap( 'edit_post_meta', $post->ID, $meta_key );
		 *
		 * While checking against a role in place of a capability is supported in part, this practice is discouraged as it
		 * may produce unreliable results.
		 *
		 * @param string $cap Capability name.
		 * @param mixed ...$args Optional further parameters, typically starting with an object ID.
		 *
		 * @return bool Whether the user has the given capability, or, if an object ID is passed, whether the user has
		 *              the given capability for that object.
		 * @since 2.0.0
		 * @since 5.3.0 Formalized the existing and already documented `...$args` parameter
		 *              by adding it to the function signature.
		 *
		 * @see map_meta_cap()
		 *
		 */
		public function has_cap( $cap, ...$args ) {
			if ( is_numeric( $cap ) ) {
				_deprecated_argument( __FUNCTION__, '2.0.0', __( 'Usage of user levels is deprecated. Use capabilities instead.' ) );
				$cap = $this->translate_level_to_cap( $cap );
			}

			$caps = map_meta_cap( $cap, $this->ID, ...$args );

			// Maintain BC for the argument passed to the "user_has_cap" filter.
			$args = array_merge( array( $cap, $this->ID ), $args );

			/**
			 * Dynamically filter a user's capabilities.
			 *
			 * @param bool[] $allcaps Array of key/value pairs where keys represent a capability name
			 *                          and boolean values represent whether the user has that capability.
			 * @param string[] $caps Required primitive capabilities for the requested capability.
			 * @param array $args {
			 *     Arguments that accompany the requested capability check.
			 *
			 * @type string    $0 Requested capability.
			 * @type int       $1 Concerned user ID.
			 * @type mixed  ...$2 Optional second and further parameters, typically object ID.
			 * }
			 *
			 * @param WP_User $user The user object.
			 *
			 * @since 2.0.0
			 * @since 3.7.0 Added the `$user` parameter.
			 *
			 */
			$capabilities = apply_filters( 'user_has_cap', $this->allcaps, $caps, $args, $this );

			// Everyone is allowed to exist.
			$capabilities['exist'] = true;

			// Nobody is allowed to do things they are not allowed to do.
			unset( $capabilities['do_not_allow'] );

			// Must have ALL requested caps.
			foreach ( (array) $caps as $cap ) {
				if ( empty( $capabilities[ $cap ] ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Convert numeric level to level capability name.
		 *
		 * Prepends 'level_' to level number.
		 *
		 * @param int $level Level number, 1 to 10.
		 *
		 * @return string
		 * @since 2.0.0
		 *
		 */
		public function translate_level_to_cap( $level ) {
			return 'level_' . $level;
		}

		/**
		 * Set the site to operate on. Defaults to the current site.
		 *
		 * @param int $blog_id Optional. Site ID, defaults to current site.
		 *
		 * @deprecated 4.9.0 Use WP_User::for_site()
		 *
		 * @since 3.0.0
		 */
		public function for_blog( $blog_id = '' ) {
			_deprecated_function( __METHOD__, '4.9.0', 'WP_User::for_site()' );

			$this->for_site( $blog_id );
		}

		/**
		 * Gets the ID of the site for which the user's capabilities are currently initialized.
		 *
		 * @return int Site ID.
		 * @since 4.9.0
		 *
		 */
		public function get_site_id() {
			return $this->site_id;
		}

		/**
		 * Gets the available user capabilities data.
		 *
		 * @return bool[] List of capabilities keyed by the capability name,
		 *                e.g. array( 'edit_posts' => true, 'delete_posts' => false ).
		 * @since 4.9.0
		 *
		 */
		private function get_caps_data() {
			$caps = array(
				'read'  => true,
				'write' => true,
			);

			return $caps;
		}
	}
}
