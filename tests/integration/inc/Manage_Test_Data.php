<?php
/**
 *
 * Copyright (c) 2016 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package E20R\Tests\Fixtures
 */

namespace E20R\Tests\Integration\Fixtures;

use Codeception\TestCase\WPTestCase;
use E20R\Import_Members\Error_Log;
use mysqli_result;
use SplFileObject;

class Manage_Test_Data {

	/**
	 * Instance of the debug info class
	 *
	 * @var null|Error_Log
	 */
	private $errorlog = null;
	/**
	 * Number of user records to process
	 *
	 * @var int|null
	 */
	private $user_line = null;

	/**
	 * The column name(s) to add data to
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * The user data to (maybe) add
	 *
	 * @var array $data
	 */
	private $data = array();

	/**
	 * The user_registered field value to use if one isn't specified
	 *
	 * @var string
	 */
	private $default_registered_time = '2001-02-12 09:38:21';

	/**
	 * An encoded password to use as the default value for a user
	 *
	 * @var string
	 */
	private $default_enc_password = '\$P\$B.hqQoTosqb3O.AUwRiIu5qU6y/xnJ1';

	/**
	 * The default (un-encoded) password to use for a user
	 *
	 * @var string
	 */
	private $default_password = 'dummy_password';

	/**
	 * Number of users to add/configure
	 *
	 * @var null
	 */
	private $users_to_configure = null;

	/**
	 * List of SQL statements
	 *
	 * @var string[]
	 */
	private $sql_array = array();

	/**
	 * Instance of the running test case...
	 *
	 * @var WPTestCase|null
	 */
	private $running_test = null;
	/**
	 * Constructor
	 *
	 * @param int|null $line User to add
	 */
	public function __construct( $running_test = null, $line = null, $errorlog = null ) {
		if ( null === $errorlog ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$errorlog = new Error_Log();
		}
		$this->errorlog = $errorlog;

		if ( null !== $running_test ) {

			if ( ! is_a( $running_test, WPTestCase::class ) ) {
				$this->errorlog->debug( 'Error: the supplied TestCase object is of the wrong type!' );
				return false;
			}

			$this->running_test = $running_test;
		}
		$this->user_line                    = $line;
		list( $this->headers, $this->data ) = $this->read_line_from_csv( $line );
	}

