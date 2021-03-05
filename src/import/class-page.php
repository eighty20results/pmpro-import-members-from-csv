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
		
		$error_msgs = get_option( 'e20r_im_error_msg', array() );
		
		if ( ! empty( $error_msgs ) && is_admin() ) {
			
			foreach ( $error_msgs as $key => $msg_info ) { ?>
            <div class="notice notice-<?php esc_attr_e( $msg_info['type'] ); ?> is-dismissible">
                <p><strong><?php echo $msg_info['message']; ?></strong></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php __( 'Dismiss this message.', Import_Members::PLUGIN_SLUG ); ?></span>
                </button>
                </div><?php
			}
			
			// Clear error/warning/notice/success messages
			delete_option( 'e20r_im_error_msg' );
		}
	}
	
	/**
	 * Add to Memberships Menu in WP Back-end (left sidebar)
	 *
	 * @since 2.1
	 **/
	public function add_admin_pages() {
		
		$utils = Utilities::get_instance();
		$utils->log( "Add submenu to Memberships menu item. Headers sent? " . (headers_sent() ? 'Yes' : 'No')  );
		
		
		add_submenu_page(
			'pmpro-membershiplevels',
			__( 'Import Members', Import_Members::PLUGIN_SLUG ),
			__( 'Import Members', Import_Members::PLUGIN_SLUG ),
			'create_users',
			Import_Members::PLUGIN_SLUG,
			array( $this, 'import_page' )
		);
	}
	
	/**
	 * Add Import Members to the PMPro Members drop-down menu
	 * @since 2.1
	 */
	public function load_to_pmpro_menu() {
		
		global $wp_admin_bar;
		
		$utils = Utilities::get_instance();
		$utils->log( "Load the Import Members menu item. Headers sent? " . (headers_sent() ? 'Yes' : 'No')  );
		
		if ( current_user_can( 'create_users' ) ) {
			$wp_admin_bar->add_menu( array(
					'id'     => Import_Members::PLUGIN_SLUG,
					'parent' => 'paid-memberships-pro',
					'title'  => __( 'Import Members', Import_Members::PLUGIN_SLUG ),
					'href'   => add_query_arg( 'page', Import_Members::PLUGIN_SLUG, get_admin_url( null, 'admin.php' ) ),
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
			wp_die( __( 'You do not have sufficient permissions to access this page.', Import_Members::PLUGIN_SLUG ) );
		}
		
		if ( is_multisite() ) {
			$current_blog_id = get_current_blog_id();
			
			if ( 1 !== $current_blog_id && false === Import_Members::is_pmpro_active() ) { ?>
                <div class="notice notice-error is-dismissable">
					<?php _e( "Paid Memberships Pro is not activated on this sub-site. This import tool will not behave as expected!", Import_Members::PLUGIN_SLUG ); ?>
                </div> <?php
			}
		}
		
		$all_levels = Import_Members::is_pmpro_active() ? pmpro_getAllLevels( true, true ) : array();
		$variables  = Variables::get_instance();
		
		if ( empty( $all_levels ) ) { ?>
            <div class="notice notice-error is-dismissable">
				<?php _e( "This site has no defined membership levels. Without them, this tool can't import any membership data for your users.", Import_Members::PLUGIN_SLUG ); ?>
            </div> <?php
		} ?>
        <div class="wrap">
        <h2><?php _e( 'Import PMPro members from a CSV file', Import_Members::PLUGIN_SLUG ); ?></h2><?php
		printf( '<div id="e20r-status" %s></div>', ( ! isset( $_REQUEST['import'] ) ? 'style="display: none;"' : 'style="display: inline-block;"' ) );
		
		if ( false === ( $efh = fopen( $variables->get( 'logfile_path' ), 'a' ) ) ) {
			
			printf( '<div class="updated"><p><strong>%s</strong></p></div>',
				sprintf(
					__( 'Note: Please make the %s directory writable to see/save the error log.', Import_Members::PLUGIN_SLUG ),
					$variables->get( 'logfile_path' )
				)
			);
		}
		
		@fwrite( $efh,
			sprintf(
				__( "BEGIN %s\n", Import_Members::PLUGIN_SLUG ),
				date( 'Y-m-d H:i:s', current_time( 'timestamp' )
				)
			)
		);
		
		// File was writable and accessible
		fclose( $efh );
		$nonce = wp_nonce_field( 'e20r-im-import-members', 'e20r-im-import-members-wpnonce', true, false );
		
		if ( isset( $_REQUEST['import'] ) ) {
			$error_log_msg = '';
			
			if ( filesize( $variables->get( 'logfile_path' ) ) > 0 ) {
				$error_log_msg = sprintf(
					__( ', please %1$scheck the error log%2$s', Import_Members::PLUGIN_SLUG ),
					sprintf( '<a href="%s">', esc_url_raw( $variables->get( 'logfile_url' ) ) ),
					'</a>'
				);
			}
			
			switch ( $_REQUEST['import'] ) {
				case 'file':
					printf( '<div class="error"><p><strong>%s</strong></p></div>', __( 'Error during file upload.', Import_Members::PLUGIN_SLUG ) );
					break;
				case 'data':
					printf( '<div class="error"><p><strong>%s</strong></p></div>', __( 'Cannot extract data from uploaded file or no file was uploaded.', Import_Members::PLUGIN_SLUG ) );
					break;
				case 'fail':
					printf( '<div class="error"><p><strong>%s</strong></p></div>', sprintf( __( 'No members imported%s.', Import_Members::PLUGIN_SLUG ), $error_log_msg ) );
					break;
				case 'errors':
					printf( '<div class="error"><p><strong>%s</strong></p></div>', sprintf( __( 'Some members were successfully imported, but some were not%s.', Import_Members::PLUGIN_SLUG ), $error_log_msg ) );
					break;
				case 'success':
					printf( '<div class="updated"><p><strong>%s</strong></p></div>', __( 'Member import was successful.', Import_Members::PLUGIN_SLUG ) );
					break;
				default:
			}
			
			if ( isset( $_REQUEST['import'] ) && $_REQUEST['import'] == 'resume' && ! empty( $_REQUEST['filename'] ) ) {
				
				// $filename = sanitize_file_name( $_REQUEST['filename'] );
				
				//Resetting position option?
				if ( ! empty( $_REQUEST['reset'] ) ) {
					$file = basename( $variables->get( 'filename' ) );
					delete_option( "e20rcsv_{$file}" );
				} ?>
                <h3><?php _e( 'Importing your members using AJAX (in the background)', Import_Members::PLUGIN_SLUG ); ?></h3>
                <p><strong><?php _e( 'IMPORTANT:', Import_Members::PLUGIN_SLUG ); ?></strong> <?php printf(
						__( 'Your import is not finished. %1$sClosing this page will stop the import operation%2$s. If the import stops or you have to close your browser, you can navigate to %3$sthis link%4$s and resume the import operation later.', Import_Members::PLUGIN_SLUG ),
						'<strong>',
						'</strong>',
						sprintf( '<a href="%s">', admin_url( 'admin.php' . "?{$_SERVER['QUERY_STRING']}" ) ),
						'</a>'
					); ?>
                </p>
                <p>
                    <a id="pauseimport"
                       href="#"><?php _e( "Click here to pause.", Import_Members::PLUGIN_SLUG ); ?></a>
                    <a id="resumeimport" href="#"
                       style="display:none;"><?php _e( "Paused. Click here to resume.", Import_Members::PLUGIN_SLUG ); ?></a>
                </p>

                <textarea id="importstatus" rows="10"
                          cols="60"><?php _e( 'Loading...', Import_Members::PLUGIN_SLUG ); ?></textarea>
                <p class="complete_btn">
				<?php echo $nonce; ?>
                <input type="button" class="button-primary" id="completed_import"
                       value="<?php _e( "Finished", Import_Members::PLUGIN_SLUG ); ?>" style="display:none;"/>
                </p><?php
			}
		}
		
		if ( empty( $_REQUEST['filename'] ) ) {
			
			$has_donated    = false;
			$current_client = Ajax::get_instance()->get_client_ip();
			
			/**
			 * Add "donations welcome" button info if the user hasn't recently donated/clicked the button
			 */
			if ( ! empty( $current_client ) ) {
				
				$donated_from = get_option( 'e20r_import_has_donated', array() );
				
				if ( in_array( $current_client, array_keys( $donated_from ) ) ) {
					
					if ( ! empty( $donated_from[ $current_client ] ) ) {
						
						// Get the timestamp when they clicked the 'donate' button
						$when = strtotime(
							date(
								'Y-m-d H:i:s',
								$donated_from[ $current_client ] ) . " +2 month",
							current_time( 'timestamp' )
						);
						
						// Need to clean up
						if ( $when < current_time( 'timestamp' ) ) {
							
							if ( WP_DEBUG ) {
								error_log( "Removing 'donated_from' entry for {$current_client}" );
							}
							
							unset( $donated_from[ $current_client ] );
							update_option( 'e20r_import_has_donated', $donated_from );
						} else {
							$has_donated = true;
						}
					}
				}
			} ?>
            <div class="e20r-donation-button">
				<?php if ( $has_donated === false ) { ?>
                    <p class="e20r-donation-text"><?php _e( "We have a donation button in case you wish to show your support for the continued development and support activities for the plugin.", Import_Members::PLUGIN_SLUG ); ?></p>
                    <p class="e20r-donation-text"><?php _e( 'Whether you choose to donate or not is obviously up to you. We do hope the plugin has enough value for you to click on the "Donate" button and help us out, but we also get it if you can\'t at this time.', Import_Members::PLUGIN_SLUG ); ?></p>
				<?php } ?>
                <p class="e20r-donation-text"><?php printf(
						__(
							'%1$sIf you haven\'t reviewed our plugin, we would %2$sappreciate your honest feedback%3$s!%4$s',
							Import_Members::PLUGIN_SLUG
						),
						'<strong>',
						sprintf( '<a href="%1$s">', esc_url( 'https://wordpress.org/support/plugin/pmpro-import-members-from-csv/reviews/#new-post' ) ),
						'</a>',
						'</strong>'
					); ?></p>
				<?php if ( $has_donated === false ) { ?>
                    <p class="e20r-donation-text"><?php printf( __( 'Regardless, please accept our sincere %1$sthanks%2$s for choosing our plugin.', Import_Members::PLUGIN_SLUG ), '<strong>', '</strong>' ); ?></p>
                    <div class="e20r-center-button">
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top"
                              id="e20r-import-donation">
                            <input type="hidden" name="cmd" value="_s-xclick">
                            <input type="hidden" name="hosted_button_id" value="YR423UJ7AZJFJ">
                            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif"
                                   border="0"
                                   name="submit" alt="PayPal - The safer, easier way to pay online!"
                                   id="e20r_donation_button">
                            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1"
                                 height="1">
                        </form>
                    </div>
                    <p class="e20r-donation-text"><?php _e( 'PS: If you click the "Donate" button, this reminder will disappear for a while', Import_Members::PLUGIN_SLUG ); ?></p>
				<?php } ?>
            </div>
            <hr/>
            <form method="post" action="" id="e20r-import-form" enctype="multipart/form-data">
				<?php echo $nonce; ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="members_csv">
								<?php _e( 'CSV file to load', Import_Members::PLUGIN_SLUG ); ?>
                            </label>
                        </th>
                        <td>
							<?php if ( filesize( $variables->get( 'logfile_path' ) ) > 0 ) { ?>
                                <div style="float: right;">
                                    <input class="e20r-import-clear-log button button-primary" type="button"
                                           id="clear_log_btn"
                                           value="<?php _e( 'Clear log', Import_Members::PLUGIN_SLUG ); ?>"/>
                                    <a href="<?php echo esc_url_raw( $variables->get( 'logfile_url' ) ) ?>"
                                       target="_blank">
                                        <input class="e20r-import-clear-log button button-secondary" type="button"
                                               id="view_log_btn"
                                               value="<?php _e( 'View log', Import_Members::PLUGIN_SLUG ); ?>"/>
                                    </a>
                                </div>
							<?php } ?>
                            <div style="float: left">
                                <input type="file" id="members_csv" name="members_csv" value="" class="all-options"
                                       accept=".csv, text/csv"
                                       about="<?php __( 'Select .CSV file to process', Import_Members::PLUGIN_SLUG ); ?>"/><br/>
                                <span class="description"><?php printf( __( 'You may want to download and review <a href="%s" target="_blank">the example CSV file</a>.', Import_Members::PLUGIN_SLUG ), plugins_url( '/examples/import.csv', Import_Members::$plugin_path . '/class.pmpro-import-members.php' ) ); ?></span>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Update user record', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Users update', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="update_users">
                                    <input id="update_users" name="update_users" type="checkbox" value="1"
                                           checked="checked"/>
									<?php _e( "Update, do not add, the user if the username or email already exists (Recommended)", Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Deactivate existing membership', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Deactivate existing membership', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="deactivate_">
                                    <input id="deactivate_old_memberships" name="deactivate_old_memberships"
                                           type="checkbox" value="1" checked="checked"/>
									<?php _e( "Update the status when importing a user who already has an 'active' membership level (Recommended)", Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Add order', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Attempt to create PMPro Order record', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="update_users">
                                    <input id="create_order" name="create_order" type="checkbox" value="1"/>
									<?php _e( "Attempt to add a PMPro order record when required data is included in the import row", Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Send WordPress notification', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Send the WordPress new user/updated password notification to the user', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="new_user_notification">
                                    <input id="new_user_notification" name="new_user_notification" type="checkbox"
                                           value="1"/>
									<?php _e( 'Send the WordPress new user/updated password notification to the user', Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Suppress "Password/Email Changed" notification', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Do not send the "Your password was changed" nor the "Your email address was changed" email messages to the updated user', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="suppress_pwdmsg">
                                    <input id="suppress_pwdmsg" name="suppress_pwdmsg" type="checkbox" value="1"/>
									<?php printf( __( '%1$sDo not send%2$s the "Your password was changed" nor the "Your email address was changed" email messages to the updated user', Import_Members::PLUGIN_SLUG ), '<strong>', '</strong>' ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <!-- send_welcome_email -->
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Send WordPress notification to admin', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Send WordPress new user/updated user notification to admin', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="admin_new_user_notification">
                                    <input id="admin_new_user_notification" name="admin_new_user_notification"
                                           type="checkbox"
                                           value="1"/>
									<?php _e( 'Send WordPress new user/updated user notification to admin', Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Send \'Welcome to the membership\' email', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Send the imported_member.html welcome email to the imported user', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="send_welcome_email">
                                    <input id="send_welcome_email" name="send_welcome_email" type="checkbox" value="1"/>
									<?php _e( 'Send the imported_member.html welcome email to the imported user', Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <!--  -->
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Display password nag', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Password nag', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="password_nag">
                                    <input id="password_nag" name="password_nag" type="checkbox" value="1"/>
									<?php _e( 'Show the password nag when the new user(s) log in', Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Password is already hashed', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Password is hashed', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="password_hashing_disabled">
                                    <input id="password_hashing_disabled" name="password_hashing_disabled"
                                           type="checkbox" value="1"/>
									<?php _e( "The passsword in the .csv file is already hashed and doesn't need to be encrypted by the import process.", Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Background import', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Import the .csv file with resume functionality.', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <label for="background_import">
                                    <input id="background_import" name="background_import" type="checkbox" value="1"
                                           checked="checked"/>
									<?php _e( 'Use a background process to import all of the records. (Recommeded)', Import_Members::PLUGIN_SLUG ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr> <?php
					if ( is_multisite() ) {
						global $current_blog_id;
						$site_list = get_sites();
						?>
                        <tr valign="top">
                        <th scope="row"><?php _e( 'Site to import to', Import_Members::PLUGIN_SLUG ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e( 'Select the multisite instance to import these members to.', Import_Members::PLUGIN_SLUG ); ?></span>
                                </legend>
                                <select id="site_id" name="site_id">
									<?php foreach ( $site_list as $site ) { ?>
                                        <option
                                        value="<?php esc_attr_e( $site->blog_id ); ?>" <?php selected( $site->blog_id, $current_blog_id ); ?>><?php esc_html_e( $site->blogname ); ?></option><?php
									} ?>
                                </select>
                            </fieldset>
                        </td>
                        </tr><?php
					}
					?>
					<?php do_action( 'e20r-import-page-setting-html' ) ?>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary"
                           value="<?php _e( 'Import', Import_Members::PLUGIN_SLUG ); ?>"
                           id="e20r-import-form-submit"/>
                </p>
            </form>
		<?php } ?>
        </div><?php
	}
}