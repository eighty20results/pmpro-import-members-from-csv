# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## v3.0 - 2021-06-27
- BUG FIX: Incorrect path to the import.csv example file (Thomas Sjolshagen)
- BUG FIX: Added new workflow for PRs and fixed RM list for SVN commit (Thomas Sjolshagen)
- BUG FIX: SVN tags directory doesn't need to be processed when testing (Thomas Sjolshagen)
- BUG FIX: Fix paths to exclude from SVN repo and update release-plugin action (Thomas Sjolshagen)
- BUG FIX: Renamed entrypoint.sh to be more indicative of purpose (Thomas Sjolshagen)
- BUG FIX: Set the expected composer.phar path (Thomas Sjolshagen)
- BUG FIX: Troubleshooting composer issue(s) (Thomas Sjolshagen)
- BUG FIX: Nit update of name strings (Thomas Sjolshagen)
- BUG FIX: Use setup-php action (Thomas Sjolshagen)
- BUG FIX: Updates to workflow branch specifications (Thomas Sjolshagen)
- BUG FIX: Didn't install sudo so don't try to use it (Thomas Sjolshagen)
- BUG FIX: Make sure composer.phar is present in repo (Thomas Sjolshagen)
- BUG FIX: Can't ignore build_env (Thomas Sjolshagen)
- BUG FIX: Didn't trigger action in branch (Thomas Sjolshagen)
- BUG FIX: Added test workflow (with unit tests) (Thomas Sjølshagen)
- BUG FIX: Add unit test execution (Thomas Sjolshagen)
- BUG FIX: Update include paths (Thomas Sjolshagen)
- BUG FIX: Moved test cases to subdirectory (Thomas Sjolshagen)
- BUG FIX: Use the right repository/slug for this plugin (Thomas Sjolshagen)
- BUG FIX: Clean up unit tests and un-needed source files (Thomas Sjolshagen)
- BUG FIX: Remove unused fixture source files (Thomas Sjolshagen)
- BUG FIX: Prepare Error_Log Unit Test for implementation (Thomas Sjolshagen)
- BUG FIX: Implement unit test for is_configured() method (Thomas Sjolshagen)
- BUG FIX: Typo in transient name to use (Thomas Sjolshagen)
- BUG FIX: Added a unit test for the is_pmpro_active() method (Thomas Sjolshagen)
- BUG FIX: Remove old use statements (Thomas Sjolshagen)
- BUG FIX: renamed _before()/_after() to startUp()/tearDown() (Thomas Sjolshagen)
- BUG FIX: Added more fixtures for test_GetImportFilePath() (Thomas Sjolshagen)
- BUG FIX: Using more descriptive variable names for fixture (Thomas Sjolshagen)
- BUG FIX: Added test(s) to method description (Thomas Sjolshagen)
- BUG FIX: Removed unnecessary comment (Thomas Sjolshagen)
- BUG FIX: Refactored and updated to mock functions and classes (Thomas Sjolshagen)
- BUG FIX: Method description (Thomas Sjolshagen)
- BUG FIX: file_exists() can be mocked (Thomas Sjolshagen)
- BUG FIX: Initial commit for Error_Log unit tests (Thomas Sjolshagen)
- BUG FIX: Add VFSStream for unit testing (Thomas Sjolshagen)
- BUG FIX: Fix volume mounts in docker stack (Thomas Sjolshagen)
- BUG FIX: Exit if PMPro plugin isn't active (Thomas Sjolshagen)
- BUG FIX: Error_Log->log_errors() method needs log file path & URL passed (Thomas Sjolshagen)
- BUG FIX: Make sure default values for payment gateway are added if necessary (Thomas Sjolshagen)
- BUG FIX: Refactored to pass log file info to log_errors() method (Thomas Sjolshagen)
- BUG FIX: Refactored to pass Variables() log file info to CSV class (Thomas Sjolshagen)
- BUG FIX: Handle Utilities module check without upgrade (Thomas Sjolshagen)
- BUG FIX: Make sure the specified plugin is being activated (Thomas Sjolshagen)
- BUG FIX: Didn't reliably activate the required plugin (Thomas Sjolshagen)
- BUG FIX: Invalid class name for deactivation hook (Thomas Sjolshagen)
- BUG FIX: Don't include the bin/ directory in the WP.org version of this plugin (Thomas Sjolshagen)
- BUG FIX: Fatal error if E20R Utilities module plugin isn't installed/active (Thomas Sjolshagen)
- BUG FIX: Add support for Utilities module check(s) (Thomas Sjolshagen)
- BUG FIX: Initial commit for Utilities Module check (Thomas Sjolshagen)
- BUG FIX: Fix possible open buffers in the case of exceptions (Thomas Sjolshagen)
- BUG FIX: Use a docker specific deps target (Thomas Sjolshagen)
- BUG FIX: Didn't make sure Docker was running on the system (Mac specific) (Thomas Sjolshagen)
- BUG FIX: aspect-mock composer package needs be >4.0 for PHP > 7.4 (Thomas Sjolshagen)
- BUG FIX: Clean up the /inc ignore (Thomas Sjolshagen)
- BUG FIX: Typo in REQUEST variable (Thomas Sjolshagen)
- BUG FIX: Clean up unneeded use statements (Thomas Sjolshagen)
- BUG FIX: Allow plugin updates, etc (Thomas Sjolshagen)
- BUG FIX: Updated/refactored Unit tests (Thomas Sjolshagen)
- BUG FIX: Refactored (Thomas Sjolshagen)
- BUG FIX: Cleaned up namespaces for User data (Thomas Sjolshagen)
- BUG FIX: Refactored, not using Singleton pattern (Thomas Sjolshagen)
- BUG FIX: Remove dependency on Data() class and refactor (Thomas Sjolshagen)
- BUG FIX: Refactor and don't use Singleton pattern for Data() class (Thomas Sjolshagen)
- BUG FIX: Whitespace cleanup (Thomas Sjolshagen)
- BUG FIX: Fix namespace and don't derive from Validate class unless needed (Thomas Sjolshagen)
- BUG FIX: Don't use singleton pattern for variables (Thomas Sjolshagen)
- BUG FIX: Be specific about the PHPCS rule to ignore (Thomas Sjolshagen)
- BUG FIX: Needed to be more specific about the return class type (Thomas Sjolshagen)
- BUG FIX: Variables should be passed to CSV class and using EPOC time (Thomas Sjolshagen)
- BUG FIX: Refactoring caused plugin file path issues (Thomas Sjolshagen)
- BUG FIX: Updated copyright notice and cleaned up CSS (Thomas Sjolshagen)
- BUG FIX: WPCS updates (Thomas Sjolshagen)
- BUG FIX: Various updates (most of which will be reverted, probably) (Thomas Sjolshagen)
- BUG FIX: WPCS updates (Thomas Sjolshagen)
- BUG FIX: Adding more filters as I come across them (Thomas Sjolshagen)
- BUG FIX: Silly updates (Thomas Sjolshagen)
- BUG FIX: Disable singleton pattern for Variables() class (Thomas Sjolshagen)
- BUG FIX: Update copyright notice and extend Validate from the Base_Validation class (Thomas Sjolshagen)
- BUG FIX: Changes to singleton pattern for certain classes and updated for WPCS compliance (Thomas Sjolshagen)
- BUG FIX: Renamed pre_member_import method to clean_up_old_import_data (Thomas Sjolshagen)
- BUG FIX: Removed duplicate clean-up function (From CSV() class) (Thomas Sjolshagen)
- BUG FIX: WPCS compliance (Thomas Sjolshagen)
- BUG FIX: Adding WPCS compliant(ish) .editorconfig (Thomas Sjolshagen)
- BUG FIX: Updated copyright notice (Thomas Sjolshagen)
- BUG FIX: Changes to singleton pattern for certain classes and for WPCS compliance (Thomas Sjolshagen)
- BUG FIX: WPCS compliance (Thomas Sjolshagen)
- BUG FIX: Use Codeception Unit test framework (Thomas Sjolshagen)
- BUG FIX: WPCS compliance (Thomas Sjolshagen)
- BUG FIX: Moving away from Singleton pattern for key classes in plugin plus WPCS related updates (Thomas Sjolshagen)
- BUG FIX: Cleaned up namespace definitions & added AspectMock (Thomas Sjolshagen)
- BUG FIX: Renamed to match WPCS expectations (more or less) (Thomas Sjolshagen)
- BUG FIX: Expanded FILTERS.md documentation (Thomas Sjolshagen)
- BUG FIX: Stop using constant for domain key in I18N translations (per WPCS) (Thomas Sjolshagen)
- BUG FIX: New fixtures for unit testing (Thomas Sjolshagen)
- BUG FIX: No changes (Thomas Sjolshagen)
- BUG FIX: No longer using Singleton pattern (Thomas Sjolshagen)
- BUG FIX: Refactored and cleaned up filter names (Thomas Sjolshagen)
- BUG FIX: Bootstrap needs to include handling for AspectMock (Thomas Sjolshagen)
- BUG FIX: Color coding for unit tests didn't work as expected (Thomas Sjolshagen)
- BUG FIX: Using Error_Log class as standalone class, not singleton (unit test improvements) (Thomas Sjolshagen)
- BUG FIX: Didn't add time per record during import for sending admin email(s) (Thomas Sjolshagen)
- BUG FIX: Don't use _ (underscore) to signify visibility of member variable (Thomas Sjolshagen)
- BUG FIX: Refactored and changed visibility of runner() method (Thomas Sjolshagen)
- BUG FIX: Add function to load fixtures for a unit test (Thomas Sjolshagen)
- BUG FIX: Ignore coding standards and use printf() for echo() (Thomas Sjolshagen)
- BUG FIX: Updated README.txt file (Thomas Sjolshagen)
- BUG FIX: Make sure we have a recent Brain/Monkey library (Thomas Sjolshagen)
- BUG FIX: PHPCS updates and use a different unit test framework base class (Thomas Sjolshagen)
- BUG FIX: Added action hook documentation (Thomas Sjolshagen)
- BUG FIX: Fix PHPCS warnings (Thomas Sjolshagen)
- BUG FIX: Fix PHPCS warnings (Thomas Sjolshagen)
- BUG FIX: Rename e20r_import_load_licensed_modules action hook (Thomas Sjolshagen)
- BUG FIX: Rename e20r_import_load_licensed_modules action hook and fix PHPCS warnings (Thomas Sjolshagen)
- BUG FIX: Moved FILTERS.md and ACTIONS.MD to docs/ directory (Thomas Sjolshagen)
- BUG FIX: Adding the Filters and Actions documentation (Thomas Sjolshagen)
- BUG FIX: Clean up and make filters adhere to WPCS standards (Thomas Sjolshagen)
- BUG FIX: Added copyright notice (Thomas Sjolshagen)
- BUG FIX: Renamed fixture source file for Import_Members unit test (Thomas Sjolshagen)
- BUG FIX: Expected the wrong filter name (Thomas Sjolshagen)
- BUG FIX: Refactored to use Test Framework In A Tweet as a separate class (Thomas Sjolshagen)
- BUG FIX: Refactored to split out the autoloader (Thomas Sjolshagen)
- BUG FIX: Refactor the validation overrides (non-fatal error list & handling) (Thomas Sjolshagen)
- BUG FIX: Simplified switch statement (Thomas Sjolshagen)
- BUG FIX: Refactor handling of column validations to ignore (Thomas Sjolshagen)
- BUG FIX: Renamed e20r-import-members-supported-field-list filter to e20r_import_supported_field_list (Thomas Sjolshagen)
- BUG FIX: Add support for a basic PHP Unit test runner in Makefile (Thomas Sjolshagen)
- BUG FIX: Refactored the Unit test framework (Thomas Sjolshagen)
- BUG FIX: Created BM/IT base class for Test Framework In A Tweet (Thomas Sjolshagen)
- BUG FIX: Handle validation error checking in base Base_Validation class (Thomas Sjolshagen)
- BUG FIX: Wrong visibility for base constructor (Thomas Sjolshagen)
- BUG FIX: Use base class for column validation rule(s) (Thomas Sjolshagen)
- BUG FIX: Mounted volume paths for DB import (Thomas Sjolshagen)
- BUG FIX: Additional logging during unit tests (Thomas Sjolshagen)
- BUG FIX: Didn't activate the expected dependencies (plugin) (Thomas Sjolshagen)
- BUG FIX: Couldn't install dependencies from non-wordpress.org repos (Thomas Sjolshagen)
- BUG FIX: Didn't import database backup if present (Thomas Sjolshagen)
- BUG FIX: Path fixes for the additional plugins to include in the WordPress test image (Thomas Sjolshagen)
- BUG FIX: Ignore all .sql files in the tests/_data directory (Thomas Sjolshagen)
- BUG FIX: Shouldn't exclude the inc/ directory from the docker build (Thomas Sjolshagen)
- BUG FIX: Didn't include the E20R Utilities plugin in the wp unit test docker image (Thomas Sjolshagen)
- BUG FIX: Typo in Dockerfile for the WP Unit Tests (Thomas Sjolshagen)
- BUG FIX: Exclude inc/wp_plugins from repository and remove old dockerfile (Thomas Sjolshagen)
- BUG FIX: Updates to the change log structure (Thomas Sjolshagen)
- BUG FIX: Not using this env file (Thomas Sjolshagen)
- BUG FIX: Exclude the docker key (Thomas Sjolshagen)
- BUG FIX: Updates to build and manage docker-compose stack for Codeception testing (Thomas Sjolshagen)
- BUG FIX: A non-empty PSR-4 prefix must end with a namespace separator (Thomas Sjolshagen)
- BUG FIX: Wrong path to the composer.phar file (Thomas Sjolshagen)
- BUG FIX: Adding docker-compose file for the codeception testing infrastructure (Thomas Sjolshagen)
- BUG FIX: Updated env file for docker-compose (unit testing) (Thomas Sjolshagen)
- BUG FIX: Updated Makefile with additional support for unit testing, etc. (Thomas Sjolshagen)
- BUG FIX: Clean up SQL files from repository (Thomas Sjolshagen)
- BUG FIX: PSR4 path definitions for namespaces (Thomas Sjolshagen)
- BUG FIX: Renamed filters to match WPCS expectations (Thomas Sjolshagen)
- BUG FIX: Not handling status message(s) correctly if the user ID doesn't exist (Thomas Sjolshagen)
- BUG FIX: Fix the filter order for sponsor info (should happen after the user's member info has been imported) (Thomas Sjolshagen)
- BUG FIX: Wrong record info being used to process sponsored membership data (Thomas Sjolshagen)
- BUG FIX: Removed extra copyright notice (Thomas Sjolshagen)
- BUG FIX: Renamed filters to match WPCS expectations (Thomas Sjolshagen)
- BUG FIX: Didn't receive the expected (meta)data for member import operation (Thomas Sjolshagen)
- BUG FIX: Can return if we're not expected to create an order for the user (Thomas Sjolshagen)
- BUG FIX: Renamed $fields to $record since that's what we're processing (Thomas Sjolshagen)
- BUG FIX: Various PHP Notices for missing array keys (Thomas Sjolshagen)
- BUG FIX: Cleaned up debug log string generation (Thomas Sjolshagen)
- BUG FIX: Renamed filters to match WPCS expectations BUG FIX: Didn't save the created user IDs (Thomas Sjolshagen)
- BUG FIX: Didn't handle invalid_membership_id check correctly (Thomas Sjolshagen)
- BUG FIX: Renamed filter to match WPCS expectations (Thomas Sjolshagen)
- BUG FIX: Renamed filter so had to update unit test (Thomas Sjolshagen)
- BUG FIX: Attempted to display error messages on page when none were defined (Thomas Sjolshagen)
- BUG FIX: Didn't include the table prefix when looking up tables in DB (Thomas Sjolshagen)
- BUG FIX: Refactor log_errors() method to speed up error handling (Thomas Sjolshagen)
- BUG FIX: Use Error_Log class for logging, etc (Thomas Sjolshagen)
- BUG FIX: Remove database file from repo (Thomas Sjolshagen)
- BUG FIX: Adding some additional info to debug message (Thomas Sjolshagen)
- BUG FIX: membership_id field related clean-up (Thomas Sjolshagen)
- BUG FIX: Don't run validations when the field(s) we're checking aren't included in the record (Thomas Sjolshagen)
- BUG FIX: Updated ignore entries to exclude DBs, etc (Thomas Sjolshagen)
- BUG FIX: PSR path definitions fixed (Thomas Sjolshagen)
- BUG FIX: 00-e20r-utilities plugin needs to exist for this plugin to work! (Thomas Sjolshagen)
- BUG FIX: str_contains() is no longer valid (Thomas Sjolshagen)
- BUG FIX: Wrong path to support manual plugin testing on in development environment (Thomas Sjolshagen)
- BUG FIX: Refactored docker-compose.override files for manual testing (Thomas Sjolshagen)
- BUG FIX: Adding/Updating *ignore files (Thomas Sjolshagen)
- BUG FIX: Adding/Updating *ignore files (Thomas Sjolshagen)
- BUG FIX: More updates to support WP Unit testing (Thomas Sjolshagen)
- BUG FIX: Various updates to support WordPress 'Unit' testing (Thomas Sjolshagen)
- BUG FIX: Didn't use Error_Log class for debug logging (Thomas Sjolshagen)
- BUG FIX: Use list for container dependencies in docker-compose (Thomas Sjolshagen)
- BUG FIX: Missing dependency in composer.json (Thomas Sjolshagen)
- BUG FIX: Testing 'Test framework in a tweet' approach to unit tests (Thomas Sjolshagen)
- BUG FIX: Didn't wait for DB to be ready (Thomas Sjolshagen)
- BUG FIX: Missing PSR-4 autoloader 'stuff' (Thomas Sjolshagen)
- BUG FIX: Didn't include phpstan packages (Thomas Sjolshagen)
- BUG FIX: Missing phpstan-test target (Thomas Sjolshagen)
- BUG FIX: Didn't exclude phpstan libraries (Thomas Sjolshagen)
- BUG FIX: Could not mock basename() and dirname() (Thomas Sjolshagen)
- BUG FIX: Style cleanup (Thomas Sjolshagen)
- BUG FIX: PHPStan and PHPCS updates (Thomas Sjolshagen)
- BUG FIX: Added phpstan config file (Thomas Sjolshagen)
- BUG FIX: Renamed plugin_slug constant (uppercase) (Thomas Sjolshagen)
- BUG FIX: Rename directory (case insensitive) (Thomas Sjolshagen)
- BUG FIX: Add function docstring for tearDown() (Thomas Sjolshagen)
- BUG FIX: Wrong path for WP unit tests (Thomas Sjolshagen)
- BUG FIX: Refactored and clean up paths (Thomas Sjolshagen)
- BUG FIX: Didn't activate the Utilities plugin (Thomas Sjolshagen)
- BUG FIX: PSR4 specifics (bad) removed (Thomas Sjolshagen)
- BUG FIX: Namespace update (Thomas Sjolshagen)
- BUG FIX: Didn't activate the Utilities plugin (Thomas Sjolshagen)
- BUG FIX: Additional setup and config for unit testing (Thomas Sjolshagen)
- BUG FIX: Added missing Makefile (Thomas Sjolshagen)
- BUG FIX: Added compose stack for test environment (Thomas Sjolshagen)
- BUG FIX: SVN clean-up and codeception testing transition (Thomas Sjolshagen)
- BUG FIX: Missing git action definitions (Thomas Sjolshagen)
- BUG FIX: Use git actions to prepare release for WordPress.org deployment (Thomas Sjolshagen)
- BUG FIX: Clean up unnecessary shell use (Thomas Sjolshagen)
- BUG FIX: Move the changelog to it's own file (Thomas Sjolshagen)
- BUG FIX: Refactored to support unit tests, build containers, etc (Thomas Sjolshagen)
- BUG FIX: Updated version (Thomas Sjolshagen)
- BUG FIX: Updated version string and PHP requirement (Thomas Sjolshagen)
- BUG FIX: Autoloader supports WPCS compliant file naming (Thomas Sjolshagen)

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
