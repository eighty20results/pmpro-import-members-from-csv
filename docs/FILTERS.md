# Available filters

## Supported and in use

* `e20r_import_wpuser_fields` - The WP_User fields to include in the import operation
* `e20r_import_records_per_scan` - Number of records to import per time-unit (default: 15 )
* `e20r_import_order_link_timeout` - Number of seconds to wait for the order to be linked (default: 1 second)
* `e20r_import_time_per_record` - Number of seconds to expect per record being imported (default: 1.5 seconds)
* `e20r_new_user_notification_time` - Number of seconds to expect per record when having to send email notification email (default: 4 seconds)
* `e20r_import_welcome_email_time` - The number of seconds we expect to spend connecting with the upstream email server and thus have to add to the total import time for each record
* `e20r_import_supported_fields` - The list of PMPro membership data fields to import (included in the .csv file for import)
* `e20r_import_set_transient_time_buffer` - The amount of additional time (buffer) that transients defined/set for the import operation should live (default: 20 (seconds)). Total transient lifetime calculated as the timeout value for each batch load of import operations, plus this buffer (Default being the [ # of records per batch operation (per_partial) * Variables::calculate_per_record_time() ] + `e20r_import_set_transient_time_buffer seconds`)
* `e20r_import_wc_pmpro_billing_field_map` - Fields included when importing billing information for PMPro or WooCommerce
* `e20r_import_users_validate_field_data` - Used to validate and potentially fix the WP User data imported
* `e20r_import_valid_member_status` - Array of valid PMPro statuses (default: 'active', 'inactive')
* `e20r_import_modules_pmpro_headers` - List of valid CSV file headers for the import file
* `e20r_import_welcome_email_template` - The HTML email template (name before the .html extension) to use when sending an imported, active member a welcome email message.
## Not supported/not in use yet

* `e20r_import_buddypress_tables` - Array of table name with import CSV prefix values used to verify the existence of, in order to import BuddyPress data
* `e20r_import_buddypress_excluded_fields` - BuddyPress metadata fields to exclude/ignore when importing (Support pending)
