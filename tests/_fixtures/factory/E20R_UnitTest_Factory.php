<?php

namespace E20R\Tests\Fixtures\Factory;

class E20R_UnitTest_Factory extends \WP_UnitTest_Factory {

	/**
	 * @var E20R_UnitTest_Factory_For_PMProLevel|null
	 */
	public $pmprolevels = null;

	/**
	 * @var E20R_UnitTest_Factory_For_PMProOrders|null
	 */
	public $pmproorders = null;

	/**
	 * @var E20R_UnitTest_Factory_For_PMProMembers|null
	 */
	public $pmpromebers = null;

	public function __construct() {
		parent::__construct();
		$this->tables_exist();
		$this->pmprolevels = new E20R_UnitTest_Factory_For_PMProLevel( $this );
		$this->pmproorders = new E20R_UnitTest_Factory_For_PMProOrders( $this );
		$this->pmpromebers = new E20R_UnitTest_Factory_For_PMProMembers( $this );
	}

	/**
	 * Do the tables we need for our testing exist?
	 *
	 * @return false|void
	 *
	 * @access private
	 */
	private function tables_exist() {
		global $wpdb;

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		$table_names = array(
			$wpdb->pmpro_memberships_users,
			$wpdb->pmpro_membership_orders,
			$wpdb->pmpro_membership_levels,
		);

		foreach ( $table_names as $table_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

			if ( empty( $result ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( "Error: Table {$table_name} does not exist!" );
				return false;
			}
		}

		return true;
	}
}
