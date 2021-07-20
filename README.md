### Import Members from CSV for Paid Memberships Pro
`Contributors: eighty20results, sorich87, ideadude` <br />
`Tags: user, users, csv, batch, import, importer, admin, paid memberships pro, members, member, pmpro` <br />
`Requires at least: 5.0` <br />
`Tested up to: 5.8` <br />
`Stable tag: 3.1.4` <br />
`License: GPLv2` <br />
`License URI: http://www.gnu.org/licenses/gpl` <br />

[![Release to wordpress.org](https://github.com/eighty20results/pmpro-import-members-from-csv/actions/workflows/release-plugin.yml/badge.svg)](https://github.com/eighty20results/pmpro-import-members-from-csv/actions/workflows/release-plugin.yml)

Import and create user + PMPro member records from a CSV file on your WordPress with Paid Memberships Pro website.
The plugin will import the membership information, user meta data, PMPro order data, Sponsored Members information and
can even link pre-existing recurring payment records for your payment gateway integration.

### Description

We designed this plugin to give you an error free import of a user/member to a WordPress/Paid Memberships Pro site.
It supports both adding and changing user data.

Unlike the "Import User From CSV Integration" add-on by Paid Memberships Pro, this "Import Members from CSV" plugin
will verify the data you are trying to import during the import operation. This is done to reduce the probability of
problem after the import. If there are any errors/issues, information about the problem will be logged to the
`e20r_im_errors.log` saved in the `wp-content/uploads/` directory.

**NOTE**: You can run the import multiple times with the same/slightly modified import .csv file and the appropriate
settings (see the FAQ/description below). If you configure the plugin settings correctly, this will only result in
overwriting/changing the existing member data.

Using a CSV (Comma Separated Values) file, the will add users with basic user information as well as user meta data
fields, the user role (if applicable) and the specified Paid Memberships Pro member configuration/information. It can
also generate an order record to ensure your recurring subscriptions continue to get attributed to the imported member.

If you've exported the user's passwords as hashed strings, you can import them without re-encrypting them again
(by setting the option).

You can also choose to send a notification to the new users and to display password nag on user login.

This plugin supports Network Activation on a WordPress Multisite (WPMU) installation (see the settings page when
using in a multisite configuration)

[Check out my other plugins.](http://eighty20results.com/wordpress-plugins/)

## Features

- Imports all WP User database table fields
- Imports user meta
- Imports PMPro membership data
- Update existing users if they already exist in the WP Users database (if the option is selected)
- Overwrite preexisting membership records for the same membership level as is being imported (if the option is selected)
- Set/Update the user WordPress role for the member (if the role is specified in the import file)
- Sends new user a notification message (if the option is selected)
- Deactivate the standard WordPress user update email notices during the import operation
- Shows password nag on user login (if the option is selected)
- Allows large user/member import without having to configure the PHP max_execution_time variable (if the option is selected)
- Import hashed password for new/updating users (if the option is selected)

**NOTE**: The plugin may not import some of data if it detects a problem. To find out what the problem was, read this
documentation and the FAQ section to ensure you have correctly formatted _all_ of your import data.

For feature request and bug reports, [please use the issues section on GitHub](https://github.com/eighty20results/pmpro-import-members-from-csv/issues).
Code contributions are welcome [on Github](https://github.com/eighty20results/pmpro-import-members-from-csv).

**NOTE**: In order to hide the "Donation" button after a donation, this plugin will attempt to track the admin's IP
address. This action may have GDPR implications for you or your administrators.

The tracking information is stored in the WordPress options table (wp_options) using the `e20r_import_has_donated`
option name and can safely be deleted in the database if you do not wish to leave it. Deleting the option from the
database will obviously re-enable the Donation nag.

The Nag tracking can be disabled altogether with the `e20r_import_donation_tracking_disabled` filter:

`add_filter( 'e20r_import_donation_tracking_disabled', '__return_true' );'`

### Installation

For an automatic installation through WordPress:

1. Go to the 'Add New' plugins screen in your WordPress admin area
1. Search for 'Import Users from CSV'
1. Click 'Install Now' and activate the plugin
1. Upload your CSV file in the 'Users' menu, under 'Import From CSV'


Or use a nifty tool by WordPress lead developer Mark Jaquith:

1. Visit [this link](http://coveredwebservices.com/wp-plugin-install/?plugin=pmpro-import-members-from-csv) and
follow the instructions.


For a manual installation via FTP:

1. Upload the `pmpro-import-members-from-csv` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' screen in your WordPress admin area
1. Upload your CSV file in the 'Members' menu, under 'Import Members'


To upload the plugin through WordPress, instead of FTP:

1. Search for the plugin ("Paid Memberships Pro Import Members from CSV" on the 'Add New' plugins screen in your WordPress admin area and activate.
1. Upload your CSV file in the 'Memberships' menu, under 'Import Members'

## Frequently Asked Questions

### How to use?

Click on the 'Import Members' link in the 'Membership' menu, then select your CSV file.
Next you have to decide whether you:

1. want to deactivate previously existing member record(s) for the user - The CSV record has to specify the same
membership as the user previously had so this is most useful when having to reimport/update data (default: enabled and
recommended),
2. update the existing user/member's information (default: enabled and recommended)
3. send a notification email to new users (default: disabled),
4. want the password nag to be displayed when the user logs in (default: disabled),
5. have included a hashed (encoded) password specified in the import file and (default: disabled)
6. want to use the background import option (default: enabled and recommended)
7. create a PMPro order record based on supplied payment info in the .csv file (default: disabled)

Then click the 'Import' button.

Each row in your CSV file should represent a user; each column identifies user data, user meta data or user membership data
If a column name matches a field in the user table, data from this column is imported in that field; if not, data is
imported in a user meta field with the name of the column or into the PMPro custom membership tables.

Look at the examples/import.csv file in the plugin directory (also linked on the "Import Members" page) to have a
better understanding of how the your CSV file should be organized and what the data fields need to contain as far as
formatting/values go.

You can always try importing the examples/import.csv file and look at the result, assuming the values specified for the
membership_id in the example file match your membership level configuration.

### The .CSV file from the "Export to CSV" button on the "Members List" page won't import?

The purpose of the resulting .CSV file from the "Export to CSV" is to generate reports that are meaningful to a human,
not a file that can be imported easily. This is true for any of the available Import from .CSV plugins/add-ons for
Paid Memberships Pro.

Basically there are a few key differences between the file resulting from the Export function and the file contents
needed to import the same member.

As of version 2.5, this plugin includes data checks for some of the more common mistakes I've seen in the .CSV file(s).

You should also check out the import example file that is linked on the "Import Members" page (under the "Choose File" button).

### My import fails, what is wrong?

This is almost always related to the data in the file being imported. As a result, I've added some data tests for some
of the typical mistakes I've seen in the .CSV file(s) being imported. There is also a link to an example file on the
"Import Members" page (under the "Choose File" button) that illustrates a functional import file. Things lik what the
field names are and the format you'll most likely need to use for the data in that column.

Check the wp-content/uploads/pmp_im_errors.log log file for details on the import operations (link should also be
included in a wp-admin dashboard notice if there are errors/warnings). The log should contain suggestions on some of
the more common mistakes in the data being imported.

### Do I need to include all the columns from the sample file?

No. __Only__ include the columns where you have data to import. I.e, if none of your members need to have their
membership end date defined (i.e the membership you're importing doesn't have an expiration or it's a recurring
membership with a linked Payment Gateway subscription plan), just remove the column(s) you don't need. That way,
the plugin doesn't try to import data that isn't there.

If a column has no data, you should remove the column and it's column header from the import file!

### The plugin didn't import any membership data!?!

This is a pretty common question and the reason is almost always because there is something unexpected in the
`membership_` portion of the row being imported.

Most often it's the date/time format for the `membership_startdate` and `membership_enddate` columns.

If you use MS Excel(tm) to prepare your .CSV file, you're in for a treat...

In my experience, MS Excel(tm) is _really_ good at changing the date format in a spreadsheet column to whatever it
thinks works best (i.e. human readable). (If my sarcasm doesn't shine through; This actually __isn't__ a good thing!)

However, human readable is often problematic for CSV imports, so you **have to make sure** the date format follows the
`YYYY-MM-DD HH:ii:ss` template (where ii = 2 digit minute value). For startdate I'd recommend using `00:00:00` and
for the enddate I'd suggest using `23:59:59`.

`Just to be clear: The __only__ date format for the `membership_startdate`, `membership_enddate` and the` <br />
``user_registered` columns that this plugin will accept is the MySQL datetime format: YYYY` <br />-MM-DD HH:ii:ss.

*Use anything else and your membership data will not be imported*!

You can change the way Microsoft Excel(tm) handles date and time data in the Regional settings, but I've yet to
figure out what the ideal settings are here. Truth be told, I'm using Apple Numbers and others have had great
success using Google Sheets to process and export their .CSV files. Because there are alternatives to Microsoft
Excel(tm) and they seem to work a lot better for this specific task, I'm not at all inclined to spend more time
on fixing something that I view to be a rather significant "bug"[1] in Excel(tm).

 [1] = Being that it's an intentional usability feature, I realize Microsoft is unlikely to be all that interested
 in fixing this "as designed" capability they've implemented.

### Can this plugin be used to link sponsored members with their sponsors?

Yes.

In version 2.60, we added support for importing Sponsors and their sponsored members. So, if you have the
PMPro Sponsored Members add-on installed, active and need to link sponsors and their sponsored members
during the import with this plugin.

### Importing the Sponsored user

To link sponsored users with their sponsors, add the `pmprosm_sponsor` column to your import file.

On the data row for the __sponsored__ user, the `pmprosm_sponsor` column must contain the user key for the sponsor
you want to link them with.

Or, if they don't have a sponsor, that column must be blank.

The sponsor key is either the email address they used when registering on your system - or the user_email column
value for their user record if they're also being imported at the same time, the WordPress user ID value (numeric),
or the login name used (user_login value).

### Importing the Sponsor

First of all, you will need to include a `pmprosm_seats` column as well. This column contains a numeric value to
indicate the number of seats (sponsored users) this user has paid to sponsor.

When importing a sponsor there are a couple of scenarios;

1) The system already contains the sponsor code (a sponsor code is a PMPro discount code prefixed with the letter 'S')
and you simply need to link the sponsor to their code.

2) The system lacks the sponsor code, so you'll need one to be created.

For scenario 1; The sponsor code (discount code) already has a Discount Code ID (integer value, found on the PMPro
"Discount Codes" settings page). This ID needs to be added in the `membership_code_id` column of the import file for
the sponsor (user record), along with a numeric value in the `pmprosm_seats` column.

For scenario 2; The sponsor code is created by this plugin. It happens automatically if the sponsor user exists - or
is being imported at the same time as - when the **sponsored** user is attempted imported and linked. The discount
code created attempts to use the settings from the PMPro Sponsored Members add-on for the discount code.

### Caveat

The order in which users are listed in the .csv import file can matter when importing sponsors and their sponsored user.

Although this plugin tries to re-import sponsored users if the import fails the first time, as part of the
clean-up process, this retry does not guarantee success!

As a result, it is possible that a sponsored user is imported without being linked to their sponsor.

You can fix that by running the import more than once.

Alternatively, you can create two import files;

One with the sponsor users only, and one with the sponsor*ed* users only.

Then import the sponsors first. Next you import the sponsored users.

## Can this plugin be used to import order data for Paid Memberships Pro?

As of version 2.20, we have an option to create member orders at the same time as we update the membership record.

That means you can now include some of the order table fields to import custom values as needed for each
user/member, along with updating/adding their membership level information.

The supported order record columns are:

1. paypal_token
1. subtotal
1. tax
1. couponamount
1. checkout_id
1. certificate_id
1. certificateamount
1. total
1. payment_type
1. cardtype
1. accountnumber
1. expirationmonth
1. expirationyear
1. status
1. gateway
1. gateway_environment
1. payment_transaction_id
1. subscription_transaction_id
1. timestamp
1. affiliate_id
1. affiliate_subid
1. notes
1. billing_street (*)
1. billing_city (*)
1. billing_state (*)
1. billing_zip (*)
1. billing_country (*)
1. billing_phone (*)

All of these columns/fields should be prefixed with `membership_`. I.e. `membership_paypal_token` or `membership_tax`,
etc. The exceptions are the `user_id` and `membership_id` columns/fields which should be left as `user_id` and
`membership_id` respectively if you want to include them in the import operation(s).

The `status` column has a limited number of valid values. By default, we recommend using either `success` or `cancelled`

All timestamp values ('timestamp') must use the same format as the one used by the MySQL database's 'DATETIME'
`format: `YYYY` <br />-MM-DD HH:MM:SS`

**PLEASE NOTE:**

Although you _can_ specify an account number (`accountnumber`) in the import file, doing that will *not* result in
this plugin importing and activating subscriptions or payments by credit card.

You CANNOT use this tool to import and **create** subscription plans, or transactions, on the payment gateway for
your Paid Memberships Pro users.

Including anything other than a masked Credit Card number for the `membership_accountnumber` column *is a really
bad idea*[1]!

A masked credit card number = Only the last 4 digits are real and the rest are repetitions of the 'X'
character (`XXXXXXXXXXXX1234`).

[1] = Importing a full credit card number will exponentially increase the probability that you, in the event of a
security problem on your site, will have to pay the Payment Card Industry (PCI) massive fines. Simply put; Don't import
Credit Card information! Instead, ask your members to resubmit their information when the site is back online/live.

**This plugin does NOT mask your credit card numbers for you!**

### Supported membership_gateway options

The Import Members from CSV plugin supports specifying different payment gateways for the user record(s) when
importing order data (i.e. the "Attempt to create PMPro Order record" option has been selected). At present, the
payment gateways that can be specified in the `membership_gateway` column are:

1. authorizenet
2. braintree
3. check
4. cybersource
5. payflowpro
6. paypal
7. paypalexpress
8. paypalstandard
9. stripe
10. twocheckout
11. payfast

During the import operation, the plugin will verify that the specified payment gateway integration is one of the
supported payment gateway integrations for Paid Memberships Pro.

Specifying a Payment Gateway Integration that has not been configured for use during the PMPro checkout process would
render the order record invalid.

**NOTE:** The limitations to how Paid Memberships Pro supports/handles multiple payment gateway integrations at the
same time still apply.

### Adding billing address information to the PMPro Order import

The normal way to import billing address data to the database for a member/user is to use the `pmpro_b[*]` fields
(`pmpro_bfirstname`, `pmpro_blastname`, `pmpro_baddress1`, `pmpro_baddress2`, `pmpro_bcity`, `pmpro_bstate`,
`pmpro_bzipcode`, `pmpro_bcountry` and `pmpro_bphone`).

If the `pmpro_b*` field data is present in the row and the 'Add order' option is selected for the import file, the
import will attempt to populate the order billing information using the `pmpro_b*` data.

## How should the .csv file be defined?

This plugin assumes that the .csv file;

- Uses a comma (,) character to denote a new column in a row
- Uses a double-quote (") character to wrap the contents of each of the columns
- Uses a backslash (\) character as the escape character

For example;
To have a 2 column .csv file, each row **after** the header row, the row should look something like this:
`"my first column data","my second \"escaped\" column data"`.

The application you use to edit and export your .CSV file will need to be configured appropriately before you
export the .csv file.

## Why am I or my users not receiving New user notifications

There are a couple of possible reasons, as far as I can tell. The functionality in WordPress that generates the
"new user notification" message is what they call "pluggable". That means that it's possible for a plugin (any plugin!)
to override the behavior of the functionality. So the first thing I'd suggest investigating is whether you have a
plugin active that intentionally changes/modifies/updates how the `wp_new_user_notification()` function works/behaves.

Next, it's (very) possible that your hosting environment doesn't want you to be sending out a lot of email messages
from their servers. As a result, the import operation could potentially trip their anti-spam measures and blocking
you from sending any messages.

Third, the recipient email server may be using a SBL (Spam Black List) and have your web server IP listed as a
typical source of Spam messages (it happens, a lot).

### I've set the 'membership_status' column to 'inactive', but the user's imported membership level is currently 'active'?

This is due to what I'd term a bug in Paid Memberships Pro. This issue doesn't currently have a fix.

Basically, the 'inactive' status will only apply to the order record (if it's created, see above) and *not* to the
user's membership status.

**NOTE:** Assigning a membership level for a user will cause them to be given an active membership on the site when
the import operation is complete, regardless of the value supplied for the 'membership_status' column.

### How do I import an existing payment plan (recurring billing plan) for a user?

This only works if the plan already exists on the payment gateway itself.

There is no way to use this plugin to import a new member/user and have the system create a recurring billing plan
for them.

### Can I use this plugin to create new billing plans or trigger charges on the payment gateway for an imported user?

No.

### What are the constraints for WordPress Multisite import operations?

As documented by Paid Memberships Pro, the PMPro plugin cannot be Network Activated.

This import plugin will work from the site(s) where PMPro is active _and_ have the same membership level IDs
identified as are listed in the `membership_id` column of the import file you're using.

If your primary site has a configured and active Paid Memberships Pro installation, you could theoretically start
the plugin from the Network Admin dashboard (which will send you to the primary site anyway).

The users being imported will only be linked to the site you import them to. Their membership data will only be
visible on the PMPro site(s) that have the membership level ID(s) configured that match those in the import file.

## Welcome Email Message (imported_welcome.html template) issues

If you selected the "Send the imported_member.html welcome email" option and your users still aren't receiving messages,
please make sure the 'membership_status' field is included in the import .csv file and contains the `active` value.

As a design philosophy, we treat an inactive member as somebody who should _not_  receive welcome messages
(you may disagree..?)

### What GDPR impacted data is stored by this plugin?

Obviously, there's the user data that this plugin is designed to import. This plugin does _not_ track, report, or
allow download/deletion of any data it imports. There are (now or soon) other plugin options to handle those
requirements from the GDPR legislation.

In an attempt to make the "Donation" button less intrusive, we attempt to track the computer (IP) address when
somebody clicks the button. This plugin does _not_ link the IP address to a user account, so it should be a little
more challenging to identify the person who clicked the "Donate" button for any 3rd party who gets access to your
database than simply looking at the options saved by this plugin.

The IP tracking information (the IP address) is stored in the WordPress options table (`wp_options`) using the
`e20r_import_has_donated` option name. That option can safely be deleted in the database if you do not wish to have
IPs tracked. Deleting the option from the database will obviously re-enable the Donation nag.

As long as this plugin remains installed and active on the server, the tracked IP address will automatically be
removed from the option 2 months after the admin clicked the Donation button.

The option is removed when the plugin is deactivated in the "Plugins" admin panel.

Nag tracking can be disabled altogether with the `e20r_import_donation_tracking_disabled` filter:
`add_filter( 'e20r_import_donation_tracking_disabled', '__return_true' );'`

### Installation

1. Upload the `pmpro-import-members-from-csv` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

### Screenshots

1. User import screen
2. Ongoing (background) import screen
3. Default settings on the user import screen

### Known Issues

N/A

### Changelog

See the official [CHANGELOG.md file](CHANGELOG.md).

### Supported Filters and Actions

The list of filters and actions supported by this plugin can be found in the [Filters](./docs/FILTERS.md) and
[Actions](./docs/ACTIONS.md) documentation.
