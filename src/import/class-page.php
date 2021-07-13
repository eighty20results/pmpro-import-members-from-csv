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
 * Copyright (c) 2019. - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Import_Members\Import;

use E20R\Import_Members\Error_Log;
use E20R\Import_Members\Variables;
use E20R\Import_Members\Import_Members;
use E20R\Utilities\Utilities;

class Page {

	/**
	 * @var null|Page
	 */
	private static $instance = null;

	/**
	 * Page constructor.
	 */
	private function __construct() {
	}

	/**
	 * @return Page|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load action and filter hooks
	 */
	public function load_hooks() {

		add_action( 'admin_menu', array( $this, 'add_admin_pages' ), 10 );
		add_action( 'admin_notices', array( $this, 'display_admin_message' ), 9 );
		add_action( 'admin_bar_menu', array( $this, 'load_to_pmpro_menu' ), 1001 );
	}

	/**
	 * Add admin notice to WP Admin backedn
	 */
	public function display_admin_message() {

		$error_msgs = get_option( 'e20r_imesc_attr_error_msg', array() );

		if ( ! empty( $error_msgs ) && is_admin() ) {

			foreach ( $error_msgs as $msg_info ) { ?>
			<div class="notice notice-<?php esc_attr_e( $msg_info['type'] ); //phpcs:ignore ?> is-dismissible">
				<p><strong><?php esc_attr_e( $msg_info['message'] ) // phpcs:ignore; ?></strong></p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php __( 'Dismiss this message.', 'pmpro-import-members-from-csv' ); ?></span>
				</button>
				</div>
				<?php
			}

			// Clear error/warning/notice/success messages
			delete_option( 'e20r_imesc_attr_error_msg' );
		}
	}

	/**
	 * Add to Memberships Menu in WP Back-end (left sidebar)
	 *
	 * @since 2.1
	 **/
	public function add_admin_pages() {

		$error_log = new Error_Log(); // phpcs:ignore
		$error_log->debug( 'Add submenu to Memberships menu item. Headers sent? ' . ( headers_sent() ? 'Yes' : 'No' ) );

		add_submenu_page(
			'pmpro-membershiplevels',
			__( 'Import Members', 'pmpro-import-members-from-csv' ),
			__( 'Import Members', 'pmpro-import-members-from-csv' ),
			'create_users',
			'pmpro-import-members-from-csv',
			array( $this, 'import_page' )
		);
	}

