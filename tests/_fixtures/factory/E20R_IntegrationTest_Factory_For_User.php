<?php

namespace E20R\Tests\Fixtures\Factory;

use WP_User;

class E20R_IntegrationTest_Factory_For_User extends E20R_IntegrationTest_Factory_For_Thing {

	public function __construct( $factory = null, $supplied_password = null ) {
		$this->default_generation_definitions = array(
			'user_login' => new E20R_IntegrationTest_Generator_Sequence( 'user_%s', 2 ),
			'user_email' => new E20R_IntegrationTest_Generator_Sequence( 'user_%s@example.org', 2 ),
			'user_pass'  => $supplied_password ?? 'password',
		);
	}

	public function create_object( $args ) {
		return wp_insert_user( $args );
	}

	public function update_object( $user_id, $fields ) {
		$fields['ID'] = $user_id;
		return wp_update_user( $fields );
	}

	public function get_object_by_id( $user_id ) {
		return new WP_User( $user_id );
	}
}
