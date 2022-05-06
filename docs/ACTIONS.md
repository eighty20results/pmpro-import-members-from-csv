### Supported Actions

* `e20r_import_load_licensed_modules` - Will load all licensed PMPro Import Members from CSV modules
* `e20r_import_page_setting_html` - Can be used to add additional import settings HTML for the settings page
* `e20r_before_user_import` - Executed _before_ the WP_User record has been created or updated during the import process.
* `e20r_after_user_import` - Executed _after_ the WP_User record has been created or updated during the import process.
* `e20r_import_trigger_membership_module_imports` - Execute module specific import activities. I.e. for the PMPro Sponsored Members, or PMPro BuddyPress and other add-ons that have PMPro membership related information to configure for the new/updated members.
* `e20r_import_post_members` - Actions to perform after the member data has been fully imported
