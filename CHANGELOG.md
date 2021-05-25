# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


## v3.0
* BUG FIX: Incorrect path to the import.csv example file

## 2.60
* ENHANCEMENT: Add support for PMPro Sponsored Members import
* ENHANCEMENT: Add documentation for the Sponsored Members import to the FAQ section
* ENHANCEMENT: Add 'Reset log' button to let admin clear the Import Error/Warning log
* ENHANCEMENT: Add 'View log' button to let admin see the Import Error/Warning log
* ENHANCEMENT: Even more clear in the FAQ that importing credit card numbers is a dangerous thing to do (can be costly!)
* ENHANCEMENT: Updated import.csv example file with correct Sponsored Member column names
* ENHANCEMENT: Refactored Import_Members_From_CSV class
* BUG FIX: Didn't trigger cleanup AJAX job when clicking the "Complete" button
* BUG FIX: Fatal error if importing when sponsored members add-on is active

## v2.50
* ENHANCEMENT: Add warning banner in /wp-admin/ when wanting to send the 'imported_welcome.html' email but didn't set the 'membership_status' column to 'active'
* ENHANCEMENT: Modify # of attempted records to import per iteration when wanting to send the imported_welcome.html email and/or creating an order record
* ENHANCEMENT: Added 'calculate_max_records' method & using it in load_settings(), not the constructor
* ENHANCEMENT: Allow editing imported_member.html in the Email Templates Admin add-on
* ENHANCEMENT: Include imported_member template in the  Email Template Admin add-on
* BUG FIX: Don't send the imported_welcome.html message if the record is configured as 'inactive'
* BUG FIX: Use paypal.me link for donations
* BUG FIX: No Warning if we chose not to send the imported_welcome.html message due to configuration
* BUG FIX: Would try to cancel subscription plans when updating existing membership level at import
* BUG FIX: Loading imported_welcome message body by 'pmp_im_imported_member_message_body' filter would sometimes get overridden.
* BUG FIX: Now running body through 'pmp_im_imported_member_message_body' after loading it
* BUG FIX: Renamed plugin slightly to match branding rules

## v2.40
* BUG FIX: Import data would be cleared if the "Import Users from CSV Integraion" add-on is installed and active.

## v2.31
* ENHANCEMENT: Add more tests & info about incorrect user_registered, membership_startdate and membership_enddate date formats
* ENHANCEMENT: Display a WordPress notice (error) message if a bad/incorrect date format is used in the import file
* ENHANCEMENT: Display proposed solution for date format issues

## v2.30
* ENHANCEMENT: Added check for valid - pre-defined - membership ID for the user/row
* ENHANCEMENT: When "Add order" is selected and there are `pmpro_b` prefixed columns with data in the row, use it to try and populate the MemberOrder billing info (in the order record)
* ENHANCEMENT: New and updated Frequently Asked Questions (FAQs)
* ENHANCEMENT: Slightly less stringent behavior if the subscription_transaction_id and/or the payment_transaction_id data is missing
* ENHANCEMENT: Verify that the current line/column count matches the # of header columns
* BUG FIX: Invalid import.csv example file
* BUG FIX: Didn't always track error status
* BUG FIX: Didn't handle membership ID values of 0 properly
* BUG FIX: Didn't set the payment gateway for an order when different from the default was specified

## v2.23
* BUG FIX: Occasional "Fatal Error" during import operation

## v2.22
* ENHANCEMENT: Dedicated data check method with filterable result (allow 3rd party data checks w/returned result: 'pmpro_import_members_from_csv_data_check_status' ). Triggered for each record/row being imported.

## v2.21
* ENHANCEMENT: Disallow problematic file names when selecting import .csv file (files that will be modified during data sanitation on transfer)
* ENHANCEMENT: Various additional tests of data and corresponding error messages when attempting to import order data

## v2.20
* ENHANCEMENT: Can add a PMPro Order record for the imported user, even without a linked payment gateway record. This assumes that either the membership_initial_payment or membership_billing_amount column is present in the import (.CSV) file. The add order operation can also use the default Membership level definition pricing if neither are present in import file).
* ENHANCEMENT: Support most of the table fields for the pmpro_membership_orders and pmpro_memberships_users table with a 'membership_' prefix as importable data (pmpro_membership_orders data expects the 'Add order' option to be enabled/checked).
* ENHANCEMENT: Extract column names dynamically from PMPro's custom membership users/orders tables
* ENHANCEMENT: Updated screenshot(s)
* ENHANCEMENT: Updated and added to FAQ section
* BUG FIX: Not loading/updating record if user exists and the only user identifiable data is the email address

