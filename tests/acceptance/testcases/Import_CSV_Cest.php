<?php

namespace E20R\Tests\Acceptance;

use AcceptanceTester;

class Import_CSV_Cest {

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
		$i->amOnAdminPage( '' );
		$i->see( 'Import Members' );
	}
}
