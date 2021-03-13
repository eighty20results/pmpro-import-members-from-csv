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

namespace E20R\Import_Members\Modules\Users;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import_Members;
use E20R\Import_Members\Status;
use E20R\Import_Members\Validate\Create_Password;
use E20R\Import_Members\Validate\User_ID;
use E20R\Import_Members\Validate\User_Update;
use E20R\Import_Members\Validate\Validate;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Validate\Date_Format;
use E20R\Import_Members\Validate\Time;
use WP_Error;

class Import_User {

	/**
	 * The instance of the Import_User class (singleton pattern)
	 *
	 * @var null|Import_User $instance
	 */
	private static $instance = null;

	/**
	 * Import_User constructor.
	 *
	 * Hide/protect the constructor for this class (singleton pattern)
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 *
	 * @return Import_User|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load action and filter hooks for import
	 */
	public function load_actions() {
		add_action( 'e20r-import-usermeta', array( $this, 'import_usermeta' ), - 1 );
	}

	/**
	 * Process and import user data/user meta data
	 *
	 * @param mixed[]  $user_data
	 * @param mixed[]  $user_meta
	 * @param string[] $headers
	 *
	 * @throws \Exception
	 *
	 * @return int[]
	 */
	public function import( $user_data, $user_meta, $headers ) {

		global $e20r_import_err;
		global $active_line_number;

		if ( ! is_array( $e20r_import_err ) ) {
			$e20r_import_err = array();
		}

		$variables      = Variables::get_instance();
		$error_log      = Error_Log::get_instance();
		$display_errors = $variables->get( 'display_errors' );
		$user_ids       = array();
		$msg_target     = 'admin';
		$site_id        = $variables->get( 'site_id' );

		// Something to be done before importing one user?
		do_action( 'is_iu_pre_user_import', $user_data, $user_meta );
		do_action( 'pmp_im_pre_member_import', $user_data, $user_meta );
		do_action( 'e20r_pre_user_import', $user_data, $user_meta );

		$user_id      = false;
		$user         = $user_id;
		$allow_update = (bool) $variables->get( 'update_users' );

		/**
		 * BUG FIX: Didn't ensure the ID column contained an integer
		 */
		$user_id_exists = User_ID::validate( $user_data, $allow_update );

		if ( true === $user_id_exists ) {
			$user = get_user_by( 'ID', $user_data['ID'] );
		} else {
			$e20r_import_err[] = User_ID::status_msg( $user_id_exists, $allow_update );
			$error_log->debug( print_r( $e20r_import_err, true ) );
		}

		$error_log->debug( 'User data received: ' . print_r( $user_data, true ) );

		if ( empty( $user ) && true === $allow_update ) {

			if ( empty( $user ) && isset( $user_data['user_email'] ) ) {
				$user = get_user_by( 'email', $user_data['user_email'] );
			}

			if ( empty( $user ) && isset( $user_data['user_login'] ) ) {
				$user = get_user_by( 'login', $user_data['user_login'] );
			}
		}

		$needs_update = false;

		if ( ! empty( $user ) ) {
			$user_data['ID'] = $user->ID;
			$needs_update    = true;
		}

		if ( ! in_array( 'user_login', $headers, true ) &&
			 empty( $user ) &&
			 ! empty( $user_data['user_email'] ) &&
			 is_email( $user_data['user_email'] )
		) {

			$msg = sprintf(
				// translators: %d row number
				__(
					'Created user login field for record at row %d',
					Import_Members::PLUGIN_SLUG
				),
				$active_line_number
			);

			$login                   = preg_replace( '/@.*/', '', $user_data['user_email'] );
			$user_data['user_login'] = preg_replace( '/-|\.|\_|\+/', '', $login );

			$e20r_import_err[ "warn_login_created_{$active_line_number}" ] = new WP_Error( 'e20r_im_login', $msg );
		}

		if ( empty( $user_data['user_email'] ) || ( ! empty( $user_data['user_email'] ) && ! is_email( $user_data['user_email'] ) ) ) {

			$msg = sprintf(
				// translators: %d row number
				__( 'Invalid email in row %d (Not imported).', Import_Members::PLUGIN_SLUG ),
				( $active_line_number )
			);

			$e20r_import_err[ "warn_invalid_email_{$active_line_number}" ] = new WP_Error(
				'e20r_im_email',
				$msg,
				isset( $user_data['user_email'] ) ? $user_data['user_email'] : null
			);

			return $user_ids;
		}

		$error_column = User_Update::validate( $user_data, $allow_update );

		if ( true === $needs_update && false === $allow_update && true !== $error_column ) {

			$msg = sprintf(
				// translators: %1$s column name, %2$s: row number
				__(
					'No "%1$s" column found, or the "%1$s" was/were included, the user exists but the "Update user record" option was not selected (row: %2$d). Not imported!',
					Import_Members::PLUGIN_SLUG
				),
				$error_column,
				$active_line_number ++
			);

			$e20r_import_err[ "user_update_not_allowed_{$active_line_number}" ] = new WP_Error( 'e20r_im_login', $msg );

			return $user_ids;
		}

		// If creating a new user and no password was set, let auto-generate one!
		if ( true === Create_Password::validate( $user_data, $allow_update ) ) {
			$user_data['user_pass'] = wp_generate_password( 12, false );
		}

		$password_hashing_disabled = (bool) $variables->get( 'password_hashing_disabled' );

		// Insert, Update or insert without (re) hashing the password
		if ( true === $needs_update && false === $password_hashing_disabled ) {
			$user_id = wp_update_user( $user_data );
		} elseif ( false === $needs_update && false === $password_hashing_disabled ) {
			$user_id = wp_insert_user( $user_data );
		} elseif ( true === $password_hashing_disabled ) {
			$user_id = self::insert_disabled_hashing_user( $user_data );
		} else {
			$active_line_number ++;

			$e20r_import_err[ "user_not_imported_{$active_line_number}" ] =
				new WP_Error(
					'e20r_im_account',
					sprintf(
						// translators: %s email address
						__( 'No update/insert action taken for %s', Import_Members::PLUGIN_SLUG ),
						$user_data['user_email']
					)
				);

			return $user_ids;
		}

		$default_role = apply_filters( 'pmp_im_import_default_user_role', 'subscriber', $user_id, $site_id );
		$default_role = apply_filters( 'e20r_import_default_user_role', $default_role, $user_id, $site_id );

		// Is there an error?
		if ( is_wp_error( $user_id ) ) {
			$e20r_import_err[ $active_line_number ] = $user_id;
		} else {

			// If no error, let's update the user meta too!
			if ( ! empty( $user_meta ) ) {
				foreach ( $user_meta as $meta_key => $meta_value ) {
					$meta_value = maybe_unserialize( $meta_value );
					update_user_meta( $user_id, $meta_key, $meta_value );
				}
			}

			// Set the password nag as needed
			if ( true === (bool) $variables->get( 'password_nag' ) ) {
				update_user_option( $user_id, 'default_password_nag', true, true );
			}

			// Adds the user to the specified blog ID if we're in a multi-site configuration
			$site_id = (int) $variables->get( 'site_id' );

			if ( is_multisite() && ! empty( $site_id ) ) {
				add_user_to_blog( $site_id, $user_id, $default_role );
			}

			// If we created a new user, send new user notification?
			if ( false === $needs_update ) {

				$new_user_notification       = (bool) $variables->get( 'new_user_notification' );
				$admin_new_user_notification = (bool) $variables->get( 'admin_new_user_notification' );

				// Only to the user?
				if ( true === $new_user_notification && false === $admin_new_user_notification ) {
					$msg_target = 'user';
				}

				// Only to the admin?
				if ( false === $new_user_notification && true === $admin_new_user_notification ) {
					$msg_target = 'admin';
				}

				// To the user _and_ the admin?
				if ( true === $new_user_notification && true === $admin_new_user_notification ) {
					$msg_target = 'both';
				}

				if ( true === $new_user_notification || true === $admin_new_user_notification ) {
					wp_new_user_notification( $user_id, null, $msg_target );
				}
			}

			if ( ! empty( $user_data['user_registered'] ) && true === Time::validate( $user_data['user_registered'] ) ) {

				// Update/set the user_registered value if the user is registered already.
				$update_registered = array(
					'ID'              => $user_id,
					'user_registered' => $user_data['user_registered'],
				);

				$status = wp_update_user( $update_registered );

				if ( is_wp_error( $status ) ) {
					$e20r_import_err[] = $status;

					$should_be = Time::convert( $user_data['user_registered'] );
					$should_be = ( false === $should_be ? current_time( 'timestamp' ) : $should_be );

					$display_errors['user_registered'] = sprintf(
						// translators: %1$s column format, %2$s html, %3$s closing html, %4$s expected format
						__(
							'The %2$suser_registered column%3$s contains an unrecognized date/time format. (Your format: \'%1$s\'. Expected: \'%4$s\')',
							Import_Members::PLUGIN_SLUG
						),
						$user_data['user_registered'],
						'<strong>',
						'</strong>',
						date_i18n( 'Y-m-d h:i:s', $should_be )
					);
				}
			}

			$settings = $variables->get();

			// Some plugins may need to do things after one user has been imported. Who know?
			do_action( 'is_iu_post_user_import', $user_id, $settings );
			do_action( 'pmp_im_post_member_import', $user_id, $settings );
			do_action( 'e20r_after_user_import', $user_id, $settings );

			$user_ids[] = $user_id;
		}

		$variables->set( 'display_errors', $display_errors );

		return $user_ids;
	}