## v2.17
* BUG FIX: Consistent use of quote characters in .js file
* BUG FIX: Incorrect ID(s) for NONCE(s)
* BUG FIX: Hardened the error/warning message logger
* BUG FIX: Plugin links were missing
* BUG FIX: Missing semi-colon terminations in some instances
* ENHANCEMENT: Added Donation button (with logic to not always display it) and request for reviews (with .css file)
* ENHANCEMENT: Clicking the Donation button will attempt to dismiss the donation nag for 2 months by trying to track the user's client IP address (can be error prone in certain environments).
* ENHANCEMENT: Remove nag tracking when plugin is being deactivated
* ENHANCEMENT: Add styling for Import Members from a CSV file page
* ENHANCEMENT: More info when we think the import file isn't found/located
* ENHANCEMENT: Open the example import file in its own browser window/tab
* ENHANCEMENT: Add user_login check (validity/duplication)
* ENHANCEMENT: Optionally suppress the "Your password was changed" and "Your email address was changed" message to member when updating their record
* ENHANCEMENT: Remove import file & options when user clicks "Finish" for the import

## v2.16
* ENHANCEMENT: Faster turnaround when triggering background loading
* ENHANCEMENT: Updated .pot file
* BUG FIX: Didn't skip user_login based search if user found by email address

## v2.15
* BUG FIX: Would look up user by username before using the (more likely to be unique) email address

## v2.14
* BUG FIX: Would attempt to update a user record when we shouldn't

## v2.13
* BUG FIX: Didn't list all tracked/saved errors when import fails
* ENHANCEMENT: Clear output buffer before sending AJAX response

## v2.12
* ENHANCEMENT/FIX: Limiting max # of records per import iteration to <= 60

## v2.11
* ENHANCEMENT: More descriptive language for some error messages
* ENHANCEMENT: More checks during Import page load if on WPMU site(s)
* ENHANCEMENT: Split out different membership ID related error messages
* BUG FIX: Didn't handle 'inactive' status when no membership_enddate is supplied
* BUG FIX: Didn't always generate a log entry for certain errors/issues

## v2.10
* ENHANCEMENT: Warn if user has an invalid (non-existing) membership ID
* ENHANCEMENT: Warn if the user has an enddate in the future but have had their membership_status set to 'inactive'
* ENHANCEMENT: Use 'admin_cancelled' as new status for preexisting user_id/membership_id records where status is 'active'
* ENHANCEMENT: Various updates to the FAQ section.
* ENHANCEMENT/FIX: Better warning messages & handling of WP Multisite configurations
* BUG FIX: Only set status if explicitly asked to
* BUG FIX: Incorrect handling of UTF BOM characters led to missing 1st column header
* BUG FIX: Didn't return new member record to update status for in all cases
* BUG FIX: Didn't set the supplied membership_status value correctly in all cases
* BUG FIX: Didn't set format for DB record update
* BUG FIX: Didn't send imported_member.html message (when activated) to imported member

## v2.9
* BUG FIX: Didn't send the email template based welcome message
* BUG FIX: Typo in warning/error messages
* BUG FIX: A little too silent when the imported file is mis-configured
* BUG FIX: MS Excel causing trouble w/first column import values (Improved UTF BOM handling)
* BUG FIX: validate_date() method triggered PHP Notices in certain situations
* BUG FIX: Improved checking of email field in import file
* BUG FIX: Would sometimes trigger PHP Warning due to incorrectly loaded empty line of text
* ENHANCEMENT: Strip the UTF BOM character if necessary from first/header line in import file
* ENHANCEMENT: Clean up unneeded user metadata
* ENHANCEMENT: Improved error logging/handling for typical import file errors
* ENHANCEMENT: Send Welcome Email template when adding user to membership level ("Welcome imported user" email located in emails/ directory)
* ENHANCEMENT: Add warning if unable to apply new membership level for user
* ENHANCEMENT: Added method to load custom imported_member.html email template for Active Theme/Active Child Theme directory
* ENHANCEMENT: Add settings for Sending WordPress new user/updated user message
* ENHANCEMENT: Add setting for sending Custom Welcome Message for new/updated members (imported members)
* ENHANCEMENT: Run cleanup of imported_ usermeta attributes after import is complete

## v2.8
* BUG FIX: Attempting to fix issue with the New User Notice not always being sent
* ENHANCEMENT: Select target recipients of "New User Notice" (User, Admin or both)

## v2.7
* BUG FIX: Didn't include the transaction IDs, affiliate ID or gateway settings in the import
* BUG FIX: Typo in readme.txt

