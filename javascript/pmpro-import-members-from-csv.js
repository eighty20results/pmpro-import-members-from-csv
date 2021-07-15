/**
 * Copyright (c) 2018 - 2021 - Eighty / 20 Results by Wicked Strong Chicks.
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
 *
 * @since 2.9 - BUG FIX: Didn't send the email template based welcome message
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        var e20r_import = {
            init: function () {
                this.status = $('#importstatus');
                this.error_msgs = $('#e20r-status');
                this.pausebutton = $('#pauseimport');
                this.resumebutton = $('#resumeimport');
                this.complete_btn = $('#completed_import');
                this.donate_btn = $('#e20r_donation_button');
                this.import_form = $('form#e20r-import-form');
                this.import_btn = $('input#e20r-import-form-submit');
                this.clear_log_btn = $('input#clear_log_btn');
                this.file_input = $('input#members_csv');
                this.title = document.title;
                this.cycles = ['|', '/', '-', '\\'];
                this.count = 0;
                this.row = 1;

                this.importTimer = null;
                this.status_paused = false;

                var self = this;
                // Load button handling
                self.bind();

                if (self.status.length > 0) {

                    self.status.html(self.status.html() + '\n' + e20r_im_settings.lang.loaded + '\n');
                    self.set_busy();
                    self.importTimer = setTimeout(function () {
                        self.import_csv();
                    }, 500);
                }
            },
            bind: function () {

                var self = this;

                self.import_btn.unbind('click').on('click', function (ev) {

                    ev.preventDefault();
                    self.import_form.submit();
                });

                // Handle click on the 'donate' button
                self.donate_btn.unbind('click').on('click', function (ev) {

                    ev.preventDefault();
                    self.update_nag();
                });

                // Activate the pause button
                self.pausebutton.unbind('click').on('click', function () {

                    clearTimeout(self.importTimer);

                    self.status_paused = true;

                    self.pausebutton.hide();
                    self.resumebutton.show();

                    self.status.html(self.status.html() + e20r_im_settings.lang.pausing + '\n');
                });

                self.resumebutton.unbind('click').on('click', function () {

                    self.status_paused = false;
                    clearTimeout(self.importTimer);
                    self.resumebutton.hide();
                    self.pausebutton.show();

                    self.status.html(self.status.html() + e20r_im_settings.lang.resuming + '\n');

                    self.import_csv();
                });

                self.complete_btn.unbind('click').on('click', function (event) {

                    event.preventDefault();
                    self.cleanup_routine();
                });

                self.file_input.unbind('change').on('change', function( event ) {

                    var file_input = $(this);
                    var $filename = file_input.val();

                    if (  $filename.indexOf(' ') >= 0 ) {
                        window.alert( e20r_im_settings.lang.whitespace_in_filename );
                        file_input.val(null);
                        return false;
                    }
                });

                // Remove the error log
                self.clear_log_btn.unbind('click').on( 'click', function( event ){
                    event.preventDefault();
                    self.clear_log();
                });
            },
            update_nag: function () {

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: (parseInt(e20r_im_settings.timeout) * 1000),
                    dataType: 'json',
                    data: {
                        action: 'e20r_visitor_clicked_donation',
                        'e20r-im-import-members-wpnonce': $('#e20r-im-import-members-wpnonce').val()
                    },
                    error: function ($response) {
                        window.console.log('Unable to save the IP address for the user who clicked the Donate button!');
                    },
                    success: function ($response) {
                        window.console.log('Saved IP address for user\'s computer');
                    }
                });

                $('form#e20r-import-donation').submit();
            },
            import_csv: function () {

                var self = this;

                // Increment counter
                self.count++;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: (parseInt(e20r_im_settings.timeout) * 1000),
                    dataType: 'json',
                    async: true,
                    data: {
                        action: 'import_from_csv',
                        'background_import': parseInt(e20r_im_settings.background_import),
                        'filename': e20r_im_settings.filename,
                        'password_nag': parseInt(e20r_im_settings.password_nag),
                        'update_users': parseInt(e20r_im_settings.update_users),
                        'deactivate_old_memberships': parseInt(e20r_im_settings.deactivate_old_memberships),
                        'new_user_notification': parseInt(e20r_im_settings.new_user_notification),
                        'suppress_pwdmsg': parseInt(e20r_im_settings.suppress_pwdmsg),
                        'admin_new_user_notification': parseInt(e20r_im_settings.admin_new_user_notification),
                        'send_welcome_email': parseInt(e20r_im_settings.send_welcome_email),
                        'password_hashing_disabled': parseInt(e20r_im_settings.password_hashing_disabled),
                        'per_partial': parseInt(e20r_im_settings.per_partial),
                        'site_id': parseInt(e20r_im_settings.site_id),
                        'import': e20r_im_settings.import,
                        'create_order': parseInt(e20r_im_settings.create_order),
                        'e20r-im-import-members-wpnonce': $('#e20r-im-import-members-wpnonce').val()
                    },
                    success: function (response) {

                        if (true === response.success) {

                            if (typeof response.data.status !== 'undefined' && response.data.status === true) {

                                if (typeof response.data.message !== 'undefined' && null !== response.data.message) {

                                    self.status.html(self.status.html() + response.data.message);
                                    document.title = self.cycles[(parseInt(self.count) % 4)] + ' ' + self.title;

                                    if (false === self.status_paused) {

                                        self.importTimer = setTimeout(function () {
                                            self.import_csv();
                                        }, 500);
                                    }
                                } else if (typeof response.data.message !== 'undefined') {
                                    self.status.html(self.status.html() + '\n' + e20r_im_settings.lang.done);
                                    document.title = '! ' + self.title;
                                    self.clear_busy();
                                    self.complete_btn.show();
                                }

                                if ( typeof response.data.display_errors !== 'undefined' && response.data.display_errors !== null ) {
                                    self.process_errors( response.data.display_errors );
                                }
                            }

                            // Scroll the text area to the bottom unless the mouse is over it
                            if ($('#importstatus:hover').length <= 0) {
                                self.status.scrollTop(self.status[0].scrollHeight - self.status.height());
                            }

                        } else if (false === response.success) {

                            if (typeof response.data.message !== 'undefined' && (response.data.status === false || response.data.status === -1)) {

                                self.status.html(self.status.html() + response.data.message);

                                document.title = self.title;

                                window.alert(e20r_im_settings.lang.error);
                            }

                            if ( typeof response.data.display_errors !== 'undefined' && response.data.display_errors !== null ) {
                                self.process_errors( response.data.display_errors );
                            }
                        }
                    },
                    error: function (jqXHR, $status, $error_thrown ) {

                        window.console.log('Import error: ', $status );
                        window.console.debug("Info: ", jqXHR );

                        window.alert(e20r_im_settings.lang.alert_msg + "\n" + $error_thrown );

                        self.process_errors( $error_thrown );

                        // Wait 10 seconds, then redirect...
                        setTimeout(function () {
                            self.trigger_redirect( e20r_im_settings.admin_page );
                        }, 10000);
                    }
                });
            },
            trigger_redirect: function( $to_url ) {
                window.location.replace( $to_url );
            },
            set_busy: function () {
                $('#importstatus').css('cursor', 'wait');
            },
            clear_busy: function () {
                $('#importstatus').css('cursor', 'pointer');
            },
            clear_log: function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: (parseInt(e20r_im_settings.timeout) * 1000),
                    dataType: 'json',
                    data: {
                        action: 'clear_log',
                        'e20r-im-import-members-wpnonce': $('#e20r-im-import-members-wpnonce').val(),
                    },
                    success: function () {
                        window.console.log("Error log has been cleared...");
                        window.location.replace( e20r_im_settings.admin_page );
                    },
                    error: function ($msg) {
                        window.console.log("Error", $msg);
                    }
                });
            },
            cleanup_routine: function () {

                var self = this;
                self.set_busy();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: (parseInt(e20r_im_settings.timeout) * 1000),
                    dataType: 'json',
                    data: {
                        action: 'cleanup_csv',
                        'e20r-im-import-members-wpnonce': $('#e20r-im-import-members-wpnonce').val(),
                        'filename': e20r_im_settings.filename,
                    },
                    success: function () {
                        window.console.log("We cleaned up after us");
                        window.location.replace( e20r_im_settings.admin_page );
                    },
                    error: function ($msg) {
                        window.console.log("Error", $msg);
                    }
                });

                self.clear_busy();
            },
            process_errors: function( $display_errors ) {

                var self = this;
                var $error_messages = '';

                if ( null === $display_errors ) {
                    window.console.log("No error messages returned");
                    return false;
                } else {

                    if ( $display_errors.startdate ) {
                        $error_messages += '<div class="notice notice-error"><strong>' + $display_errors.startdate + '</strong></div>';
                    }

                    if ( $display_errors.enddate ) {
                        $error_messages += '<div class="notice notice-error"><strong>' + $display_errors.enddate + '</strong></div>';
                    }

                    if ( $display_errors.user_registered ) {
                        $error_messages += '<div class="notice notice-error"><strong>' + $display_errors.user_registered + '</strong></div>';
                    }

                    $error_messages += '<div class="notice notice-info">' + e20r_im_settings.lang.excel_info + '</div>';

                    self.error_msgs.html($error_messages);
                }
            }
        };

		e20r_import.init();

    });

})(jQuery);