	/**
	 * Insert an user into the database.
	 * Copied from wp-include/user.php and commented wp_hash_password part
	 *
	 * @param mixed[]|\WP_User $userdata
	 *
	 * @return int|WP_Error
	 *
	 * @since 2.0.1
	 *
	 **/
	public function insert_disabled_hashing_user( $userdata ) {

		global $wpdb;

		$old_user_data = null;

		if ( is_a( $userdata, 'stdClass' ) ) {
			$userdata = get_object_vars( $userdata );
		} elseif ( is_a( $userdata, 'WP_User' ) ) {
			$userdata = $userdata->to_array();
		}
		// Are we updating or creating?
		if ( ! empty( $userdata['ID'] ) ) {

			$id            = (int) $userdata['ID'];
			$update        = true;
			$old_user_data = \WP_User::get_data_by( 'id', $id );

			// hashed in wp_update_user(), plaintext if called directly
			// $user_pass = $userdata['user_pass'];

		} else {
			$update = false;
			// Hash the password
			// $user_pass = wp_hash_password( $userdata['user_pass'] );
		}
		$user_pass            = $userdata['user_pass'];
		$sanitized_user_login = sanitize_user( $userdata['user_login'], true );

		/**
		 * Filter a username after it has been sanitized.
		 *
		 * This filter is called before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $sanitized_user_login Username after it has been sanitized.
		 */
		$pre_user_login = apply_filters( 'pre_user_login', $sanitized_user_login );

		//Remove any non-printable chars from the login string to see if we have ended up with an empty username
		$user_login = trim( $pre_user_login );

		if ( empty( $user_login ) ) {
			return new WP_Error(
				'empty_user_login',
				__( 'Cannot create a user with an empty login name.', Import_Members::PLUGIN_SLUG )
			);
		}

		if ( false === $update && username_exists( $user_login ) ) {
			return new WP_Error(
				'existing_user_login',
				sprintf(
					// translators: %s username (login name)
					__( 'Sorry, that username (%s) already exists!', Import_Members::PLUGIN_SLUG ),
					$user_login
				)
			);
		}

		if ( empty( $userdata['user_nicename'] ) ) {
			$user_nicename = sanitize_title( $user_login );
		} else {
			$user_nicename = $userdata['user_nicename'];
		}

		// Store values to save in user meta.
		$meta = array();

		/**
		 * Filter a user's nicename before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $user_nicename The user's nicename.
		 */
		$user_nicename = apply_filters( 'pre_user_nicename', $user_nicename );
		$raw_user_url  = empty( $userdata['user_url'] ) ? '' : $userdata['user_url'];

		/**
		 * Filter a user's URL before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $raw_user_url The user's URL.
		 */
		$user_url       = apply_filters( 'pre_user_url', $raw_user_url );
		$raw_user_email = empty( $userdata['user_email'] ) ? '' : $userdata['user_email'];

		/**
		 * Filter a user's email before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $raw_user_email The user's email.
		 */
		$user_email = apply_filters( 'pre_user_email', $raw_user_email );
		if ( false === $update && ! defined( 'WP_IMPORTING' ) && email_exists( $user_email ) ) {
			return new WP_Error(
				'existing_user_email',
				sprintf(
					// translators: %s email address
					__( 'Sorry, that email address (%s) is already used!', Import_Members::PLUGIN_SLUG ),
					$user_email
				)
			);
		}

		$nickname = empty( $userdata['nickname'] ) ? $user_login : $userdata['nickname'];

		/**
		 * Filter a user's nickname before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $nickname The user's nickname.
		 */
		$meta['nickname'] = apply_filters( 'pre_user_nickname', $nickname );
		$first_name       = empty( $userdata['first_name'] ) ? '' : $userdata['first_name'];

		/**
		 * Filter a user's first name before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $first_name The user's first name.
		 */
		$meta['first_name'] = apply_filters( 'pre_user_first_name', $first_name );
		$last_name          = empty( $userdata['last_name'] ) ? '' : $userdata['last_name'];

		/**
		 * Filter a user's last name before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $last_name The user's last name.
		 */
		$meta['last_name'] = apply_filters( 'pre_user_last_name', $last_name );
		if ( empty( $userdata['display_name'] ) ) {
			if ( true === $update ) {
				$display_name = $user_login;
			} elseif ( $meta['first_name'] && $meta['last_name'] ) {
				/* translators: 1: first name, 2: last name */
				$display_name = sprintf(
					// translators: %1$s first name, $2$s last name/surname
					_x(
						'%1$s %2$s',
						'Display name based on first name and last name',
						Import_Members::PLUGIN_SLUG
					),
					$meta['first_name'],
					$meta['last_name']
				);

			} elseif ( $meta['first_name'] ) {
				$display_name = $meta['first_name'];
			} elseif ( $meta['last_name'] ) {
				$display_name = $meta['last_name'];
			} else {
				$display_name = $user_login;
			}
		} else {
			$display_name = $userdata['display_name'];
		}
		/**
		 * Filter a user's display name before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $display_name The user's display name.
		 */
		$display_name      = apply_filters( 'pre_user_display_name', $display_name );
		$alt_user_nicename = $display_name;
		$description       = empty( $userdata['description'] ) ? '' : $userdata['description'];

		/**
		 * Filter a user's description before the user is created or updated.
		 *
		 * @since 2.0.1
		 *
		 * @param string $description The user's description.
		 */
		$meta['description']          = apply_filters( 'pre_user_description', $description );
		$meta['rich_editing']         = ( empty( $userdata['rich_editing'] ) ? 'true' : $userdata['rich_editing'] );
		$meta['comment_shortcuts']    = ( empty( $userdata['comment_shortcuts'] ) ? 'false' : $userdata['comment_shortcuts'] );
		$admin_color                  = ( empty( $userdata['admin_color'] ) ? 'fresh' : $userdata['admin_color'] );
		$meta['admin_color']          = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $admin_color );
		$meta['use_ssl']              = ( empty( $userdata['use_ssl'] ) ? 0 : $userdata['use_ssl'] );
		$user_registered              = ( empty( $userdata['user_registered'] ) ? gmdate( 'Y-m-d H:i:s' ) : $userdata['user_registered'] );
		$meta['show_admin_bar_front'] = ( empty( $userdata['show_admin_bar_front'] ) ? 'true' : $userdata['show_admin_bar_front'] );