	/**
	 * Add Import Members to the PMPro Members drop-down menu
	 * @since 2.1
	 */
	public function load_to_pmpro_menu() {

		global $wp_admin_bar;

		$error_log = new Error_Log(); // phpcs:ignore
		$error_log->debug( 'Load the Import Members menu item. Headers sent? ' . ( headers_sent() ? 'Yes' : 'No' ) );

		if ( current_user_can( 'create_users' ) ) {
			$wp_admin_bar->add_menu(
				array(
					'id'     => 'pmpro-import-members-from-csv',
					'parent' => 'paid-memberships-pro',
					'title'  => __( 'Import Members', 'pmpro-import-members-from-csv' ),
					'href'   => add_query_arg( 'page', 'pmpro-import-members-from-csv', get_admin_url( null, 'admin.php' ) ),
				)
			);
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function import_page() {

		if ( ! current_user_can( 'create_users' ) ) {
			wp_die(
				esc_attr__(
					'You do not have sufficient permissions to access this page.',
					'pmpro-import-members-from-csv'
				)
			);
		}

		if ( is_multisite() ) {
			$current_blog_id = get_current_blog_id();

			if ( 1 !== $current_blog_id && false === Import_Members::is_pmpro_active() ) {
				?>
				<div class="notice notice-error is-dismissable">
					<?php
					esc_attr_e(
						'Paid Memberships Pro is not activated on this sub-site. This import tool will not behave as expected!',
						'pmpro-import-members-from-csv'
					);
					?>
				</div>
				<?php
			}
		}

		$all_levels = Import_Members::is_pmpro_active() ? pmpro_getAllLevels( true, true ) : array();
		$variables  = new Variables();
		$error_log  = new Error_Log(); // phpcs:ignore

		if ( empty( $all_levels ) ) {
			?>
			<div class="notice notice-error is-dismissable">
				<?php esc_attr_e( "This site has no defined membership levels. Without them, this tool can't import any membership data for your users.", 'pmpro-import-members-from-csv' ); ?>
			</div>
			<?php
		}
		?>
		<div class="wrap">
		<h2><?php esc_attr_e( 'Import PMPro members from a CSV file', 'pmpro-import-members-from-csv' ); ?></h2>
			<?php
            // phpcs:ignore
            printf(
				'<div id="e20r-status" %1$s></div>',
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				( ! isset( $_REQUEST['import'] ) ? 'style="display: none;"' : 'style="display: inline-block;"' )
			);
			$efh = fopen( $variables->get( 'logfile_path' ), 'a' ); //phpcs:ignore
			// phpcs::ignore
			if ( false === $efh ) {
				printf(
					'<div class="updated"><p><strong>%1$s</strong></p></div>',
					sprintf(
						// translators: %s - Path to log file
						esc_attr__( 'Note: Please make the %s directory writable to see/save the error log.', 'pmpro-import-members-from-csv' ),
						esc_attr__( $variables->get( 'logfile_path' ) ) // phpcs:ignore
					)
				);
			}
			// phpcs:ignore
			@fwrite(
				$efh,
				sprintf(
						// translators: %s - Timestamp for import operation
					__( "BEGIN %s\n", 'pmpro-import-members-from-csv' ),
					date_i18n(
						'Y-m-d H:i:s',
						time()
					)
				)
			);

			// File was writable and accessible
            fclose( $efh ); // phpcs:ignore
			$nonce = wp_nonce_field(
				'e20r-im-import-members',
				'e20r-im-import-members-wpnonce',
				true,
				false
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['import'] ) ) {
				$error_log_msg = '';

				if ( filesize( $variables->get( 'logfile_path' ) ) > 0 ) {
					$error_log_msg = sprintf(
							// translators: $1$s HTML, %2, HTML
						__( ', please %1$scheck the error log%2$s', 'pmpro-import-members-from-csv' ),
						sprintf( '<a href="%1$s">', esc_url_raw( $variables->get( 'logfile_url' ) ) ),
						'</a>'
					);
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				switch ( $_REQUEST['import'] ) {
					case 'file':
						// phpcs:ignore
						printf(
							'<div class="error"><p><strong>%1$s</strong></p></div>',
							esc_attr__( 'Error during file upload.', 'pmpro-import-members-from-csv' )
						);
						break;
					case 'data':
						// phpcs:ignore
						printf(
							'<div class="error"><p><strong>%1$s</strong></p></div>',
							esc_attr__(
								'Cannot extract data from uploaded file or no file was uploaded.',
								'pmpro-import-members-from-csv'
							)
						);
						break;
					case 'fail':
						// phpcs:ignore
						printf(
							'<div class="error"><p><strong>%1$s</strong></p></div>',
							sprintf(
								// translators: %1$s - Error message from import operation
								esc_attr__(
									'No members imported: %1$s.',
									'pmpro-import-members-from-csv'
								),
								esc_html__( $error_log_msg ) // phpcs:ignore
							)
						);
						break;
					case 'errors':
						// phpcs:ignore
						printf(
							'<div class="error"><p><strong>%1$s</strong></p></div>',
							sprintf(
								// translators: %1$s - Error message from import operation
								esc_attr__(
									'Some members were successfully imported, but some were not: %1$s.',
									'pmpro-import-members-from-csv'
								),
								esc_html__( $error_log_msg ) // phpcs:ignore
							)
						);
						break;
					case 'success':
						// phpcs:ignore
						printf(
							'<div class="updated"><p><strong>%1$s</strong></p></div>',
							esc_attr__(
								'Member import was successful.',
								'pmpro-import-members-from-csv'
							)
						);
						break;
					default:
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_REQUEST['import'] ) && 'resume' === $_REQUEST['import'] && ! empty( $_REQUEST['filename'] ) ) {

					// phpcs:ignore $filename = sanitize_file_name( $_REQUEST['filename'] );

					// Resetting position option?
					if ( ! empty( $_REQUEST['reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$file = basename( $variables->get( 'filename' ) );
						delete_option( "e20rcsv_{$file}" );
					}
					?>
				<h3><?php esc_attr_e( 'Importing your members using AJAX (in the background)', 'pmpro-import-members-from-csv' ); ?></h3>
				<p><strong><?php esc_attr_e( 'IMPORTANT:', 'pmpro-import-members-from-csv' ); ?></strong>
					<?php
					printf(
							// translators: %1$s HTML, %2$s HTML, %3$s - wp-admin URL, %4$s HTML
						esc_attr__(
							'Your import is not finished. %1$sClosing this page will stop the import operation%2$s. If the import stops or you have to close your browser, you can navigate to %3$sthis link%4$s and resume the import operation later.',
							'pmpro-import-members-from-csv'
						),
						'<strong>',
						'</strong>',
						sprintf(
							'<a href="%1$s">',
							esc_url_raw( admin_url( 'admin.php' . "?{$_SERVER['QUERY_STRING']}" ) )
						),
						'</a>'
					);
					?>
				</p>
				<p>
					<a id="pauseimport" href="#"><?php esc_attr_e( 'Click here to pause.', 'pmpro-import-members-from-csv' ); ?></a>
					<a id="resumeimport" href="#" style="display:none;"><?php esc_attr_e( 'Paused. Click here to resume.', 'pmpro-import-members-from-csv' ); ?></a>
				</p>

				<textarea id="importstatus" rows="10" cols="60"><?php esc_attr_e( 'Loading...', 'pmpro-import-members-from-csv' ); ?></textarea>
				<p class="complete_btn">
					<?php echo $nonce; // phpcs:ignore ?>
					<input type="button" class="button-primary" id="completed_import" value="<?php esc_attr_e( 'Finished', 'pmpro-import-members-from-csv' ); ?>" style="display:none;"/>
				</p>
					<?php
				}
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_REQUEST['filename'] ) ) {

				$has_donated    = false;
				$current_client = Ajax::get_instance()->get_client_ip();

				/**
				 * Add "donations welcome" button info if the user hasn't recently donated/clicked the button
				 */
				if ( ! empty( $current_client ) ) {

					$donated_from = get_option( 'e20r_import_has_donated', array() );

					if ( in_array( $current_client, array_keys( $donated_from ), true ) ) {

						if ( ! empty( $donated_from[ $current_client ] ) ) {

							// Get the timestamp when they clicked the 'donate' button
							$when = strtotime(
								date_i18n(
									'Y-m-d H:i:s',
									$donated_from[ $current_client ]
								) . ' +2 month',
								time()
							);

							// Need to clean up
							if ( $when < time() ) {
								$error_log->debug( "Removing 'donated_from' entry for {$current_client}" );

								unset( $donated_from[ $current_client ] );
								update_option( 'e20r_import_has_donated', $donated_from );
							} else {
								$has_donated = true;
							}
						}
					}
				}
				?>
			<div class="e20r-donation-button">
				<?php if ( false === $has_donated ) { ?>
				<p class="e20r-donation-text">
					<?php esc_attr_e( 'We have a donation button in case you wish to show your support for the continued development and support activities for the plugin.', 'pmpro-import-members-from-csv' ); ?></p>
				<p class="e20r-donation-text"><?php esc_attr_e( 'Whether you choose to donate or not is obviously up to you. We do hope the plugin has enough value for you to click on the "Donate" button and help us out, but we also get it if you can\'t at this time.', 'pmpro-import-members-from-csv' ); ?></p>
				<?php } ?>
				<p class="e20r-donation-text">
					<?php
					printf(
							// translators: %1$s - HTML, %2$s - URL to plugin review page, %3$s - HTML, %4$s - HTML
						esc_attr__(
							'%1$sIf you haven\'t reviewed our plugin, we would %2$sappreciate your honest feedback%3$s!%4$s',
							'pmpro-import-members-from-csv'
						),
						'<strong>',
						sprintf(
							'<a href="%1$s">',
							esc_url_raw( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv/reviews/#new-post' )
						),
						'</a>',
						'</strong>'
					);
					?>
				</p>
				<?php
				if ( false === $has_donated ) {
					?>
				<p class="e20r-donation-text">
					<?php
					printf(
							// translators: %1$s - HTML, %2$s - HTML
						esc_attr__(
							'Regardless, please accept our sincere %1$sthanks%2$s for choosing our plugin.',
							'pmpro-import-members-from-csv'
						),
						'<strong>',
						'</strong>'
					);
					?>
				</p>
				<div class="e20r-center-button">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" id="e20r-import-donation">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="YR423UJ7AZJFJ">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" id="e20r_donation_button">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
				<p class="e20r-donation-text"><?php esc_attr_e( 'PS: If you click the "Donate" button, this reminder will disappear for a while', 'pmpro-import-members-from-csv' ); ?></p>
			<?php } ?>
			</div>
			<hr/>
			<form method="post" action="" id="e20r-import-form" enctype="multipart/form-data">
				<?php echo $nonce; // phpcs:ignore ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="members_csv">
							<?php
							esc_attr_e( 'CSV file to load', 'pmpro-import-members-from-csv' );
							?>
							</label>
						</th>
						<td>
							<?php
							if ( filesize( $variables->get( 'logfile_path' ) ) > 0 ) {
								?>
							<div style="float: right;">
								<input class="e20r-import-clear-log button button-primary" type="button" id="clear_log_btn" value="<?php esc_attr_e( 'Clear log', 'pmpro-import-members-from-csv' ); ?>"/>
								<a href="<?php echo esc_url_raw( $variables->get( 'logfile_url' ) ); ?>" target="_blank">
									<input class="e20r-import-clear-log button button-secondary" type="button" id="view_log_btn" value="<?php esc_attr_e( 'View log', 'pmpro-import-members-from-csv' ); ?>"/>
								</a>
							</div>
							<?php } ?>
							<div style="float: left">
								<input type="file" id="members_csv" name="members_csv" value="" class="all-options" accept=".csv, text/csv" about="<?php __( 'Select .CSV file to process', 'pmpro-import-members-from-csv' ); ?>"/><br/>
								<span class="description">
									<?php
									printf(
											// translators: %1$s - HTML link to example CSV file, %2$s - HTML
										esc_attr__( 'You may want to download and review %1$sthe example CSV file%2$s.', 'pmpro-import-members-from-csv' ),
										sprintf(
											'<a href="%1$s" target="_blank">',
											esc_url_raw(
												plugins_url( '/examples/import.csv', Import_Members::$plugin_path . '/class.pmpro-import-members.php' )
											)
										),
										'</a>'
									);
									?>
								</span>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Update user record', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Users update', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="update_users">
									<input id="update_users" name="update_users" type="checkbox" value="1" checked="checked"/>
									<?php
									esc_attr_e(
										'Update, do not add, the user if the username or email already exists (Recommended)',
										'pmpro-import-members-from-csv'
									);
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Deactivate existing membership', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Deactivate existing membership', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="deactivate_">
									<input id="deactivate_old_memberships" name="deactivate_old_memberships"  type="checkbox" value="1" checked="checked"/>
									<?php
									esc_attr_e( "Update the status when importing a user who already has an 'active' membership level (Recommended)", 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Add order', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Attempt to create PMPro Order record', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="update_users">
									<input id="create_order" name="create_order" type="checkbox" value="1"/>
									<?php
									esc_attr_e( 'Attempt to add a PMPro order record when required data is included in the import row', 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Send WordPress notification', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Send the WordPress new user/updated password notification to the user', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="new_user_notification">
									<input id="new_user_notification" name="new_user_notification" type="checkbox" value="1"/>
									<?php
									esc_attr_e( 'Send the WordPress new user/updated password notification to the user', 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Suppress "Password/Email Changed" notification', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Do not send the "Your password was changed" nor the "Your email address was changed" email messages to the updated user', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="suppress_pwdmsg">
									<input id="suppress_pwdmsg" name="suppress_pwdmsg" type="checkbox" value="1"/>
									<?php
									printf(
											// translators: %1$s HTML, %2$s HTML
										esc_attr__(
											'%1$sDo not send%2$s the "Your password was changed" nor the "Your email address was changed" email messages to the updated user',
											'pmpro-import-members-from-csv'
										),
										'<strong>',
										'</strong>'
									);
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<!-- send_welcome_email -->
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Send WordPress notification to admin', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Send WordPress new user/updated user notification to admin', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="admin_new_user_notification">
									<input id="admin_new_user_notification" name="admin_new_user_notification" type="checkbox" value="1"/>
									<?php
									esc_attr_e( 'Send WordPress new user/updated user notification to admin', 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Send \'Welcome to the membership\' email', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Send the imported_member.html welcome email to the imported user', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="send_welcome_email">
									<input id="send_welcome_email" name="send_welcome_email" type="checkbox" value="1"/>
									<?php
									esc_attr_e( 'Send the imported_member.html welcome email to the imported user', 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<!--  -->
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Display password nag', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Password nag', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="password_nag">
									<input id="password_nag" name="password_nag" type="checkbox" value="1"/>
									<?php
									esc_attr_e( 'Show the password nag when the new user(s) log in', 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Password is already hashed', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Password is hashed', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="password_hashing_disabled">
									<input id="password_hashing_disabled" name="password_hashing_disabled" type="checkbox" value="1"/>
									<?php
									esc_attr_e( "The passsword in the .csv file is already hashed and doesn't need to be encrypted by the import process.", 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Background import', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Import the .csv file with resume functionality.', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<label for="background_import">
									<input id="background_import" name="background_import" type="checkbox" value="1" checked="checked"/>
									<?php
									esc_attr_e( 'Use a background process to import all of the records. (Recommeded)', 'pmpro-import-members-from-csv' );
									?>
								</label>
							</fieldset>
						</td>
					</tr>
					<?php
					if ( is_multisite() ) {
						global $current_blog_id;
						$site_list = get_sites();
						?>
						<tr valign="top">
						<th scope="row"><?php esc_attr_e( 'Site to import to', 'pmpro-import-members-from-csv' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_attr_e( 'Select the multisite instance to import these members to.', 'pmpro-import-members-from-csv' ); ?></span>
								</legend>
								<select id="site_id" name="site_id">
							<?php
							foreach ( $site_list as $site ) {
								// phpcs:ignore ?>
									<option value="<?php esc_attr_e( $site->blog_id ); ?>" <?php selected( $site->blog_id, $current_blog_id ); ?>><?php esc_html_e( $site->blogname ); ?></option>
									<?php
							}
							?>
								</select>
							</fieldset>
						</td>
						</tr>
						<?php
					}
					?>
					<?php do_action( 'e20r_import_page_setting_html' ); ?>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Import', 'pmpro-import-members-from-csv' ); ?>" id="e20r-import-form-submit"/>
				</p>
			</form>
				<?php
			}
			?>
		</div>
		<?php
	}
}
