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

namespace E20R\Import_Members;

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
	 * Email_Templates constructor.
	 */
	private function __construct() {
		$this->error_log = new Error_Log(); //phpcs:ignore
		$this->variables = new Variables();
	}

	/**
	 * Load Action and Filter hooks for this class
	 */
	public function load_hooks() {

		add_action( 'wp_loaded', array( $this, 'add_email_templates' ), 11 );
		add_action( 'wp_mail_failed', array( $this, 'mail_failure_handler' ) );
	}

	/**
	 * Maybe send the import 'welcome imported user' email message
	 *
	 * @param \WP_User $user
	 */
	public function maybe_send_email( $user ) {
		global $pmproiufcsv_email;

		$send_email = $this->variables->get( 'send_welcome_email' );
		$fields     = $this->variables->get( 'fields' );

		// Email 'your membership account is active' to member if they were imported with an active member status
		if ( true === (bool) $send_email &&
			 isset( $fields['membership_status'] ) && 'active' === $fields['membership_status'] &&
			 1 === version_compare( PMPRO_VERSION, '1.9.5' )
		) {
			$subject = null;
			$body    = null;

			if ( ! empty( $pmproiufcsv_email ) ) {
				$subject = apply_filters( 'pmp_im_imported_member_message_subject', $pmproiufcsv_email['subject'] );
				$body    = apply_filters( 'pmp_im_imported_member_message_body', $pmproiufcsv_email['body'] );
			}

			global $pmproet_email_defaults;

			$template_name = 'imported_member';

			// Apply the saved
			if ( empty( $subject ) ) {
				$subject = pmpro_getOption( "email_{$template_name}_subject" );

				if ( empty( $subject ) ) {
					$subject = $pmproet_email_defaults[ $template_name ]['subject'] ?? __( 'Your membership to !!sitename!! has been activated', 'pmpro-import-members-from-csv' );
				}
			}

			$this->error_log->debug( "Using {$template_name} template for '{$subject}' message" );

			$email           = new \PMProEmail();
			$email->email    = $user->user_email;
			$email->data     = apply_filters( 'pmp_im_imported_member_message_data', array() );
			$email->subject  = $subject;
			$email->template = $template_name;

			if ( ! empty( $body ) ) {
				$email->body = $body;
			} else {
				$email->body = $this->load_email_body( null, $email->template );
			}

			$email->body = apply_filters( 'pmp_im_imported_member_message_body', $email->body );

			// Process and send email
			$email->sendEmail();
		}
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
	public function load_email_body( $body = null, $template_name ) {
		$this->error_log->debug( "Loading template text for {$template_name}" );

		if ( ! empty( $body ) ) {
			return $body;
		}

		if ( ! function_exists( 'pmpro_getOption' ) ) {
			return $body;
		}

		$email_text = '';

		global $pmproet_email_defaults;

		// Email disabled?
		if ( true === (bool) pmpro_getOption( "email_{$template_name}_disabled}" ) ) {
			return null;
		}

		// Not in the list of templates?
		if ( empty( $pmproet_email_defaults[ $template_name ] ) ) {
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
	 * Load the body for the email message to send to the updated/new member
	 *
	 * @param string|null $template
	 *
	 * @return string|null
	 *
	 * @since v2.50 - ENHANCEMENT: Include imported_member template in the  Email Template Admin add-on
	 */
	private function load_template_part( $template ) {

		global $pmproet_email_defaults;

		$locale = apply_filters( 'plugin_locale', get_locale(), 'pmpro-import-members-from-csv' );
		$body   = null;

		// Load template from PMPro Email Templates Admin add-on
		if ( isset( $pmproet_email_defaults[ $template ]['body'] ) && ! empty( $pmproet_email_defaults[ $template ]['body'] ) ) {
			$body = $pmproet_email_defaults[ $template ]['body'];
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
					$body = file_get_contents( $path );
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
	 * @since v2.50 - ENHANCEMENT: Allow editing imported_member.html in the Email Templates Admin add-on
	 */
	public function add_email_templates() {

		global $pmproet_email_defaults;

		$this->error_log->debug( 'Attempting to load template for the Welcome Imported Member message' );

		$pmproet_email_defaults['imported_member'] = array(
			'subject'     => __( 'Welcome to my new website', 'pmpro-import-members-from-csv' ),
			'description' => __( 'Import: Welcome Member', 'pmpro-import-members-from-csv' ),
			'body'        => file_get_contents( Import_Members::$plugin_path . '/emails/imported_member.html' ),
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
				__( 'Unable to send email message from Import operation: %s', 'pmpro-import-members-from-csv' ),
				$error->get_error_message( 'wp_mail_failed' )
			),
			'warning'
		);
	}
}