		$user_nicename_check = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_nicename = %s AND user_login != %s LIMIT 1",
				$user_nicename,
				$user_login
			)
		);

		if ( ! empty( $user_nicename_check ) ) {

			$suffix = 2;

			while ( $user_nicename_check ) {

				$alt_user_nicename   = $user_nicename . "-$suffix";
				$user_nicename_check = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->users} WHERE user_nicename = %s AND user_login != %s LIMIT 1",
						$alt_user_nicename,
						$user_login
					)
				);
				$suffix ++;
			}

			$user_nicename = $alt_user_nicename;
		}

		$compacted = compact( 'user_pass', 'user_email', 'user_url', 'user_nicename', 'display_name', 'user_registered' );

		$data = wp_unslash( $compacted );

		if ( true === $update ) {
			$wpdb->update( $wpdb->users, $data, compact( 'ID' ) );
			$user_id = (int) $id;
		} else {
			$wpdb->insert( $wpdb->users, $data + compact( 'user_login' ) );
			$user_id = (int) $wpdb->insert_id;
		}
		$user = new \WP_User( $user_id );
		// Update user meta.
		foreach ( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}
		foreach ( wp_get_user_contact_methods( $user ) as $key => $value ) {
			if ( isset( $userdata[ $key ] ) ) {
				update_user_meta( $user_id, $key, $userdata[ $key ] );
			}
		}
		if ( isset( $userdata['role'] ) ) {
			$user->set_role( $userdata['role'] );
		} elseif ( false === $update ) {
			$user->set_role( get_option( 'default_role' ) );
		}
		wp_cache_delete( $user_id, 'users' );
		wp_cache_delete( $user_login, 'userlogins' );
		if ( true === $update ) {
			/**
			 * Fires immediately after an existing user is updated.
			 *
			 * @since 2.0.1
			 *
			 * @param int    $user_id       User ID.
			 * @param object $old_user_data Object containing user's data prior to update.
			 */
			do_action( 'profile_update', $user_id, $old_user_data );
		} else {
			/**
			 * Fires immediately after a new user is registered.
			 *
			 * @since 2.0.1
			 *
			 * @param int $user_id User ID.
			 */
			do_action( 'user_register', $user_id );
		}

		return $user_id;
	}

	/**
	 * Change some of the imported columns to add "imported_" to the front so we don't confuse the data later.
	 *
	 * @param array $user_meta
	 * @param array $user_data
	 *
	 * @return array
	 */
	public function import_usermeta( $user_meta, $user_data ) {

		$variables = Variables::get_instance();

		foreach ( $user_meta as $key => $value ) {

			if ( in_array( $key, array_keys( $variables->get( 'fields' ) ), true ) ) {
				$key = "imported_{$key}";
			}

			$user_meta[ $key ] = $value;
		}

		return $user_meta;
	}

	/**
	 * Hide/protect the __clone() magic method for this class (singleton pattern)
	 *
	 * @access private
	 */
	private function __clone() {
		// TODO: Implement __clone() method.
	}
}
