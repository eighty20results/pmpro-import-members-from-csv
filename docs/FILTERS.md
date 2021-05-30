### Supported Filters

* `e20r_import_wpuser_fields` - The WP_User fields to include in the import operation
* `e20r_import_records_per_scan` - Number of records to import per time-unit (default: 15 )
* `e20r_import_order_link_timeout` - Number of seconds to wait for the order to be linked (default: 1 second)
* `e20r_import_time_per_record` - Number of seconds to expect per record being imported (default: 1.5 seconds)
* `e20r_import_welcome_email_time` - The number of seconds we expect to spend connecting with the upstream email server and thus have to add to the total import time for each record
* `e20r_import_supported_field_list` - The list of PMPro membership data fields to import (included in the .csv file for import)