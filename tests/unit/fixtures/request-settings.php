<?php
/*
 *  Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

namespace E20R\Test\Unit\Fixtures;

use E20R\Import_Members\Import_Members;
use Brain\Monkey\Functions;

/**
 * Fixture for the CSV::get_import_file_path() unit test
 *
 * Expected info for fixture:
 *      string full path, (string) $expected_file_name
 *
 * @return array[]
 */
function request_settings() {
	return array(
		// $_REQUEST
		array(
			'update_users'                => 1,
			'background_import'           => 1,
			'deactivate_old_memberships'  => 0,
			'create_order'                => 0,
			'password_nag'                => 0,
			'password_hashing_disabled'   => 0,
			'new_user_notification'       => 1,
			'suppress_pwdmsg'             => 1,
			'admin_new_user_notification' => 0, // WP's standard Admin notification
			'send_welcome_email'          => 0, // User notification w/custom template
			'new_member_notification'     => 0, // WP's standard User notification
			'per_partial'                 => 1, // Whether to batch this (and background it)
			'site_id'                     => 1, // The WordPress Site ID (default is 1)
		),
		array(
			'update_users'                => 1,
			'background_import'           => 1,
			'deactivate_old_memberships'  => 0,
			'create_order'                => 0,
			'password_nag'                => 0,
			'password_hashing_disabled'   => 0,
			'new_user_notification'       => 1,
			'suppress_pwdmsg'             => 1,
			'admin_new_user_notification' => 0, // WP's standard Admin notification
			'send_welcome_email'          => 0, // User notification w/custom template
			'new_member_notification'     => 0, // WP's standard User notification
			'per_partial'                 => 1, // Whether to batch this (and background it)
			'site_id'                     => 1, // The WordPress Site ID (default is 1)
		),
		array(
			'update_users'                => 1,
			'background_import'           => 1,
			'deactivate_old_memberships'  => 0,
			'create_order'                => 0,
			'password_nag'                => 0,
			'password_hashing_disabled'   => 0,
			'new_user_notification'       => 1,
			'suppress_pwdmsg'             => 1,
			'admin_new_user_notification' => 0, // WP's standard Admin notification
			'send_welcome_email'          => 0, // User notification w/custom template
			'new_member_notification'     => 0, // WP's standard User notification
			'per_partial'                 => 1, // Whether to batch this (and background it)
			'site_id'                     => 1, // The WordPress Site ID (default is 1)
		),
	);
}