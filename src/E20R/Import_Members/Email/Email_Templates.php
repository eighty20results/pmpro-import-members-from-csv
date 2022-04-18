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

use E20R\Exceptions\InvalidInstantiation;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Import;
use E20R\Import_Members\Variables;

if ( ! class_exists( 'E20R\Import_Members\Email\Email_Templates' ) ) {
	/**
	 * Class Email_Templates
	 * @package E20R\Import_Members
	 */
	class Email_Templates {

		/**
		 * Instance of Email_Templates class
		 *
		 * @var null|Email_Templates
		 */
		private static $instance = null;

		/**
		 * Instance of Error_Log class
		 *
		 * @var Error_Log|null $error_log
		 */
		private $error_log = null;

		/**
		 * Instance of Variables class
		 *
		 * @var null|Variables $variables
		 */
		private $variables = null;

		/**
		 * @var null|Import The Import class
		 */
		private $import = null;

		/**
		 * Email_Templates constructor.
		 *
		 * @param Import|null $import Instance of the Import() class
		 *
		 * @throws InvalidInstantiation Thrown when the Import() class isn't present and instantiated when creating this class
		 * @throws InvalidSettingsKey Thrown when the Import::get() method attempts to access an invalid class property
		 */
		public function __construct( $import = null ) {
			if ( null === $import ) {
				throw new InvalidInstantiation(
					esc_attr__(
						'The Import() class was not instantiated correctly',
						'pmpro-import-members-from-csv'
					)
				);
			}

			$this->import    = $import;
			$this->variables = $this->import->get( 'variables' );
			$this->error_log = $this->import->get( 'error_log' );
		}

		/**
		 * Load Action and Filter hooks for this class
		 */
		public function load_hooks() {
			$this->error_log->debug( 'Loading action/filter hooks' );
			add_action( 'wp_loaded', array( $this, 'add_email_templates' ), 11 );
			add_action( 'wp_mail_failed', array( $this, 'mail_failure_handler' ), 10 );
			add_filter( 'e20r_import_message_body', array( $this, 'substitute_data' ), 1, 3 );
			add_filter( 'e20r_import_message_subject', array( $this, 'substitute_data' ), 1, 3 );
		}

		/**
		 * Maybe send the Import 'welcome imported user' Email message
		 *
		 * @param \WP_User $user
		 * @param array $fields
		 *
		 * @throws InvalidSettingsKey Thrown if the specified settings key is undefined
		 */
		public function maybe_send_email( $user, $fields ) {
			$send_email = (bool) $this->variables->get( 'send_welcome_email' );

			if ( version_compare( PMPRO_VERSION, '1.9.5', 'le' ) ) { // @phpstan-ignore-line
				$this->error_log->debug( 'Unable to send Email due to the specified PMPro version: ' . PMPRO_VERSION );

				return false;
			}

			if ( false === $send_email ) {
				$this->error_log->debug( 'Not sending Email because you asked me not to!' );

				return false;
			}

			if ( ! isset( $fields['membership_status'] ) || ( isset( $fields['membership_status'] ) && 'active' !== $fields['membership_status'] ) ) {
				$this->error_log->debug( "The membership_status field wasn't set to active: {$fields['membership_status']}" );

				return false;
			}

			// Email 'your membership account is active' to the member if they were imported with an active member status
			$template_name = apply_filters( 'e20r_import_welcome_email_template', 'imported_member' );
			$subject       = pmpro_getOption( "email_{$template_name}_subject" );
			$body          = pmpro_getOption( "email_{$template_name}_body" );

			global $pmproiufcsv_email;
			global $pmpro_email_templates_defaults;

			if ( ! empty( $pmproiufcsv_email ) ) {
				$subject = $pmproiufcsv_email['subject'];
				$body    = $pmproiufcsv_email['body'];
			}

			// Apply the saved
			if ( empty( $subject ) ) {
				$subject = pmpro_getOption( "email_{$template_name}_subject" );

				if ( empty( $subject ) ) {
					$subject = $pmpro_email_templates_defaults[ $template_name ]['subject'] ?? esc_attr__(
						'Your membership to !!sitename!! has been activated',
						'pmpro-import-members-from-csv'
					);
				}
			}

			$this->error_log->debug( "Using {$template_name} template for '{$subject}' message" );

			// The authors of PMPro are not good at defining properties in classes (badly reliant on the historically dynamic nature of PHP)
			// so will have PHPStan ignore these lines until PMPro cleans up their stuff
			// (i.e. TODO when PMPro takes better advantage of PHP)
			$email           = new \PMProEmail();
			$email->email    = $user->user_email; // @phpstan-ignore-line
			$email->data     = apply_filters( 'e20r_import_message_data', $user, $fields ); // @phpstan-ignore-line
			$email->subject  = apply_filters( 'e20r_import_message_subject', $subject, $user, $fields ); // @phpstan-ignore-line
			$email->template = $template_name; // @phpstan-ignore-line

			if ( ! empty( $body ) ) {
				$email->body = $body; // @phpstan-ignore-line
			} else {
				$email->body = $this->load_email_body( null, $email->template ); // @phpstan-ignore-line
			}

			$email->body    = apply_filters( 'pmp_im_imported_member_message_body', $email->body );
			$email->body    = apply_filters( 'e20r_import_message_body', $email->body, $user, $fields );
			$email->subject = apply_filters( 'pmp_im_imported_member_message_subject', $email->subject, $user, $fields );
			$email->subject = apply_filters( 'e20r_import_message_subject', $email->subject, $user, $fields );

			// Process and send Email
			$email->sendEmail();
			return true;
		}

		/**
		 * Substitute all in-message (body) !!something!! variables from the imported data
		 *
		 * @param string $substitution_text
		 * @param \WP_User $user
		 * @param array $fields
		 *
		 * @return string
		 */
		public function substitute_data( $substitution_text, $user, $fields ) {

			// PMPro not active
			if ( ! class_exists( '\MemberOrder' ) ) {
				return $substitution_text;
			}

			if ( 1 !== preg_match( '/!![a-zA-Z\-_].*!!/', $substitution_text ) ) {
				$this->error_log->debug( 'No Metadata substitution data found' );
			}

			// Substitute all membership information from the user's metadata
			foreach ( $fields as $meta_key => $meta_value ) {
				$this->error_log->debug( "For metadata: Try to substitute !!{$meta_key}!! with {$meta_value}" );
				$substitution_text = str_replace( "!!{$meta_key}!!", $meta_value, $substitution_text );
			}

			// Substitute WP_User information from the user's WP_User data
			foreach ( (array) $user as $user_key => $user_value ) {
				$this->error_log->debug( "Fpr User data: Try to substitute !!{$user_key}!! with {$user_value}" );
				$substitution_text = str_replace( "!!{$user_key}!!", $user_value, $substitution_text );
			}

			return $substitution_text;
		}

		/**
		 * Load the body of the template
		 *
		 * @param string $body
		 * @param string $template_name
		 *
		 * @return string|null
		 *
		 * @since v2.50 - ENHANCEMENT: Include imported_member template in the  Email Template Admin add-on
		 */
		public function load_email_body( $body, $template_name ) {
			$this->error_log->debug( "Loading template text for {$template_name}" );

			if ( ! empty( $body ) ) {
				return $body;
			}

			if ( ! function_exists( 'pmpro_getOption' ) ) {
				return $body;
			}

			$email_text = '';

			global $pmpro_email_templates_defaults;

			// Email disabled?
			if ( true === (bool) pmpro_getOption( "email_{$template_name}_disabled}" ) ) {
				return null;
			}

			// Not in the list of templates?
			if ( empty( $pmpro_email_templates_defaults[ $template_name ] ) ) {
				return null;
			}

			$this->error_log->debug( "Setting the message subject for {$template_name} template" );

			$template_body   = pmpro_getOption( "email_{$template_name}_body" );
			$template_header = pmpro_getOption( 'email_header_body' );
			$template_footer = pmpro_getOption( 'email_footer_body' );

			// Header disabled?
			if ( true !== (bool) pmpro_getOption( 'email_header_disabled' ) ) {
				if ( ! empty( $template_header ) ) {
					$email_text = $template_header;
				} else {
					$email_text = $this->load_template_part( 'header' );
				}
			}

			/**
			 * Have body content?
			 */
			if ( ! empty( $template_body ) ) {
				$email_text .= $template_body;
			} else {
				// Load the body content from a HTML template (somewhere)
				$email_text .= $this->load_template_part( $template_name );
			}

			// Footer disabled?
			if ( true === (bool) pmpro_getOption( 'email_footer_disabled' ) ) {
				if ( ! empty( $template_footer ) ) {
					$email_text .= $template_footer;
				} else {
					$email_text .= $this->load_template_part( 'footer' );
				}
			}

			return $email_text;
		}

		/**
		 * Load the body for the Email message to send to the updated/new member
		 *
		 * @param string|null $template
		 *
		 * @return string|null
		 *
		 * @since v2.50 - ENHANCEMENT: Include imported_member template in the  Email Template Admin add-on
		 */
		private function load_template_part( $template ) {

			global $pmpro_email_templates_defaults;

			$locale = apply_filters( 'plugin_locale', get_locale(), 'pmpro-import-members-from-csv' );
			$body   = null;

			// Load template from PMPro Email Templates Admin add-on
			if (
				isset( $pmpro_email_templates_defaults[ $template ]['body'] ) &&
				! empty( $pmpro_email_templates_defaults[ $template ]['body'] )
			) {
				$body = $pmpro_email_templates_defaults[ $template ]['body'];
			} else {
				$locations = array(
					get_stylesheet_directory() . "/pmpro-import-members-from-csv/{$locale}/{$template}.html",
					get_stylesheet_directory() . "/pmpro-import-members-from-csv/{$template}.html",
					get_template_directory() . "/pmpro-import-members-from-csv/{$locale}/{$template}.html",
					get_template_directory() . "/pmpro-import-members-from-csv/{$template}.html",
					plugin_dir_path( __FILE__ ) . "/languages/emails/{$locale}/{$template}.html",
					plugin_dir_path( __FILE__ ) . "/emails/${template}.html",
				);

				$locations = apply_filters( 'pmpro_import_members_template_locations', $locations, $template );

				// Try to locate the template file in the file system
				foreach ( $locations as $path ) {
					if ( true === file_exists( $path ) ) {
						$body = file_get_contents( $path ); // phpcs:ignore
						break;
					}
				}
			}

			return $body;
		}

		/**
		 * Get or instantiate and get this class
		 *
		 * @return null|Email_Templates
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Load Imported Member template to Email Templates Admin (add-on)
		 *
		 * @throws InvalidSettingsKey Thrown if the specified settings key is missing
		 *
		 * @since v2.50 - ENHANCEMENT: Allow editing imported_member.html in the Email Templates Admin add-on
		 */
		public function add_email_templates() {

			global $pmpro_email_templates_defaults;

			$this->error_log->debug( 'Attempting to load template for the Welcome Imported Member message' );

			$pmpro_email_templates_defaults['imported_member'] = array(
				'subject'     => esc_attr__( 'Welcome to my new website', 'pmpro-import-members-from-csv' ),
				'description' => esc_attr__( 'Import: Welcome Imported Member', 'pmpro-import-members-from-csv' ),
				// phpcs:ignore
				'body'        => file_get_contents( $this->import->get( 'plugin_path' ) . '/emails/imported_member.html' ),
			);
		}

		/**
		 * Add message to /wp-admin/ when having problem(s) with sending wp_mail() message
		 *
		 * @param \WP_Error $error
		 */
		public function mail_failure_handler( $error ) {

			$this->error_log->add_error_msg(
				sprintf(
				// translators: %s - Error message supplied
					__( 'Unable to send Email message from Import operation: %s', 'pmpro-import-members-from-csv' ),
					$error->get_error_message( 'wp_mail_failed' )
				),
				'warning'
			);
		}
	}
}
