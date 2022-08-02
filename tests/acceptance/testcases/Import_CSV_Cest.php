<?php
/**
 *   Copyright (c) 2022. - Eighty / 20 Results by Wicked Strong Chicks.
 *   ALL RIGHTS RESERVED
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package E20R\Tests\Acceptance\Import_CSV_Cest
 */

namespace E20R\Tests\Acceptance;

use AcceptanceTester;

class Import_CSV_Cest {

	/**
	 * Setting up the test environment by activating the required plugins
	 *
	 * @param AcceptanceTester $i
	 *
	 * @return void
	 */
	public function _before( AcceptanceTester $i ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		// I can activate the plugin successfully.
		$i->loginAsAdmin();
		$i->amOnPluginsPage();
		$i->seePluginInstalled( 'paid-memberships-pro' );
		$i->activatePlugin( 'paid-memberships-pro' );
		$i->seePluginActivated( 'paid-memberships-pro' );
		$i->seePluginInstalled( '00-e20r-utilities' );
		$i->activatePlugin( '00-e20r-utilities' );
		$i->seePluginActivated( '00-e20r-utilities' );
		$i->seePluginInstalled( 'pmpro-import-members-from-csv' );
		$i->activatePlugin( 'pmpro-import-members-from-csv' );
		$i->seePluginActivated( 'pmpro-import-members-from-csv' );
	}

	/**
	 * Clean up the test data if necessary
	 *
	 * @param AcceptanceTester $i
	 *
	 * @return void
	 */
	public function _after( AcceptanceTester $i ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		// Test cleanup.
		$i->loginAsAdmin();
		$i->amOnPluginsPage();
		$i->seePluginInstalled( 'pmpro-import-members-from-csv' );
		$i->seePluginActivated( 'pmpro-import-members-from-csv' );
		$i->deactivatePlugin( 'pmpro-import-members-from-csv' );
		$i->seePluginInstalled( '00-e20r-utilities' );
		$i->seePluginActivated( '00-e20r-utilities' );
		$i->deactivatePlugin( '00-e20r-utilities' );

	}

	/**
	 * Log in as admin and access the Import Members page
	 *
	 * @param AcceptanceTester $i
	 *
	 * @return void
	 * @test
	 */
	public function it_should_visit_import_members_page( AcceptanceTester $i ) {
		$i->loginAsAdmin();
		$i->amOnAdminPage( '?page=pmpro-import-members-from-csv' );
		$i->see( 'Import Members' );
	}
}
