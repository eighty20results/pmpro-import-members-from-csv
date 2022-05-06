# Available filters

## Supported and in use

* `e20r_import_wpuser_fields` - The WP_User fields to include in the import operation
* `e20r_import_records_per_scan` - Number of records to import per time-unit (default: 15 )
* `e20r_import_order_link_timeout` - Number of seconds to wait for the order to be linked (default: 1 second)
* `e20r_import_time_per_record` - Number of seconds to expect per record being imported (default: 1.5 seconds)
* `e20r_new_user_notification_time` - Number of seconds to expect per record when having to send email notification email (default: 4 seconds)
* `e20r_import_welcome_email_time` - The number of seconds we expect to spend connecting with the upstream email server and thus have to add to the total import time for each record
* `e20r_import_supported_fields` - The list of PMPro membership data fields to import (included in the .csv file for import)
* `e20r_import_set_transient_time_buffer` - The amount of additional time (buffer) that transients defined/set for the import operation should live (default: 20 (seconds)). Total transient lifetime calculated as the timeout value for each batch load of import operations, plus this buffer.  Default buffer size being  ((# of records per batch operation (per_partial) * Variables::calculate_per_record_time()) + `e20r_import_set_transient_time_buffer` seconds )
* `e20r_import_wc_pmpro_billing_field_map` - Fields included when importing billing information for PMPro or WooCommerce
* `e20r_import_users_validate_field_data` - Used to validate and potentially fix the WP User data imported
* `e20r_import_valid_member_status` - Array of valid PMPro statuses (default: 'active', 'inactive')
* `e20r_import_modules_pmpro_headers` - List of valid CSV file headers for the import file
* `e20r_import_welcome_email_template` - The HTML email template (name before the .html extension) to use when sending an imported, active member a welcome email message.
* `e20r_import_column_validation_class_names` - The list of Column_Validation - and Custom column validation - classes we can/need to use in order to validate import file data (columns). The filter returns the class name and the path to its location in the file system. NOTE: All custom validation classes must be in the `E20R\Import_Members\Validate\Custom` namespace AND be extended from the `E20R\Import_Members\Validate\Base_Validation` base class
* `e20r_import_message_subject` - Filter the email Subject information to let us substitute any variables (data). Accepts WP_User object and the array of fields 
* `e20r_import_enable_user_id_override` - Can be used to override the default behavior of the plugin to ignore the ID value specified in the CSV import file when adding or updating user data as part of the import. If this filter returns `true`, if the plugin can locate the user whose data is being imported, either by using the email address, login name or ID value from the CSV import file, it will attempt to change the users record in the wp_users database table to match the ID value supplied in the CSV import file. Should that ID value, from the CSV import file, already exist in the wp_users table and belong to a user who has a different email address or login name recorded in WordPress than what the CSV import file specifies, the plugin will not update the ID number for either user. **NOTE**: We do _NOT_ recommend enabling this option!
* `e20r_import_default_field_values` - Define custom default values for the import metadata if applicable
* 
## Not supported/not in use yet

* `e20r_import_buddypress_tables` - Array of table name with import CSV prefix values used to verify the existence of, in order to import BuddyPress data
* `e20r_import_buddypress_excluded_fields` - BuddyPress metadata fields to exclude/ignore when importing (Support pending)