## v2.6
* ENHANCEMENT: Now calculating # of records to import in background based on PHP setting for max_execution_time
* ENHANCEMENT: Differentiate between warnings and errors. Errors should terminate execution. Warnings should be logged, but not end import.
* ENHANCEMENT: Added ('Recommended') for Import settings we recommend you use
* ENHANCEMENT: Make timeout for JavaScript loop be 20% > than max_execution_time
* ENHANCEMENT: Improved error/warning reporting by using SplFileObject() to manage import file (indicates the line in the .csv file with a problem)
* ENHANCEMENT: Added error message when the ID, user_login _and_ user_email columns are missing
* ENHANCEMENT: Added file indexed warning message if user_login has to be generated from the record email address (using front of '@' as the login name)
* ENHANCEMENT: Added file indexed warning message when an email address is either missing or unrecognizable
* ENHANCEMENT: Include warning and error messages in Import status window (when applicable)
* ENHANCEMENT: Updated links to the WordPress.org repository for information and support for the plugin
* ENHANCEMENT: Linted and refactored the source files
* ENHANCEMENT: Expanded the FAQ section, fixed a few typos and updated the Change log in readme.txt
* BUG FIX: Better handling/reporting of unexpected values in the membership_status column, incorrect email formats, incorrect or missing membership_id value(s), improper date formats for the membership_startdate, membership_enddate and membership_timestamp columns and unexpected membership configurations (based on values supplied in the import file)
* BUG FIX: Displaying error/warning/info messages in wp-admin was a little flaky
* BUG FIX: Would allow duplication of error messages in wp-admin area

## v2.5
* ENHANCEMENT: Added error checking and messages to warn about the most common PMPro related errors in the .csv file being imported.
* ENHANCEMENT: Added function to test dates for compliance with MySQL requirements

## v2.4
* BUG FIX: Typo in add_action( 'pmp_im_pre_user_import' ...) caused default pre-import action to not be triggered
* ENHANCEMENT/FIX: Updated action names: 'pmp_im_post_member_import', 'pmp_im_pre_members_import'

## v2.3
* BUG FIX: PHP Warning
* BUG FIX: The resume URL didn't work as expected
* BUG FIX: Didn't set the blog ID when updating membership info
* BUG FIX: Didn't cancel previous membership records for the imported user ID / membership ID combination when deactivate_old_memberships was set to true (checked)
* ENHANCEMENT: Renamed import page function (documentation related)
* ENHANCEMENT: Added link to error log (if applicable) to the admin notices

## v2.2
* ENHANCEMENT: Add support for WP Multi Site configurations (adding/updating users to the specified WPMU site)
* ENHANCEMENT: Better error handling/notifications

## v2.1
* BUG FIX: Lost track of how to update via JavaScript
* BUG FIX: Didn't preserve settings between loads of JS
* BUG FIX: Didn't always load the correct settings for the JS based import
* BUG FIX: Include error info
* BUG FIX: Didn't include all required settings in AJAX operation
* BUG FIX: Didn't set and clear busy icon for import status text
* ENHANCEMENT/FIX: Use PMPro variable array to set record info for each user
* ENHANCEMENT/FIX: Renamed methods to better describe where they're being used/what they're used for
* ENHANCEMENT: Action handler when clicking the "Finished" button at end of import ( redirect to admin_url() )
* ENHANCEMENT: Add set_busy() and clear_busy() for feedback during import
* ENHANCEMENT: Add "Finished" button (display when import is complete)
* ENHANCEMENT: Reorder options (more logical, I think)
* ENHANCEMENT: Use AJAX import as default behavior
* ENHANCEMENT: Add "Import Members" to "Memberships" drop-down menu in /wp-admin/
* ENHANCEMENT: Improved prompts/info for options on import page
* ENHANCEMENT: Load plugin actions in plugins_loaded action
* ENHANCEMENT: Fixing PHPDoc for new functions/updated functions
* ENHANCEMENT: Variable name update
* ENHANCEMENT: Clean up insert method for hashed passwords
* ENHANCEMENT: Log error message if there's a problem with a header field
* ENHANCEMENT: Adding screenshot images

## v2.0.1
* Fixed bug: Sanitizing request variables
* Fixed bug: Notification nag not getting configured correctly
* Fixed bug: New user notification not getting configured correctly
* Fixed bug: Didn't always handle pause/resume (at all)
* Fixed bug: Didn't stop/pause the import when user clicked the "pause" link
* Enhancement: Added `is_iu_import_records_per_scan` filter to let user set # of records to import per step when using JS import
* Enhancement: Refactored and added local scope for variables & functions
* Enhancement: Use JSON and status returns from server
* Enhancement: Use filter to set timeout for AJAX operation
* Enhancement: Use proper WP AJAX functionality for variables and statuses
* Enhancement: Clean up translations and grammar.
* Enhancement: Use printf()/sprintf() to improve formatting & translation
* Enhancement: Use wp_register_script()/wp_localize_script() and wp_enqueue_script() to handle passing dynamic data to JavaScript
* Enhancement: Clean up REQUEST variable use
* Enhancement: Allow setting password nag for both new and updated accounts
* Enhancement: Use wp_send_json*() functions for AJAX actions
* Enhancement: Allow import of pre-hashed passwords for user records
* Enhancement: More reliable option based tracking of imports (not transients)
* Enhancement: Moved JavaScript to own location

## v2.0.0
* Forked from the Import Users from CSV plugin and integrated the "PMPro Import Users From CSV Integration" add-on functionality
* Fixed bug with Notification Nag
* Fixed bug with user notification
* Fixed bugs with static/non-static function calls
* Enhancement: JavaScript based async loading (for large imports)