	/**
	 * Do the tables we need for our testing exist?
	 *
	 * @return false|void
	 */
	public function tables_exist() {
		global $wpdb;

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		$table_names = array(
			$wpdb->pmpro_memberships_users,
			$wpdb->pmpro_membership_orders,
			$wpdb->pmpro_membership_levels,
			$wpdb->usermeta,
			$wpdb->users,
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

	/**
	 * Insert test user records in database
	 *
	 * @param int $line_to_load Number of users to install/load
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function insert_user_records( $line_to_load = null ) {

		global $wpdb;
		$this->sql_array = array();

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		/** phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		ID,user_login,user_email,user_pass,first_name,last_name,display_name,role
		"","test_user_1","test_user_1@example.com","","Thomas","Example","Thomas Example","subscriber"
		"1002","kari_normann","kari_normann@example.com","","Kari","Normann","Kari Normann","subscriber"
		"","olga@owndomain.com","olga@owndomain.com","","Olga","Kvinne","Olga Kvinne","subscriber"
		"","peter@owndomain.com","peter@owndomain.com","","Peter","Mann","Peter Mann","subscriber, administrator"
		"","test_user_1","test_user_1@example.com","","Thomas","Example New","Thomas Example New","subscriber, pmpro_role_1"
		 */

		$column_map = array(
			'ID'                  => 'ID',
			'user_login'          => 'user_login',
			'user_pass'           => 'user_pass',
			'user_nicename'       => 'user_nicename',
			'user_email'          => 'user_email',
			'user_url'            => 'user_url',
			'user_registered'     => 'user_registered',
			'user_activation_key' => 'null',
			'user_status'         => 'membership_status',
			'display_name'        => 'display_name',
		);

		if ( null !== $line_to_load ) {
			$this->read_line_from_csv( $line_to_load, __DIR__ . '/test_data_to_load.csv' );
		}

		$data_list = array();

		foreach ( $column_map as $db_col => $csv_col ) {
			switch ( $csv_col ) {
				case 'membership_status':
					if ( isset( $this->data[ $csv_col ] ) && 'active' === $this->data[ $csv_col ] ) {
						$value = 1;
					} else {
						$value = 0;
					}
					break;
				case 'user_pass':
					$value = wp_hash_password( $this->default_password );
					break;
				case 'null':
					$value = null;
					break;
				default:
					$value = $this->data[ $csv_col ] ?? '';
			}

			if ( null !== $value ) {
				$data_list[ $csv_col ] = $value;
			} else {
				$data_list[ $csv_col ] = 'NULL';
			}
		}

		$this->sql_array[] = sprintf( "('%1\$s')", implode( "','", $data_list ) );

		$this->users_to_configure = count( $this->sql_array );

		return $this->add_to_db( $wpdb->users );
	}

	/**
	 * Read CSV file data and return as an array to caller
	 *
	 * @param int $line_to_load The line of CSV values from the test_data_to_load.csv file to use
	 *
	 * @return array
	 */
	public function get_user_record_data( $line_to_load = null ) {

		$this->sql_array = array();

		$column_map = array(
			'ID'                  => 'ID',
			'user_login'          => 'user_login',
			'user_pass'           => 'user_pass',
			'user_nicename'       => 'user_nicename',
			'user_email'          => 'user_email',
			'user_url'            => 'user_url',
			'user_registered'     => 'user_registered',
			'user_activation_key' => 'null',
			'user_status'         => 'membership_status',
			'display_name'        => 'display_name',
		);

		if ( null !== $line_to_load ) {
			$this->read_line_from_csv( $line_to_load, __DIR__ . '/test_data_to_load.csv' );
		}

		$data_list = array();
		// $this->errorlog->debug( "Data read from test_data_to_load.csv: " . print_r( $this->data, true ) );

		foreach ( $column_map as $db_col => $csv_col ) {

			switch ( $csv_col ) {
				case 'user_pass':
					$value = wp_hash_password( $this->default_password );
					break;
				case 'null':
					$value = null;
					break;
				default:
					$value = $this->data[ $csv_col ] ?? '';
			}

			if ( 'null' !== $csv_col ) {
				if ( null !== $value ) {
					$data_list[ $csv_col ] = $value;
				} else {
					$data_list[ $csv_col ] = 'NULL';
				}
			}
		}

		$this->users_to_configure = count( $data_list );
		return $data_list;
	}

	/**
	 * Insert usermeta for test purposes
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function insert_usermeta() {
		global $wpdb;

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		$sql  = "REPLACE INTO {$wpdb->usermeta} VALUES ";
		$sql .= "(19,2,'nickname','test_user_1'),(20,2,'first_name','Test'),";
		$sql .= "(21,2,'last_name','User1'),(22,2,'description','')";
		$sql .= ",(23,2,'rich_editing','true'),(24,2,'syntax_highlighting','true')";
		$sql .= ",(25,2,'comment_shortcuts','false'),";
		$sql .= "(26,2,'admin_color','fresh'),(27,2,'use_ssl','0'),(28,2,'show_admin_bar_front','true'),(29,2,'locale',''),";
		$sql .= "(30,2,'wp_capabilities','a:1:{s:10:\"subscriber\";b:1;}'),(31,2,'wp_user_level','0'),";
		$sql .= "(32,2,'session_tokens','a:2:{s:64:\"0be1b6f7347cdde32e4cb47daa5199f474125d754d51f981e67b05497381e1ce\";a:4:{s:10:\"expiration\";i:16
45868193;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658593;}s:64:\"db1e71412d6400ac1432449b60e74fe68824a6126bce5d1842245ea118c67635\";a:4:{s:10:\"expiration\";i:1645868193;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658593;}}'),";
		$sql .= "(33,2,'pmpro_logins','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}'),";
		$sql .= "(34,2,'pmpro_CardType','Visa'),(35,2,'pmpro_AccountNumber','XXXX-XXXX-XXXX-4242'),(36,2,'pmpro_ExpirationMonth','10'),";
		$sql .= "(37,2,'pmpro_ExpirationYear','2025'),(38,2,'pmpro_bfirstname','Test'),(39,2,'pmpro_blastname','User1'),";
		$sql .= "(40,2,'pmpro_baddress1','123 Nostreet'),(41,2,'pmpro_baddress2',''),(42,2,'pmpro_bcity','Oslo'),";
		$sql .= "(43,2,'pmpro_bstate','Oslo'),(44,2,'pmpro_bzipcode','0571'),(45,2,'pmpro_bcountry','NO'),(46,2,'pmpro_bphone','1234567890'),";
		$sql .= "(47,2,'pmpro_bemail','test_user_1@example.com'),";
		$sql .= "(48,2,'pmpro_views','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}'),";
		$sql .= "(49,3,'nickname','test_user_2'),(50,3,'first_name','Test'),(51,3,'last_name','User2'),(52,3,'description',''),";
		$sql .= "(53,3,'rich_editing','true'),(54,3,'syntax_highlighting','true'),(55,3,'comment_shortcuts','false'),";
		$sql .= "(56,3,'admin_color','fresh'),(57,3,'use_ssl','0'),(58,3,'show_admin_bar_front','true'),(59,3,'locale',''),";
		$sql .= "(60,3,'wp_capabilities','a:1:{s:10:\"subscriber\";b:1;}'),(61,3,'wp_user_level','0'),";
		$sql .= "(62,3,'session_tokens','a:2:{s:64:\"1b9d09d1e9c87aa6dcc846c8a29b2279044120ace34692d41af86a91b4d0ab02\";a:4:{s:10:\"expiration\";i:1645868302;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658702;}s:64:\"27de0b4225084355d89f58c576c91ebebb85efe5a2f37870cb83f6439517f523\";a:4:{s:10:\"expiration\";i:1645868302;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658702;}}'),";
		$sql .= "(63,3,'pmpro_logins','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}'),(64,3,'pmpro_CardType','Visa'),";
		$sql .= "(65,3,'pmpro_AccountNumber','XXXX-XXXX-XXXX-4242'),(66,3,'pmpro_ExpirationMonth','01'),(67,3,'pmpro_ExpirationYear','2026'),(68,3,'pmpro_bfirstname','Test'),";
		$sql .= "(69,3,'pmpro_blastname','User2'),(70,3,'pmpro_baddress1','234 Nostreet'),(71,3,'pmpro_baddress2',''),(72,3,'pmpro_bcity','Oslo'),(73,3,'pmpro_bstate','Oslo'),";
		$sql .= "(74,3,'pmpro_bzipcode','0517'),(75,3,'pmpro_bcountry','NO'),(76,3,'pmpro_bphone','1234567890'),(77,3,'pmpro_bemail','test_user_2@example.com'),";
		$sql .= "(78,3,'pmpro_views','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}');";

		// Insert user metadata
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->query( $sql );
	}

	/**
	 * Insert the PMPro order(s) we need for testing
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function insert_order_data( $line_to_load = null ) {
		global $wpdb;

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		$this->sql_array = array(
			"(1,'4240DB7E44','c87a9d2f9028edc0cdd7b0f9fda961e4',2,1,'','Test User1','123 Nostreet','Oslo','Oslo','0571','NO','1234567890','25','0','',1,0,'','25','','Visa','XXXXXXXXXXXX4242','10','2025','success','','sandbox','TEST4240DB7E44','','2022-02-12 09:36:34','','',''),",
			"(2,'E26C893AC4','e58966f4c242d4835fd72d55f60174bd',3,2,'','Test User2','234 Nostreet','Oslo','Oslo','0517','NO','1234567890','15','0','',2,0,'','15','','Visa','XXXXXXXXXXXX4242','01','2026','success','','sandbox','TESTE26C893AC4','TESTE26C893AC4','2022-02-12 09:38:22','','','');",
		);

		return $this->add_to_db( $wpdb->pmpro_membership_orders );
	}

	/**
	 * Insert the PMPro membership level info we need for testing
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function insert_level_data() {
		global $wpdb;

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		$this->sql_array = array(
			"(1,'Test Level 1 (1 year long)','','',25.00000000,0.00000000,0,'',0,0.00000000,0,1,1,'Year')",
			"(2,'Test Level 2 (Recurring)','','',15.00000000,10.00000000,1,'Month',0,0.00000000,0,1,0,'')",
		);

		return $this->add_to_db( $wpdb->pmpro_membership_levels );
	}

	/**
	 * Insert the PMPro Member (user) data we need for testing
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function insert_member_data() {

		global $wpdb;

		$column_map      = array(
			'id'              => 'null',
			'user_id'         => 'membership_user_id',
			'membership_id'   => 'membership_id',
			'code_id'         => 'membership_code_id',
			'initial_payment' => 'membership_initial_payment',
			'billing_amount'  => 'membership_billing_amount',
			'cycle_number'    => 'membership_cycle_number',
			'cycle_period'    => 'membership_cycle_period',
			'billing_limit'   => 'membership_billing_limit',
			'trial_amount'    => 'membership_trial_amount',
			'trial_limit'     => 'membership_trial_limit',
			'status'          => 'membership_status',
			'startdate'       => 'membership_startdate',
			'enddate'         => 'membership_enddate',
			'modified'        => 'null',
		);
		$this->sql_array = array();
		$sql             = '(';
		foreach ( $column_map as $db_col => $csv_col ) {
			$sql .= sprintf( '\'%1$s\',', $this->data[ $csv_col ] ?? '' );
		}

		$this->sql_array[] = $sql;

		$this->sql_array = array(
			"(1,2,1,0,25.00000000,0.00000000,0,'',0,0.00000000,0,'active','2022-02-12 09:36:33','2023-02-12 23:59:59','2022-02-12 09:36:34')",
			"(2,3,2,0,15.00000000,10.00000000,1,'Month',0,0.00000000,0,'active','2022-02-12 09:38:22','0000-00-00 00:00:00','2022-02-12 09:38:22')",
			"(2,4,2,0,15.00000000,10.00000000,1,'Month',0,0.00000000,0,'active','2021-02-12 09:38:22','0000-00-00 00:00:00','2022-02-12 09:38:22')",
			"(1,1002,1,0,25.00000000,0.00000000,0,'',0,0.00000000,0,'active','2020-02-12 09:36:33','2023-02-12 23:59:59','2022-02-12 09:36:34')",
		);

		return $this->add_to_db( $wpdb->pmpro_memberships_users );
	}

	/**
	 * Add specified number of records to the specified table
	 *
	 * @param string $table Name of the DB table
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	private function add_to_db( $table ) {

		global $wpdb;

		if ( null === $wpdb ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			$this->errorlog->debug( 'No WPDB defined?!?' );
			trigger_error( 'WordPress environment is not running. Invalid test' );
		}

		if ( null === $this->users_to_configure ) {
			$this->users_to_configure = count( $this->sql_array );
		}

		$sql = "REPLACE INTO {$table} VALUES ";

		foreach ( range( 0, ( $this->users_to_configure - 1 ) ) as $i ) {
				$sql .= sprintf( '%1$s, ', $this->sql_array[ $i ] );
		}

		$sql = preg_replace( '/(.*), $/', '$1;', $sql );

		$this->errorlog->debug( "SQL: '{$sql}'" );
		// Insert user metadata
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->query( $sql );
	}

	/**
	 * Read from the specific CSV file (with path)
	 *
	 * @param int    $line_id The Line # to read from (line # 0 = header)
	 * @param string $file_name Name of the CSV file to read from
	 *
	 * @return mixed
	 */
	private function read_line_from_csv( $line_id, $file_name = __DIR__ . '/test_data_to_load.csv' ) {

		$file_object = new SplFileObject( $file_name, 'r' );
		$data_array  = array();
		$line        = array();

		// Use the expected delimiters, enclosures and escape characters
		$file_object->setCsvControl(
			E20R_IM_CSV_DELIMITER,
			E20R_IM_CSV_ENCLOSURE,
			E20R_IM_CSV_ESCAPE
		);
		$file_object->setFlags(
			SplFileObject::READ_AHEAD |
			SplFileObject::DROP_NEW_LINE |
			SplFileObject::SKIP_EMPTY
		);

		// Read header for file
		$this->headers = $this->make_header_array( $file_object->fgetcsv() );

		// Find the first entry
		$file_object->seek( (int) $line_id );
		$line = $file_object->fgetcsv();

		foreach ( $line as $key => $value ) {
			$column_name = $this->headers[ $key ];
			$column      = trim( $value );
			if ( ! empty( $column_name ) ) {
				$this->data[ $column_name ] = $column;
			}
		}

		return array( $this->headers, $this->data );
	}

	/**
	 * Set the CSV file line to import into the WP DB
	 *
	 * @param int $number The line number from the CSV file (to load)
	 *
	 * @return void
	 */
	public function set_line( $number = null ) {
		$this->user_line = $number;
	}

	/**
	 * Create the header array (skip empty header entries)
	 *
	 * @param string[] $line The CSV file entry as an array
	 *
	 * @return string[]
	 */
	private function make_header_array( $line ) {
		return $this->strip_bom( $line );
	}

	/**
	 * Remove the BOM character from the array entry (string)
	 *
	 * @param string[] $text_array
	 *
	 * @return string[]
	 */
	private function strip_bom( $text_array ) {
		// Clear the old (possible) BOM 'infected' key
		$bom           = pack( 'H*', 'EFBBBF' );
		$text_array[0] = preg_replace( "/^{$bom}/", '', $text_array[0] );
		reset( $text_array );

		return $text_array;
	}

	/**
	 * Maybe delete pre-existing users who may have been added by another test
	 *
	 * @param bool       $clear_user Whether to delete a pre-existing user record (or not)
	 * @param array|null $import_data The wp_users data to identify and delete the user (if we're supposed to delete them)
	 *
	 * @return void
	 */
	public function maybe_delete_user( $clear_user = false, $import_data = null ) {
		// Let us test with/without deleting existing users
		if ( true === $clear_user && null !== $import_data ) {
			$user_to_delete = null;
			$id_fields      = array(
				'ID'         => 'ID',
				'user_login' => 'login',
				'user_email' => 'email',
			);

			foreach ( $id_fields as $field_name => $user_by_field ) {
				$this->errorlog->debug( "Attempting to locate user from {$field_name}" );
				if ( isset( $user_data[ $field_name ] ) && ! empty( $user_data[ $field_name ] ) ) {
					$this->errorlog->debug( "Import has data in {$field_name} column" );
					$user = get_user_by( $user_by_field, $user_data[ $field_name ] );
					if ( ! empty( $user->ID ) ) {
						$this->errorlog->debug( 'Found user to delete: ' . $user->ID );
						break;
					}
				}
			}

			if ( ! empty( $user_to_delete ) ) {
				$this->errorlog->debug( "Removing a pre-existing user with ID {$user_to_delete->get('ID')} from the WP database" );
				wp_delete_user( $user_to_delete->ID );
			}
		}
	}
}
