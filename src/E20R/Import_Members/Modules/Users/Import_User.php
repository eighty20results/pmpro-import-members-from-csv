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

namespace E20R\Import_Members\Modules\Users;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Exceptions\UserIDAlreadyExists;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Status;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Validate\Time;
use WP_Error;
use WP_User;

if ( ! class_exists( 'E20R\Import_Members\Modules\Users\Import_User' ) ) {
	class Import_User {

		/**
		 * Instance of the Variables class
		 *
		 * @var Variables|null $variables
		 */
		private $variables = null;

		/**
		 * Instance of the Error_Log class
		 *
		 * @var Error_Log|null $error_log
		 */
		private $error_log = null;

		/**
		 * Class used to check if the user exists on the system already (validation)
		 *
		 * @var null|User_Present
		 */
		private $user_presence = null;

		/**
		 * Class to check validity of the password record (if it is included)
		 *
		 * @var Create_Password|mixed|null
		 */
		private $passwd_validator = null;

		/**
		 * Import_User constructor.
		 *
		 * @param null|Variables $variables Instance of the Variables() class
		 * @param null|Error_Log $error_log Instance of the Error_Log() class
		 * @param null|User_Present $user_presence Instance of the tests for user existence on the system
		 * @param null|Create_Password $passwd_validator Instance of the tests for the user password
		 *
		 * @access private
		 */
		public function __construct( $variables = null, $error_log = null, $user_presence = null, $passwd_validator = null ) {
			if ( null === $error_log ) {
				$error_log = new Error_Log(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			$this->error_log = $error_log;

			if ( null === $variables ) {
				$variables = new Variables( $this->error_log );
			}
			$this->variables = $variables;

			if ( null === $user_presence ) {
				$user_presence = new User_Present( $this->variables, $this->error_log );
			}
			$this->user_presence = $user_presence;

			if ( null === $passwd_validator ) {
				$passwd_validator = new Create_Password( $this->variables, $this->error_log );
			}
			$this->passwd_validator = $passwd_validator;
		}

		/**
		 * Load action and filter hooks for Import
		 */
		public function load_actions() {
			add_filter( 'e20r_import_usermeta', array( $this, 'import_usermeta' ), - 1, 3 );
		}

		/**
		 * Process and Import user data/user meta data
		 *
		 * @param array         $user_data Array of data from the CSV file we will import as WP_User data
		 * @param array         $user_meta Array of metadata for the user being imported
		 * @param string[]      $headers The file headers/import headers supplied
		 * @param WP_Error|null $wp_error For unit testing. Default and expected received value should be null.
		 *
		 * @return int|null
		 * @throws InvalidSettingsKey Thrown when we specify an invalid setting (variable)
		 */
		public function import( $user_data, $user_meta, $headers, $wp_error = null ) {

			global $e20r_import_err;
			global $e20r_import_warn;
			global $active_line_number;

			if ( null === $wp_error ) {
				$wp_error = new WP_Error();
			}

			if ( ! is_array( $e20r_import_err ) ) {
				$e20r_import_err = array();
			}

			if ( ! is_array( $e20r_import_warn ) ) {
				$e20r_import_warn = array();
			}

			$display_errors  = $this->variables->get( 'display_errors' );
			$allow_update    = (bool) $this->variables->get( 'update_users' );
			$allow_id_update = (bool) $this->variables->get( 'update_id' );
			$msg_target      = '';
			$site_id         = $this->variables->get( 'site_id' );

			if ( empty( $display_errors ) ) {
				$display_errors = array();
			}

			// Something to be done before importing one user?
			do_action( 'is_iu_pre_user_import', $user_data, $user_meta );
			do_action( 'pmp_im_pre_member_import', $user_data, $user_meta );
			do_action( 'e20r_before_user_import', $user_data, $user_meta );

			$user_id = 0;
			$user    = false;

			/**
			 * Can the user from the import data be found on the system
			 */
			$user_exists = $this->user_presence->validate( $user_data, $allow_update );

			if ( Status::E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED === $user_exists ) {
				$msg = sprintf(
				// translators: %1$d: Current user ID, %2$d: User ID from import file, %3$d: Current line in import file
					esc_attr__(
						'The import data specifies an existing user but the settings disallow updating their record (line: %3$d)',
						'pmpro-import-members-from-csv'
					),
					$user_id,
					$user_data['ID'],
					$active_line_number
				);

				$new_error = $wp_error;
				$new_error->add( 'user_exists_no_update', $msg );
				$e20r_import_warn[ "user_exists_cannot_update_{$active_line_number}" ] = $new_error;
				$this->error_log->debug( $msg );
				return null;
			}

			if ( true === $user_exists ) {
				$this->error_log->debug( 'Loading user because we know it exists already' );
				$user = $this->find_user( $user_data );

				if ( ! empty( $user->ID ) ) {
					$user_id = $user->ID;
				}
			}

			if ( false === $allow_id_update && ( ! empty( $user_id ) && ! empty( $user_data['ID'] ) && $user_id !== (int) $user_data['ID'] ) ) {
				$msg = sprintf(
					// translators: %1$d: Current user ID, %2$d: User ID from import file, %3$d: Current line in import file
					esc_attr__(
						'The import data and the WP User data are different. Not allowed to update %1$d to %2$d (line: %3$d)',
						'pmpro-import-members-from-csv'
					),
					$user_id,
					$user_data['ID'],
					$active_line_number
				);

				$new_error = $wp_error;
				$new_error->add( 'id_mismatch', $msg );
				$e20r_import_warn[ "id_mismatch_{$active_line_number}" ] = $new_error;
				$this->error_log->debug( $msg );
				return null;

			} elseif ( true === $allow_id_update && ! empty( $user_id ) && ! empty( $user_data['ID'] ) && $user_id !== (int) $user_data['ID'] ) {
				$this->error_log->debug( 'Warning: Updating the user ID in the WordPress Users DB table!' );
				try {
					$new_error = $wp_error;
					$user      = $this->update_user_id( $user, $user_data );
				} catch ( InvalidSettingsKey $e ) {
					$this->error_log->debug( $e->getMessage() );
					$new_error->add( 'unexpected_key', $e->getMessage() );
					$e20r_import_err[ "unexpected_key_{$active_line_number}" ] = $new_error;
					return null;
				} catch ( UserIDAlreadyExists $e ) {
					$new_error->add( 'preexisting_user_id', $e->getMessage() );
					$e20r_import_err[ "preexisting_user_id_{$active_line_number}" ] = $new_error;
					$this->error_log->debug( $e->getMessage() );
					return null;
				}

				if ( ! empty( $user->ID ) ) {
					$user_id = $user->ID;
				}
			}

			$password_hashing_disabled = (bool) $this->variables->get( 'password_hashing_disabled' );
			$create_password           = $this->passwd_validator->validate(
				$user_data,
				$allow_update,
				( $user_exists ? $user : null )
			);

			// If creating a new user and no password was set, let auto-generate one!
			$default_password_length = apply_filters( 'e20r_import_password_length', 12 );

			if ( true === $create_password && empty( $user_data['user_pass'] ) ) {
				$this->error_log->debug( 'Generating new password for user' );
				$user_data['user_pass'] = wp_generate_password( $default_password_length, false );
			}

			if ( false === $user && false === $password_hashing_disabled ) {
				$this->error_log->debug( 'Adding new user record' );
				$user_id = wp_insert_user( $user_data );
			} elseif ( // pre-hashed password and we'll allow both updating and adding user if other settings let us
				( false === $user && true === $password_hashing_disabled ) ||
				( false !== $user && true === $password_hashing_disabled && true === $allow_update )
			) {
				if ( is_a( $user, 'WP_User' ) && empty( $user_data['ID'] ) ) {
					$user_data['ID'] = $user->ID;
				}
				$user_id = $this->insert_or_update_disabled_hashing_user( $user_data );
			} elseif ( ! empty( $user_id ) && true === $allow_update ) {
				// Update and support hashing the of any $user_data['user_pass'] string
				if ( empty( $user_data['ID'] ) ) {
					$user_data['ID'] = $user_id;
				}

				$user_id = wp_update_user( $user_data );
				if ( is_wp_error( $user_id ) ) {
					$this->error_log->debug( "Error updating user ID {$user_data['ID']}" );
					$e20r_import_err[ "data_not_updated_{$active_line_number}" ] = $user_id;
					return null;
				}
				$this->error_log->debug( "Existing user with ID {$user_id} has been updated" );

			} else {
				$new_error = $wp_error;
				$new_error->add(
					'e20r_im_account',
					sprintf(
							// translators: %1$s: Email address, %2$d: Row number from CSV file
						esc_attr__( 'No update/insert action taken for %1$s, on row %2$d', 'pmpro-import-members-from-csv' ),
						$user_data['user_email'],
						$active_line_number
					)
				);
				$e20r_import_warn[ "user_not_imported_{$active_line_number}" ] = $new_error;
				return null;
			}

			// Is there an error?
			if ( is_wp_error( $user_id ) ) {
				$e20r_import_err[ $active_line_number ] = $user_id;
				$user_id                                = null;
			} else {

				if ( ! empty( $user_id ) ) {
					$user = $this->find_user( array( 'ID' => $user_id ) );
				}

				/**
				 * Identify any new roles to add for the user (and add them)
				 */
				$default_role = 'subscriber';
				$default_role = apply_filters( 'pmp_im_import_default_user_role', $default_role, $user_id, $site_id );
				$default_role = apply_filters( 'e20r_import_default_user_role', $default_role, $user_id, $site_id );
				$all_roles    = array( $default_role );

				if ( ! empty( $user_data['role'] ) ) {
					$roles      = array_map( 'trim', explode( ';', $user_data['role'] ) );
					$all_roles += $roles;
				}

				if ( ! empty( $all_roles ) ) {
					foreach ( $all_roles as $role_name ) {
						$this->error_log->debug( "Adding role '{$role_name}' for user ID {$user_id}" );
						$user->add_role( $role_name );
					}
				}

				$updated = wp_update_user( $user );

				if ( is_wp_error( $updated ) ) {
					$e20r_import_err[ "save_error_{$active_line_number}" ] = $updated;
					$this->error_log->debug( $updated->get_error_message() );
					return null;
				} else {
					$this->error_log->debug( "User {$user_id} is saved..." );
				}

				// Adds the user to the specified blog ID if we're in a multi-site configuration
				$site_id = (int) $this->variables->get( 'site_id' );

				if ( is_multisite() && ! empty( $site_id ) ) {
					add_user_to_blog( $site_id, $user_id, $default_role );
				}

				// If no error, let's update the user meta too!
				if ( ! empty( $user_meta ) ) {
					foreach ( $user_meta as $meta_key => $meta_value ) {
						$meta_value = maybe_unserialize( $meta_value );
						update_user_meta( $user_id, $meta_key, $meta_value );
					}
				}

				// Set the password nag as needed
				if ( true === (bool) $this->variables->get( 'password_nag' ) ) {
					update_user_option( $user_id, 'default_password_nag', true, true );
				}

				// If we created a new user, send new user notification?
				$new_user_notification       = (bool) $this->variables->get( 'new_user_notification' );
				$admin_new_user_notification = (bool) $this->variables->get( 'admin_new_user_notification' );

				// Only to the user?
				if ( true === $new_user_notification && false === $admin_new_user_notification ) {
					$msg_target = 'user';
				} elseif ( false === $new_user_notification && true === $admin_new_user_notification ) {
					// Only to the admin?
					$msg_target = 'admin';
				} elseif ( true === $new_user_notification && true === $admin_new_user_notification ) {
					// To the user _and_ the admin?
					$msg_target = 'both';
				}

				if ( ( false !== $user && true === $allow_update ) && ( true === $new_user_notification || true === $admin_new_user_notification ) ) {
					wp_new_user_notification( $user_id, null, $msg_target );
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
						$should_be = ( false === $should_be ? time() : $should_be );

						$display_errors['user_registered'] = sprintf(
						// translators: %1$s column format, %2$s html, %3$s closing html, %4$s expected format
							esc_attr__(
								'The %2$suser_registered column%3$s contains an unrecognized date/time format. (Your format: \'%1$s\'. Expected: \'%4$s\')',
								'pmpro-import-members-from-csv'
							),
							$user_data['user_registered'],
							'<strong>',
							'</strong>',
							date_i18n( 'Y-m-d h:i:s', $should_be )
						);
					}
				}

				$settings = $this->variables->get();

				// Some plugins may need to do things after one user has been imported. Who knows?
				do_action( 'is_iu_post_user_import', $user_id, $settings );
				do_action( 'pmp_im_post_member_import', $user_id, $settings );
				do_action( 'e20r_after_user_import', $user_id, $user_data, $user_meta );
			}

			$this->variables->set( 'display_errors', $display_errors );
			return $user_id;
		}

		/**
		 * Locate the user based on the import data (if present on the system)
		 *
		 * @param array $user_data The data from the row being imported from the CSV file
		 *
		 * @return false|WP_User
		 */
		public function find_user( $user_data ) {
			$user = false;

			$this->error_log->debug(
				'User exists. Could have searched by user_email - ' .
				( isset( $user_data['user_email'] ) ? 'Yes' : 'No' ) .
				'. Or maybe use user_login? ' .
				( isset( $user_data['user_login'] ) ? 'Yes' : 'No' )
			);

			$id_fields = array(
				'ID'         => 'ID',
				'user_login' => 'login',
				'user_email' => 'email',
			);

			foreach ( $id_fields as $field_name => $user_by_field ) {
				if ( isset( $user_data[ $field_name ] ) && ! empty( $user_data[ $field_name ] ) ) {
					$user = get_user_by( $user_by_field, $user_data[ $field_name ] );
					if ( isset( $user->ID ) && ! empty( $user->ID ) ) {
						$this->error_log->debug( 'Found user: ' . $user->ID );
						break;
					}
				}
			}

			return $user;
		}


		/**
		 * Change the user ID to match the import data
		 *
		 * @param WP_User $wp_user User record currently on the system
		 * @param array $user_data The data we're going to be importing
		 *
		 * @return WP_User|false
		 *
		 * @throws UserIDAlreadyExists Thrown if the "target" local User ID already has a user
		 * @throws InvalidSettingsKey Thrown if the 'update_id' variable, for some inexplicable reason, no longer exists
		 */
		public function update_user_id( $wp_user, $user_data ) {
			global $active_line_number;

			$wp_user_id     = $wp_user->ID;
			$import_user_id = (int) $user_data['ID'];

			if ( $wp_user_id === $import_user_id ) {
				return $wp_user;
			}

			$existing_user = get_user_by( 'ID', $import_user_id );
			if ( false !== $existing_user && true === (bool) $this->variables->get( 'update_id' ) ) {
				throw new UserIDAlreadyExists(
					sprintf(
						// translators: %1$d: line number in CSV import file %2$d: email address of existing user with same user ID
						esc_attr__(
							'The expected user ID from line %1$d in the CSV file already exists but belongs to a different user (%2$s)!',
							'pmpro-import-members-from-csv'
						),
						$active_line_number,
						$existing_user->user_email
					)
				);

			}

			global $wpdb;
			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->users} SET ID = %d WHERE `ID` = %d",
					$import_user_id,
					$wp_user_id
				)
			);

			return get_user_by( 'ID', $import_user_id );
		}

		/**
		 * Custom import function for adding/updating a WordPress user to the database (with a pre-hashed password)
		 *
		 * Copied from wp-include/user.php and commented wp_hash_password part
		 *
		 * @param array|WP_User $userdata
		 *
		 * @return int|WP_Error
		 *
		 * @since 2.0.1
		 *
		 **/
		public function insert_or_update_disabled_hashing_user( $userdata ) {

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
				$old_user_data = WP_User::get_data_by( 'id', $id );

				// hashed in the wp_update_user function, plaintext if called directly
				// $user_pass = $userdata['user_pass'];

			} else {
				$update = false;
				$id     = null;
				// Here we're supposed to hash the password
				// $user_pass = wp_hash_password( $userdata['user_pass'] );
			}
			$user_pass            = $userdata['user_pass'];
			$sanitized_user_login = sanitize_user( $userdata['user_login'], true );

			/**
			 * Filter a username after it has been sanitized.
			 *
			 * This filter is called before the user is created or updated.
			 *
			 * @param string $sanitized_user_login Username after it has been sanitized.
			 *
			 * @since 2.0.1
			 *
			 */
			$pre_user_login = apply_filters( 'pre_user_login', $sanitized_user_login );

			//Remove any non-printable chars from the login string to see if we have ended up with an empty username
			$user_login = trim( $pre_user_login );

			if ( empty( $user_login ) ) {
				return new WP_Error(
					'empty_user_login',
					__( 'Cannot create a user with an empty login name.', 'pmpro-import-members-from-csv' )
				);
			}

			if ( false === $update && username_exists( $user_login ) ) {
				return new WP_Error(
					'existing_user_login',
					sprintf(
					// translators: %s username (login name)
						__( 'Sorry, that username (%1$s) already exists!', 'pmpro-import-members-from-csv' ),
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
			 * @param string $user_nicename The user's nicename.
			 *
			 * @since 2.0.1
			 *
			 */
			$user_nicename = apply_filters( 'pre_user_nicename', $user_nicename );
			$raw_user_url  = empty( $userdata['user_url'] ) ? '' : $userdata['user_url'];

			/**
			 * Filter a user's URL before the user is created or updated.
			 *
			 * @param string $raw_user_url The user's URL.
			 *
			 * @since 2.0.1
			 *
			 */
			$user_url       = apply_filters( 'pre_user_url', $raw_user_url );
			$raw_user_email = empty( $userdata['user_email'] ) ? '' : $userdata['user_email'];

			/**
			 * Filter a user's Email before the user is created or updated.
			 *
			 * @param string $raw_user_email The user's Email.
			 *
			 * @since 2.0.1
			 *
			 */
			$user_email = apply_filters( 'pre_user_email', $raw_user_email );
			if ( false === $update && ! defined( 'WP_IMPORTING' ) && email_exists( $user_email ) ) {
				return new WP_Error(
					'existing_user_email',
					sprintf(
					// translators: %s Email address
						__( 'Sorry, that Email address (%s) is already used!', 'pmpro-import-members-from-csv' ),
						$user_email
					)
				);
			}

			$nickname = empty( $userdata['nickname'] ) ? $user_login : $userdata['nickname'];

			/**
			 * Filter a user's nickname before the user is created or updated.
			 *
			 * @param string $nickname The user's nickname.
			 *
			 * @since 2.0.1
			 *
			 */
			$meta['nickname'] = apply_filters( 'pre_user_nickname', $nickname );
			$first_name       = empty( $userdata['first_name'] ) ? '' : $userdata['first_name'];

			/**
			 * Filter a user's first name before the user is created or updated.
			 *
			 * @param string $first_name The user's first name.
			 *
			 * @since 2.0.1
			 *
			 */
			$meta['first_name'] = apply_filters( 'pre_user_first_name', $first_name );
			$last_name          = empty( $userdata['last_name'] ) ? '' : $userdata['last_name'];

			/**
			 * Filter a user's last name before the user is created or updated.
			 *
			 * @param string $last_name The user's last name.
			 *
			 * @since 2.0.1
			 *
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
							'pmpro-import-members-from-csv'
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
			 * @param string $display_name The user's display name.
			 *
			 * @since 2.0.1
			 *
			 */
			$display_name      = apply_filters( 'pre_user_display_name', $display_name );
			$alt_user_nicename = $display_name;
			$description       = empty( $userdata['description'] ) ? '' : $userdata['description'];

			/**
			 * Filter a user's description before the user is created or updated.
			 *
			 * @param string $description The user's description.
			 *
			 * @since 2.0.1
			 *
			 */
			$meta['description']          = apply_filters( 'pre_user_description', $description );
			$meta['rich_editing']         = ( empty( $userdata['rich_editing'] ) ? 'true' : $userdata['rich_editing'] );
			$meta['comment_shortcuts']    = ( empty( $userdata['comment_shortcuts'] ) ? 'false' : $userdata['comment_shortcuts'] );
			$admin_color                  = ( empty( $userdata['admin_color'] ) ? 'fresh' : $userdata['admin_color'] );
			$meta['admin_color']          = preg_replace( '|[^a-z\d _.\-@]|i', '', $admin_color );
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
				$wpdb->update( $wpdb->users, $data, compact( 'id' ) );
				$user_id = (int) $id;
			} else {
				$wpdb->insert( $wpdb->users, $data + compact( 'user_login' ) );
				$user_id = (int) $wpdb->insert_id;
			}
			$user = new WP_User( $user_id );
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
				 * @param int $user_id User ID.
				 * @param object $old_user_data Object containing user's data prior to update.
				 *
				 * @since 2.0.1
				 *
				 */
				do_action( 'profile_update', $user_id, $old_user_data );
			} else {
				/**
				 * Fires immediately after a new user is registered.
				 *
				 * @param int $user_id User ID.
				 *
				 * @since 2.0.1
				 *
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
		 * @param array $headers
		 *
		 * @return array
		 */
		public function import_usermeta( $user_meta, $user_data, $headers ) {

			try {
				$meta_keys = $this->variables->get( 'fields' );
			} catch ( InvalidSettingsKey $e ) {
				$this->error_log->debug( 'Unexpected: ' . $e->getMessage() );
				return $user_meta;
			}

			foreach ( $user_meta as $key => $value ) {
				if ( in_array( $key, array_keys( $meta_keys ), true ) ) {
					$key = "imported_{$key}";
				}

				$user_meta[ $key ] = $value;
			}

			return $user_meta;
		}
	}
}
