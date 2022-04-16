<?php
/**
 * Copyright 2011-2022  Stranger Studios
 * (email : info@paidmembershipspro.com)
 * GPLv2 Full license details in license.txt
 */

if ( ! class_exists( 'MemberOrder' ) ) {

	/**
	 * PMPro MemberOrder class
	 */
	class MemberOrder {

		/**
		 * Stores the MemberOrder Properties (using mapping through magic methods)
		 *
		 * @var stdClass|null
		 */
		private $data = null;

		/**
		 * Is the order configured to not use a gateway
		 *
		 * @var bool
		 */
		private $nogateway = true;

		/**
		 * Whether this MemberOrder is a renewal of a previous order, or not
		 *
		 * @var bool
		 */
		private $is_renewal = false;

		/**
		 * The list of supported/used MemberOrder properties
		 *
		 * @var array
		 */
		private $properties;

		/**
		 * List of billing fields and their corresponding properties
		 *
		 * @var string[][]
		 */
		private $billing_map;

		/**
		 * The last SQL query executed by this class
		 *
		 * @var null|string
		 */
		private $sql_query = '';

		/**
		 * MemberOrder Constructor
		 *
		 * @param null|int|string $id Order ID or Order code (string) to use when loading from DB (if applicable)
		 * @access public
		 */
		public function __construct( $id = null ) {

			$this->setProperties();

			// Various aliases for billing information (name and address info)
			$this->billing_map = array(
				'billing_name'      => array( 'name', 'Name' ),
				'billing_firstname' => array( 'firstname', 'FirstName' ),
				'billing_lastname'  => array( 'lastname', 'LastName' ),
				'billing_street'    => array( 'street', 'Address1' ),
				'billing_city'      => array( 'city', 'City' ),
				'billing_zip'       => array( 'zip ', 'Zip' ),
				'billing_state'     => array( 'state', 'State' ),
				'billing_country'   => array( 'country', 'CountryCode' ),
				'billing_phone'     => array( 'phone', 'PhoneNumber' ),
			);

			if ( null === $id ) {
				$this->data = new stdClass();

				foreach ( $this->properties as $property => $default_value ) {
					$this->data->{$property} = $this->getDefaultValue( $property );
				}
			} else {
				// Load the data if an ID was passed/received
				if ( is_numeric( $id ) ) {
					$this->getMemberOrderByID( $id );
				} else {
					$this->getMemberOrderByCode( $id );
				}
			}
		}

		/**
		 * Configure the MemberOrder data properties and the format to use when saving data to the DB
		 *
		 * @access private
		 */
		private function setProperties() {
			$properties = array(
				// Associative array indicating which of the fields should be saved
				'id'                          => '%d',
				'code'                        => '%s',
				'user_id'                     => '%d',
				'user'                        => false,
				'session_id'                  => '%s',
				'membership_id'               => '%d',
				'membership_level'            => false,
				'subtotal'                    => '%s',
				'total'                       => '%s',
				'tax'                         => '%s',
				'InitialPayment'              => false,
				'couponamount'                => false,
				'certificate_id'              => '%d',
				'certificateamount'           => '%s',
				'payment_type'                => '%s',
				'paypal_token'                => '%s',
				'cardtype'                    => '%s',
				'accountnumber'               => '%s',
				'timestamp'                   => '%s',
				'datetime'                    => '%s',
				'expirationmonth'             => '%s',
				'expirationyear'              => '%s',
				'ExpirationDate'              => false,
				'CVV2'                        => false,
				'status'                      => '%s',
				'gateway'                     => '%s',
				'Gateway'                     => false,
				'gateway_environment'         => '%s',
				'subscription_transaction_id' => '%s',
				'payment_transaction_id'      => '%s',
				'affiliate_id'                => '%s',
				'affiliate_subid'             => '%s',
				'notes'                       => '%s',
				'checkout_id'                 => '%d',
				'billing'                     => false,
				'billing_name'                => '%s',
				'billing_street'              => '%s',
				'billing_city'                => '%s',
				'billing_zip'                 => '%s',
				'billing_country'             => '%s',
				'billing_phone'               => '%s',
				'FirstName'                   => false,
				'LastName'                    => false,
				'Address1'                    => false,
				'City'                        => false,
				'State'                       => false,
				'Zip'                         => false,
				'CountryCode'                 => false,
				'PhoneNumber'                 => false,
				'error'                       => false,
			);

			if ( ! function_exists( 'apply_filters' ) ) {
				$this->properties = $properties;
				return;
			}

			$this->properties = apply_filters(
				'pmpro_member_order_properties_with_formatting',
				$properties
			);
		}

		/**
		 * Configure default billing fields for the MemberOrder object
		 *
		 * @return void
		 *
		 * @access private
		 */
		private function setDefaultBilling() {

			// Overwrites any previous billing info
			$this->data->billing = new stdClass();

			// Assign all possible properties for the supplied billing property
			// and set it to its default value of '' (empty string)
			foreach ( $this->billing_map as $default => $aliases ) {
				$this->data->billing->{$default} = '';
				foreach ( $aliases as $alternate ) {
					$this->data->{$alternate} = '';
				}
			}
		}

		/**
		 * Set the default value for the supplied parameter (if it exists)
		 *
		 * @param string $key_name The name of the MemberOrder property
		 *
		 * @return int|string
		 *
		 * @access private
		 */
		private function getDefaultValue( $key_name ) {
			global $current_user;

			switch ( $key_name ) {
				case 'status':
					$value = 'success';
					break;
				case 'gateway':
					$value = 'stripe';
					break;
				case 'gateway_environment':
					$value = 'test';
					break;
				case 'user_id':
					$value = 0;
					break;
				case 'code':
					$value = $this->getRandomCode();
					break;
				// Both of these return a stdClass() object
				case 'billing':
					$this->setDefaultBilling();
					$value = $this->get( $key_name );
					break;
				case 'Email':
					$value = '';
					break;
				case 'timestamp':
					$value = gmdate( 'Y-m-d H:i:s' );
					break;
				default:
					$value = '';
			}


			return $value;
		}

		/**
		 * Magic method to set a valid MemberOrder parameter
		 *
		 * @param string $property The class property to return the value of
		 *
		 * @return false|string|stdClass|int
		 *
		 * @access public
		 */
		public function __get( $property ) {

			if ( ! $this->isPresent( $property ) ) {
				return false;
			}

			if ( isset( $this->billing_map[ $property ] ) ) {
				$property = $this->billing_map[ $property ];
			}

			if ( in_array( $property, $this->billing_map[ $property ], true ) ) {
				return $this->data->billing->{$property};
			} else {
				return $this->data->{$property};
			}
		}

		/**
		 * Wrapper for the __get() magic method
		 *
		 * @param string $property The name of the MemberOrder property to return the value of
		 *
		 * @return false|int|stdClass|string
		 *
		 * @access public
		 */
		public function get( $property ) {
			return $this->__get( $property );
		}

		/**
		 * Magic method for setting MemberOrder properties.
		 *
		 * This method does not update custom fields in the database. It only stores
		 * the value for the in-memory MemberOrder instance.
		 *
		 * @param string $property MemberOrder property name
		 * @param mixed $value MemberOrder property value
		 *
		 * @access public
		 */
		public function __set( $property, $value ) {

			if ( ! $this->isPresent( $property ) ) {
				return false;
			}

			if ( 'membership_id' === $property ) {
				$ml_value = null;

				if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
					$ml_value = pmpro_getMembershipLevelForUser( $this->get( 'user_id' ), true );
				}

				$this->data->membership_level = $ml_value;
			}

			if ( 'sqlQuery' === $property ) {
				$property = 'sql_query';
			}

			if ( $this->isABillingField( $property ) ) {
				if ( empty( $this->data->billing ) ) {
					$this->setDefaultBilling();
				}
				if ( 'street' === $property ) {
					$this->data->Address1 = $value;
				}
				$this->data->billing->{$property} = ! empty( $value ) ? trim( $value ) : null;
			} else {
				if ( 'gateway' === $property ) {
					$this->data->Gateway = ! empty( $value ) ? trim( $value ) : null;
				}
				// FIXME: billing_* properties aren't correct here (array to string conversion)
				$this->data->{$property} = ! empty( $value ) ? trim( $value ) : null;
			}

			return true;
		}

		/**
		 * Wrapper for __set() magic method
		 *
		 * @param string $property The name of the MemberOrder property
		 * @param mixed $value The value to assign
		 *
		 * @return void
		 *
		 * @access public
		 */
		public function set( $property, $value ) {
			$this->__set( $property, $value );
		}

		/**
		 * Verify if the specified property exists in the MemberOrder
		 *
		 * @param string $key Property name to look for
		 * @param string $location The config array this data belongs to.
		 *
		 * 'class' => a class property,
		 * 'billing' => the key or one of the aliases from the $this->billing_map array
		 * 'any' => Any of the locations above
		 *
		 * @return bool
		 *
		 * @access private
		 */
		private function isPresent( $key, $location = 'any' ) {

			if ( empty( $this->properties ) ) {
				$this->setProperties();
			}

			switch ( $location ) {
				case 'any':
					$present = in_array( $key, $this->properties, true ) ||
									$this->isABillingField( $key );
					break;
				case 'billing':
					$present = $this->isABillingField( $key );
					break;
				case 'class':
					$present = in_array( $key, $this->properties, true );
					break;
				default:
					$present = false;
			}

			return $present;
		}

		/**
		 * Is the specified property one of the billing field or it's alias(s)?
		 *
		 * @param string $property The (billing) property, or it's alias, we're checking for the existence of
		 *
		 * @return bool
		 *
		 * @access private
		 */
		private function isABillingField( $property ) {
			$present = false;
			foreach ( $this->billing_map as $default => $property_aliases ) {
				if ( $property === $default || in_array( $property, $property_aliases, true ) ) {
					$present = true;
					break;
				}
			}

			return $present;
		}

		/**
		 * Magic method for checking the existence of a certain custom field.
		 *
		 * @param string $property MemberOrder property to check if is set
		 *
		 * @return bool Whether the given user meta key is set.
		 *
		 * @access public
		 */
		public function __isset( $property ) {
			return $this->isPresent( $property );
		}

		/**
		 * Magic method for unsetting a certain custom field.
		 *
		 * @param string $key MemberOrder property to unset
		 *
		 * @access public
		 */
		public function __unset( $key ) {
			if ( isset( $this->data->{$key} ) ) {
				unset( $this->data->{$key} );
			}

			if ( isset( $this->data->billing->{$key} ) ) {
				unset( $this->data->billing->{$key} );
			}
		}

		/**
		 * Return an array representation.
		 *
		 * @return array Array representation.
		 *
		 * @access public
		 */
		public function toArray() {
			return get_object_vars( $this->data );
		}

		/**
		 * Returns an empty (but complete) order object.
		 *
		 * @return MemberOrder $order - a 'clean' order object
		 *
		 * @since: 1.8.6.8
		 * @access public
		 */
		public function getEmptyMemberOrder() {

			// Cast the stdClass to an associative array and iterate through
			foreach ( (array) $this->data as $key => $value ) {
				$this->data->{$key} = $this->getDefaultValue( $key );
			}

			return $this;
		}

		/**
		 * Set the FName, LName information
		 *
		 * @param string $name The supplied name information to split and assign to properties
		 *
		 * @return void
		 *
		 * @access private
		 */
		private function setNameInfo( $name ) {

			$this->data->Name         = $name;
			$this->data->billing_name = $name;

			// split up some values
			$nameparts = pnp_split_full_name( $name );

			if ( ! empty( $nameparts['fname'] ) ) {
				$this->data->FirstName = $nameparts['fname'];
			} else {
				$this->data->FirstName = '';
			}
			if ( ! empty( $nameparts['lname'] ) ) {
				$this->data->LastName = $nameparts['lname'];
			} else {
				$this->data->LastName = '';
			}

		}

		/**
		 * Set the billing parameter value for the default and any aliases
		 *
		 * @param string $parameter The billing parameter to set
		 * @param string $value  The value to assign to the parameter
		 *
		 * @return void
		 *
		 * @access private
		 */
		private function maybe_set_billing_info( $parameter, $value ) {
			// TODO: Make sure we set First/Last name and full name parameters properly
			switch ( $parameter ) {
				case 'Name':
				case 'Address1':
				case 'Street':
					break;
			}

			// FIXME: More stuff to update??
		}

		/**
		 * Retrieve a member order from the DB by ID
		 *
		 * @param int|null|string The order ID to retrieve the order record for
		 *
		 * @return false|int
		 * @access public
		 */
		public function getMemberOrderByID( $id ) {
			global $wpdb;

			if ( ! $id ) {
				return false;
			}

			if ( empty( $wpdb ) ) {
				return false;
			}

			$dbobj = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->pmpro_membership_orders} WHERE id = %s LIMIT 1",
					$id
				)
			);

			if ( empty( $dbobj ) ) {
				return false;
			}

			foreach ( $dbobj as $key => $value ) {
				if ( 'name' === $key ) {
					$this->setNameInfo( $value );
				}

				$this->maybe_set_billing_info( $key, $value );

				// Add the WP_User object for the user_id specified
				if ( 'user_id' === $key ) {
					$this->set( 'user', $this->getUserObject( $value ) );
				}

				// Save the DB object properties returned to this class's data object
				$this->set( $key, $value );
			}

			// Return the email address for the user (use cached value if necessary/desirable)
			$user = $this->getUserObject( $dbobj->user_id );
			$this->set( 'Email', $user->user_email );

			// date formats sometimes useful
			$this->set( 'ExpirationDate', "{$this->data->expirationmonth}{$this->data->expirationyear}" );
			$this->set( 'ExpirationDate_YdashM', "{$this->data->expirationyear}-{$this->data->expirationmonth}" );

			// reset the gateway value
			if ( empty( $this->nogateway ) ) {
				$this->setGateway( 'test' );
			}

			return $this->get( 'id' );
		}

		/**
		 * Fetch the User record based on the supplied WP User ID value
		 *
		 * @param int $user_id The ID of the user object to fetch
		 *
		 * @return array|false|object|stdClass[]|WP_User
		 */
		private function getUserObject( $user_id ) {
			global $wpdb;

			if ( function_exists( 'get_user_by' ) ) {
				$user = get_user_by( 'ID', $user_id );
			} elseif ( ! empty( $wpdb ) ) {
				$user = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->users} WHERE ID = %d LIMIT 1",
						$this->get( 'user_id' )
					)
				);
			} else {
				$user = false;
			}

			return $user;
		}

		/**
		 * Get the first order for this subscription.
		 * Useful to find the original order from a recurring order.
		 *
		 * @param string $subscription_id The ID of the payment gateway subscription record
		 *
		 * @return bool|MemberOrder Order object if found or false if not.
		 * @since 2.5
		 *
		 * @access public
		 */
		public function get_original_subscription_order( $subscription_id = '' ) {
			global $wpdb;

			// Default to use the subscription ID on this order object.
			if ( empty( $subscription_id ) && ! empty( $this->data->subscription_transaction_id ) ) {
				$subscription_id = $this->get( 'subscription_transaction_id' );
			}

			// Must have a subscription ID.
			if ( empty( $subscription_id ) ) {
				return false;
			}

			// For (real) unit testing purposes we'll have to mock $wpdb
			if ( empty( $wpdb ) ) {
				return false;
			}

			$order_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID
					 FROM {$wpdb->pmpro_membership_orders}
					 WHERE `subscription_transaction_id` = %s
					   AND `user_id` = %d
					   AND `gateway` = %s
					   AND `gateway_environment` = %s
					 ORDER BY id ASC
					 LIMIT 1",
					$subscription_id,
					$this->get( 'user_id' ),
					$this->get( 'gateway' ),
					$this->get( 'gateway_environment' )
				)
			);

			if ( ! empty( $order_id ) ) {
				return new MemberOrder( $order_id );
			}

			return false;
		}

		/**
		 * Is this order a 'renewal'?
		 *
		 * We currently define a renewal as any order from a user who has
		 * a previous paid (non-$0) order.
		 *
		 * @return bool
		 *
		 * @access public
		 */
		public function is_renewal() {
			global $wpdb;
			$older_order_id = 0;

			// If our property is already set, use that.
			if ( false !== $this->is_renewal ) {
				return $this->is_renewal;
			}

			// Can't tell if this is a renewal without a user.
			if ( empty( $this->data->user_id ) ) {
				$this->is_renewal = false;

				return $this->is_renewal;
			}

			// Check the DB.
			if ( ! empty( $wpdb ) ) {
				$this->sql_query = $wpdb->prepare(
					"SELECT `id`
						 FROM {$wpdb->pmpro_membership_orders}
						 WHERE `user_id` = %d
							AND `id` <> %d
							AND `gateway_environment` = %s
							AND `total` > 0
							AND `total` IS NOT NULL
							AND status NOT IN ('refunded', 'review', 'token', 'error')
							AND timestamp < %s
						 LIMIT 1",
					$this->get( 'user_id' ),
					$this->get( 'id' ),
					$this->get( 'gateway_environment' ),
					date( 'Y-m-d H:i:s', $this->get( 'timestamp' ) )
				);

				$older_order_id = $wpdb->get_var( $this->sql_query );
			}

			if ( ! empty( $older_order_id ) ) {
				$this->is_renewal = true;
			} else {
				$this->is_renewal = false;
			}

			return $this->is_renewal;
		}

		/**
		 * Set up the Gateway class to use with this order.
		 *
		 * @param string $gateway Name/label for the gateway to set.
		 *
		 * @access public
		 */
		public function setGateway( $gateway = null ) {
			//set the gateway property
			if ( empty( $gateway ) ) {
				$this->data->gateway = $gateway;
			}

			//which one to load?
			$classname = 'PMProGateway';    //default test gateway
			if ( ! empty( $this->data->gateway ) && 'free' !== $this->data->gateway ) {
				$classname .= '_' . $this->data->gateway; //adding the gateway suffix
			}

			if ( class_exists( $classname ) && isset( $this->data->gateway ) ) {
				$this->data->Gateway = new $classname( $this->data->gateway );
			} else {
				$this->data->Gateway = null;  //null out any current gateway
				$this->data->error   = new WP_Error(
					'PMPro1001',
					sprintf(
						esc_attr__(
							'Could not locate the gateway class file with class name = %s.',
							'paid-memberships-pro'
						),
						$classname
					)
				);
			}

			if ( ! empty( $this->data->Gateway ) ) {
				return $this->data->Gateway;
			} else {
				//gateway wasn't setup
				return false;
			}
		}

		/**
		 * Get the most recent order for a user.
		 *
		 * @param int|null $user_id ID of user to find order for.
		 * @param string|string[] $status Limit search to only orders with this status. Defaults to "success".
		 * @param int|null $membership_id Limit search to only orders for this membership level. Defaults to NULL to find orders for any level.
		 * @param string|null $gateway Limit search to the specified gateway(s)
		 * @param string|null $gateway_environment Limit search to the specified gateway environment (live or test)
		 *
		 * @return false|int|MemberOrder|stdClass|string
		 *
		 * @access public
		 */
		public function getLastMemberOrder( $user_id = null, $status = 'success', $membership_id = null, $gateway = null, $gateway_environment = null ) {
			global $current_user, $wpdb;

			if ( ! $user_id ) {
				$user_id = $current_user->ID;
			}

			if ( ! $user_id ) {
				return false;
			}

			if ( ! empty( $wpdb ) ) {
				//build query
				$this->sql_query = $wpdb->prepare( "SELECT `id` FROM {$wpdb->pmpro_membership_orders} WHERE user_id = %s", $user_id );

				if ( ! empty( $status ) && is_array( $status ) ) {
					$this->sql_query .= " AND status IN('" . implode( "','", $status ) . "') ";
				} elseif ( ! empty( $status ) ) {
					$this->sql_query .= " AND status = '" . esc_sql( $status ) . "' ";
				}

				if ( ! empty( $membership_id ) ) {
					$this->sql_query .= " AND membership_id = '" . (int) $membership_id . "' ";
				}

				if ( ! empty( $gateway ) ) {
					$this->sql_query .= " AND gateway = '" . esc_sql( $gateway ) . "' ";
				}

				if ( ! empty( $gateway_environment ) ) {
					$this->sql_query .= " AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";
				}

				$this->sql_query .= ' ORDER BY timestamp DESC LIMIT 1';

				//get id
				$id = $wpdb->get_var( $this->sql_query );

				return $this->getMemberOrderByID( $id );
			}

			return false;
		}

		/**
		 * Returns the order using the given order code.
		 *
		 * @param string $code Order code to retrieve the order of
		 *
		 * @return false|int|stdClass|MemberOrder|string
		 *
		 * @access public
		 */
		public function getMemberOrderByCode( $code ) {
			global $wpdb;

			if ( empty( $wpdb ) ) {
				return false;
			}

			$id = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE code = %s LIMIT 1", $code )
			);

			if ( ! empty( $id ) ) {
				return $this->getMemberOrderByID( $id );
			}

			return false;

		}

		/**
		 * Returns the last order using the given payment_transaction_id.
		 *
		 * @param string $payment_transaction_id The Payment gateway's transaction ID to search for
		 *
		 * @return false|int|stdClass|MemberOrder|string
		 *
		 * @access public
		 */
		public function getMemberOrderByPaymentTransactionID( $payment_transaction_id ) {

			// did they pass a trans id?
			if ( empty( $payment_transaction_id ) ) {
				return false;
			}
			global $wpdb;

			if ( empty( $wpdb ) ) {
				return false;
			}

			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE payment_transaction_id = %s LIMIT 1",
					$payment_transaction_id
				)
			);

			if ( ! empty( $id ) ) {
				return $this->getMemberOrderByID( $id );
			}

			return false;
		}

		/**
		 * Returns the last order using the given data->subscription_transaction_id.
		 *
		 * @param string $subscription_transaction_id The ID of the subscription record on the payment gateway
		 *
		 * @return false|int|stdClass|MemberOrder|string
		 *
		 * @access public
		 */
		public function getLastMemberOrderBySubscriptionTransactionID( $subscription_transaction_id ) {
			//did they pass a sub id?
			if ( empty( $subscription_transaction_id ) ) {
				return false;
			}

			global $wpdb;

			if ( empty( $wpdb ) ) {
				return false;
			}

			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE subscription_transaction_id = %s ORDER BY id DESC LIMIT 1",
					$subscription_transaction_id
				)
			);

			if ( ! empty( $id ) ) {
				return $this->getMemberOrderByID( $id );
			}

			return false;
		}

		/**
		 * Returns the last order using the given paypal token.
		 *
		 * @param string $token The PayPal token to search for
		 *
		 * @return false|int|stdClass|MemberOrder|string
		 */
		function getMemberOrderByPayPalToken( $token ) {
			global $wpdb;
			if ( ! empty( $wpdb ) ) {
				return false;
			}
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE paypal_token = %s LIMIT 1",
					$token
				)
			);

			if ( ! empty( $id ) ) {
				return $this->getMemberOrderByID( $id );
			}

			return false;
		}

		/**
		 * Get a discount code object for the code used in this order.
		 *
		 * @param bool $force If true, it will query the database again.
		 *
		 * @return null|string
		 *
		 * @access public
		 */
		public function getDiscountCode( $force = false ) {
			if ( ! empty( $this->data->discount_code ) && ! $force ) {
				return $this->data->discount_code;
			}

			global $wpdb;
			$discount_code = false;

			if ( empty( $wpdb ) ) {
				return false;
			}

			$discount_code = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT dc.* FROM {$wpdb->pmpro_discount_codes} AS dc LEFT JOIN {$wpdb->pmpro_discount_codes_uses} AS dcu ON dc.id = dcu.code_id WHERE dcu.order_id = %d LIMIT 1",
					$this->get( 'id' )
				)
			);

			//filter @since v1.7.14
			$this->set( 'discount_code', apply_filters( 'pmpro_order_discount_code', $discount_code, $this ) );

			return $discount_code;
		}

		/**
		 * Update the discount code used in this order.
		 *
		 * @param int $discount_code_id The ID of the discount code to update.
		 *
		 * @return false|string
		 *
		 * @access public
		 */
		public function updateDiscountCode( $discount_code_id ) {
			global $wpdb;
			$discount_codes_uses_id = null;

			if ( empty( $wpdb ) ) {
				return false;
			}

			// Assumes one discount code per order
			$this->sql_query        = $wpdb->prepare(
				"
			SELECT id FROM $wpdb->pmpro_discount_codes_uses
			WHERE order_id = %d
			LIMIT 1",
				$this->get( 'id' )
			);
			$discount_codes_uses_id = $wpdb->get_var( $this->sql_query );

			// INSTEAD: Delete the code use if found
			if ( empty( $discount_code_id ) ) {
				if ( ! empty( $discount_codes_uses_id ) ) {
					$wpdb->delete(
						$wpdb->pmpro_discount_codes_uses,
						array( 'id' => $discount_codes_uses_id ),
						array( '%d' )
					);
				}
			} else {
				if ( ! empty( $discount_codes_uses_id ) ) {
					// Update existing row
					$wpdb->update(
						$wpdb->pmpro_discount_codes_uses,
						array(
							'code_id'  => $discount_code_id,
							'user_id'  => $this->get( 'user_id' ),
							'order_id' => $this->get( 'id' ),
						),
						array( 'id' => $discount_codes_uses_id ),
						array( '%d', '%d', '%d' ),
						array( '%d' )
					);
				} else {
					// Insert a new row
					$wpdb->insert(
						$wpdb->pmpro_discount_codes_uses,
						array(
							'code_id'  => $discount_code_id,
							'user_id'  => $this->get( 'user_id' ),
							'order_id' => $this->get( 'id' ),
						),
						array( '%d', '%d', '%d' )
					);
				}
			}

			// Make sure to reset properties on this object
			return $this->getDiscountCode( true );
		}

		/**
		 * Get a user record for the user ID associated with this order.
		 *
		 * @return WP_User|stdClass|false|null
		 *
		 * @access public
		 */
		public function getUser() {
			global $wpdb;

			if ( ! empty( $this->data->user ) ) {
				return $this->data->user;
			}

			if ( ! empty( $wpdb ) ) {
				$user = $this->getUserObject( $this->get( 'user_id' ) );
				$this->set( 'user', $user );
			}

			// Fix the timestamp for local time
			if ( ! empty( $this->data->user ) && ! empty( $this->data->user->user_registered ) ) {
				$this->data->user->user_registered = strtotime(
					get_date_from_gmt( $this->data->user->user_registered, 'Y-m-d H:i:s' )
				);
			}

			return $this->data->user;
		}

		/**
		 * Get a membership level object for the level associated with this order.
		 *
		 * @param bool $force If true, it will query the database again.
		 *
		 * @return bool|stdClass
		 *
		 * @access public
		 */
		public function getMembershipLevel( $force = false ) {
			global $wpdb;

			if ( false === $force && ! empty( $this->data->membership_level ) ) {
				return $this->get( 'membership_level' );
			}

			//check if there is an entry in memberships_users first
			if ( empty( $this->data->user_id ) ) {
				return false;
			}

			$membership_level = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						l.id as level_id,
                        l.name,
                        l.description,
                        l.allow_signups,
                        l.expiration_number,
                        l.expiration_period,
                        mu.*,
                        UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate,
                        UNIX_TIMESTAMP(CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone)) as enddate,
                        l.name,
                        l.description,
                        l.allow_signups
			FROM {$wpdb->pmpro_membership_levels} AS l
			    LEFT JOIN {$wpdb->pmpro_memberships_users} AS mu ON l.id = mu.membership_id
			WHERE mu.status = 'active' AND l.id = %d AND mu.user_id = %d
			LIMIT 1",
					$this->get( 'membership_id' ),
					$this->get( 'user_id' )
				)
			);

			//fix the membership level id
			if ( ! empty( $membership_level->level_id ) ) {
				$membership_level->id = $membership_level->level_id;
			}

			//okay, do I have a discount code to check? (if there is no membership_level->membership_id value, that means there was no entry in memberships_users)
			if ( ! empty( $this->data->discount_code ) && empty( $membership_level->membership_id ) ) {
				if ( ! empty( $this->data->discount_code->code ) ) {
					$discount_code = $this->data->discount_code->code;
				} else {
					$discount_code = $this->data->discount_code;
				}

				$membership_level = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT l.id,
       							cl.*,
       							l.name,
       							l.description,
       							l.allow_signups
							FROM {$wpdb->pmpro_discount_codes_levels} AS cl
							    LEFT JOIN {$wpdb->pmpro_membership_levels} AS l
							        ON cl.level_id = l.id
						    	LEFT JOIN {$wpdb->pmpro_discount_codes} AS dc ON dc.id = cl.code_id
							WHERE dc.code = %s AND cl.level_id = %d LIMIT 1",
						$discount_code,
						$this->get( 'membership_id' )
					)
				);
			}

			//just get the info from the membership table	(sigh, I really need to standardize the column names for membership_id/level_id) but we're checking if we got the information already or not
			if ( empty( $membership_level->membership_id ) && empty( $membership_level->level_id ) ) {
				$membership_level = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT l.* FROM {$wpdb->pmpro_membership_levels} l WHERE l.id = %d LIMIT 1",
						$this->get( 'membership_id' )
					)
				);
			}

			// Round prices to avoid extra decimals.
			if ( ! empty( $membership_level ) ) {
				$membership_level->initial_payment = pmpro_round_price( $membership_level->initial_payment );
				$membership_level->billing_amount  = pmpro_round_price( $membership_level->billing_amount );
				$membership_level->trial_amount    = pmpro_round_price( $membership_level->trial_amount );
			}

			$this->set( 'membership_level', $membership_level );

			return $membership_level;
		}

		/**
		 * Get a membership level object at checkout
		 * for the level associated with this order.
		 *
		 * @param bool $force If true, it will reset the property.
		 *
		 * @since 2.0.2
		 *
		 * @access public
		 */
		public function getMembershipLevelAtCheckout( $force = false ) {
			global $pmpro_level;

			if ( ! empty( $this->data->membership_level ) && empty( $force ) ) {
				return $this->data->membership_level;
			}

			// If for some reason, we haven't setup pmpro_level yet, do that.
			if ( empty( $pmpro_level ) && function_exists( 'pmpro_getLevelAtCheckout' ) ) {
				$pmpro_level = pmpro_getLevelAtCheckout();
			}

			// Set the level to the checkout level global.
			$this->data->membership_level = $pmpro_level;

			// Fix the membership level id.
			if ( ! empty( $this->data->membership_level ) && ! empty( $this->data->membership_level->level_id ) ) {
				$this->data->membership_level->id = $this->data->membership_level->level_id;
			}

			// Round prices to avoid extra decimals.
			if ( ! empty( $this->data->membership_level ) && function_exists( 'pmpro_round_price' ) ) {
				$this->data->membership_level->initial_payment = pmpro_round_price( $this->data->membership_level->initial_payment );
				$this->data->membership_level->billing_amount  = pmpro_round_price( $this->data->membership_level->billing_amount );
				$this->data->membership_level->trial_amount    = pmpro_round_price( $this->data->membership_level->trial_amount );
			}

			return $this->data->membership_level;
		}

		/**
		 * Apply tax rules for the price given.
		 *
		 * @param string|float $price The price to apply the tax calculation for
		 *
		 * @return float|string|null
		 *
		 * @access public
		 */
		public function getTaxForPrice( $price ) {

			$tax_state = '';
			$tax_rate  = 0.00;

			if ( function_exists( 'pmpro_getOption' ) ) {
				//get options
				$tax_state = pmpro_getOption( 'tax_state' );
				$tax_rate  = pmpro_getOption( 'tax_rate' );
			}

			//default
			$tax     = 0;
			$billing = $this->get( 'billing' );

			//calculate tax
			if ( $tax_state && $tax_rate ) {
				//we have values, is this order in the tax state?
				if ( ! empty( $billing ) && trim( strtoupper( $billing->state ) ) == trim( strtoupper( $tax_state ) ) ) {
					//return value, pass through filter
					$tax = round( (float) $price * (float) $tax_rate, 2 );
				}
			}

			//set values array for filter
			$values = array(
				'price'     => $price,
				'tax_state' => $tax_state,
				'tax_rate'  => $tax_rate,
			);
			if ( ! empty( $billing->street ) ) {
				$values['billing_street'] = $billing->street;
			}
			if ( ! empty( $billing->state ) ) {
				$values['billing_state'] = $billing->state;
			}
			if ( ! empty( $billing->city ) ) {
				$values['billing_city'] = $billing->city;
			}
			if ( ! empty( $billing->zip ) ) {
				$values['billing_zip'] = $billing->zip;
			}
			if ( ! empty( $billing->country ) ) {
				$values['billing_country'] = $billing->country;
			}

			//filter
			$tax = apply_filters( 'pmpro_tax', $tax, $values, $this );
			$this->set( 'tax', $tax );

			return $tax;
		}

		/**
		 * Get the tax amount for this order.
		 *
		 * @param bool $force Whether to force-calculate the tax info or use cached value(s)
		 *
		 * @return float|string
		 *
		 * @access public
		 */
		public function getTax( $force = false ) {
			if ( ! empty( $this->data->tax ) && ! $force ) {
				return $this->get( 'tax' );
			}

			// reset
			$this->set( 'tax', $this->getTaxForPrice( $this->get( 'subtotal' ) ) );

			return $this->get( 'tax' );
		}

		/**
		 * Get the timestamp for this order.
		 *
		 * @param bool $gmt whether to return GMT time or local timestamp.
		 *
		 * @return int The seconds-since-epoch timestamp (UTC)
		 *
		 * @access public
		 */
		public function getTimestamp( $gmt = false ) {
			return $gmt ?
				$this->get( 'timestamp' ) :
				strtotime(
					get_date_from_gmt(
						date( 'Y-m-d H:i:s', $this->get( 'timestamp' ) )
					)
				);
		}

		/**
		 * Change the timestamp of an order by passing in year, month, day, time.
		 *
		 * $time should be adjusted for local timezone.
		 *
		 * NOTE: This function should no longer be used. Instead, set the timestamp
		 * for the order directly and call the MemberOrder->saveOrder() function.
		 * This function is no longer used on the /adminpages/orders.php page.
		 *
		 * @param int|string $year Four digit year value
		 * @param int|string $month Two digit month value
		 * @param int|string $day Two digit day value
		 * @param string|null $time Time value in HH:MM:SS format
		 *
		 * @return int|bool
		 * @deprecated
		 *
		 * @access public
		 */
		public function updateTimestamp( $year, $month, $day, $time = null ) {
			_deprecated_function( 'updateTimestamp', '2.7.4' );

			if ( empty( $this->data->id ) ) {
				return false;       //need a saved order
			}

			if ( empty( $time ) ) {
				// Just save the order date.
				$date = $year . '-' . $month . '-' . $day . ' 00:00:00';
			} else {
				$date = get_gmt_from_date( $year . '-' . $month . '-' . $day . ' ' . $time, 'Y-m-d H:i:s' );
			}

			global $wpdb;

			if ( empty( $wpdb ) ) {
				return false;
			}

			$this->sql_query = $wpdb->prepare(
				"UPDATE {$wpdb->pmpro_membership_orders} SET timestamp = %s WHERE id = %d LIMIT 1",
				$date,
				$this->get( 'id' )
			);

			do_action( 'pmpro_update_order', $this );

			if ( $wpdb->query( $this->sql_query ) !== 'false' ) {
				$this->set( 'timestamp', strtotime( $date ) );
				do_action( 'pmpro_updated_order', $this );

				return $this->getMemberOrderByID( $this->get( 'id' ) );
			} else {
				return false;
			}
		}

		/**
		 * Save/update the values of the order in the database.
		 *
		 * @access public
		 */
		public function saveOrder() {
			global $current_user, $wpdb, $pmpro_checkout_id;

			$amount = 0;

			//figure out how much we charged
			if ( ! empty( $this->data->InitialPayment ) ) {
				$amount = $this->data->InitialPayment;
			} elseif ( ! empty( $this->data->subtotal ) ) {
				$amount = $this->data->subtotal;
			}

			//Todo: Tax?!, Coupons, Certificates, affiliates
			if ( empty( $this->data->subtotal ) ) {
				$this->data->subtotal = $amount;
			}
			if ( isset( $this->data->tax ) ) {
				$tax = $this->data->tax;
			} else {
				$tax = $this->getTax( true );
			}

			//calculate total
			if ( ! empty( $this->total ) ) {
				$total = $this->total;
			} elseif ( ! isset( $this->data->total ) || empty( $this->data->total ) ) {
				$total             = (float) $amount + (float) $tax;
				$this->data->total = $total;
			} else {
				$total = 0;
			}

			// Set default values for properties that haven't been set already
			foreach ( $this->properties as $property_name ) {
				if ( empty( $this->data->{$property_name} ) ) {
					$this->data->{$property_name} = $this->getDefaultValue( $property_name );
				}
			}

			if ( empty( $this->data->datetime ) && empty( $this->data->timestamp ) ) {
				$this->data->datetime = date( 'Y-m-d H:i:s', time() );
			} elseif ( empty( $this->data->datetime ) && ! empty( $this->data->timestamp ) && is_numeric( $this->data->timestamp ) ) {
				$this->data->datetime = date( 'Y-m-d H:i:s', $this->data->timestamp );    //get datetime from timestamp
			} elseif ( empty( $this->data->datetime ) && false !== strtotime( $this->data->timestamp ) ) {
				$this->data->datetime = $this->data->timestamp;     //must have a datetime in it
			}

			if ( empty( $this->data->checkout_id ) || (int) $this->data->checkout_id < 1 ) {
				$highestval              = ! empty( $wpdb ) ? $wpdb->get_var( "SELECT MAX(checkout_id) FROM $wpdb->pmpro_membership_orders" ) : 0;
				$this->data->checkout_id = (int) $highestval + 1;
				$pmpro_checkout_id       = $this->data->checkout_id;
			}

			$data   = array();
			$format = array();

			//only on inserts, we might want to set the expirationmonth and expirationyear from ExpirationDate
			if ( ( empty( $this->data->expirationmonth ) || empty( $this->data->expirationyear ) ) && ! empty( $this->data->ExpirationDate ) ) {
				$this->data->expirationmonth = substr( $this->data->ExpirationDate, 0, 2 );
				$this->data->expirationyear  = substr( $this->data->ExpirationDate, 2, 4 );
			}

			// Load the data array for the insert or update DB operation(s)
			foreach ( $this->properties as $key => $col_format ) {
				// Skip the MemberOrder property if it's 'save' state is set to 'false'
				if ( false === $col_format ) {
					continue;
				}

				if ( 'accountnumber' === $property_name ) {
					$this->data->accountnumber = hideCardNumber( $this->data->accountnumber, false );
				}

				if ( 'billing' === $property_name ) {
					foreach ( $this->data->billing as $b_key => $b_value ) {
						if ( 'phone' === $b_key && function_exists( 'cleanPhone' ) ) {
							$b_value = cleanPhone( $b_value );
						}

						$this->data->billing->{$b_key} = $b_value;
						$data[ "billing_{$b_key}" ]    = $b_value;
					}
				} else {
					$data[ $key ] = $this->get( $key );
				}

				$format[ $key ] = $col_format;
			}

			if ( ! empty( $this->id ) ) {
				//set up actions
				$before_action = 'pmpro_update_order';
				$after_action  = 'pmpro_updated_order';

				//update
				$result = $wpdb->update(
					$wpdb->pmpro_membership_orders,
					$data,
					array( 'id' => $this->get( 'id' ) ),
					$format
				);

			} else {
				//set up actions
				$before_action = 'pmpro_add_order';
				$after_action  = 'pmpro_added_order';

				$result = $wpdb->insert(
					$wpdb->pmpro_membership_orders,
					$data,
					$format
				);

			}

			do_action( $before_action, $this );
			if ( false !== $result ) {
				if ( empty( $this->data->id ) && 'pmpro_add_order' === $before_action ) {
					$this->set( 'id', $result );
				}
				do_action( $after_action, $this );

				return $this->getMemberOrderByID( $this->get( 'id' ) );
			} else {
				return false;
			}
		}

		/**
		 * Get a random code to use as the order code.
		 *
		 * @return string|null
		 *
		 * @access public
		 */
		public function getRandomCode() {
			global $wpdb;

			// We mix this with the seed to make sure we get unique codes.
			static $count = 0;
			$count ++;

			if ( empty( $wpdb ) ) {
				return null;
			}
			while ( empty( $code ) ) {
				$scramble = md5( AUTH_KEY . microtime() . SECURE_AUTH_KEY . $count );
				$code     = substr( $scramble, 0, 10 );
				$code     = apply_filters( 'pmpro_random_code', $code, $this ); //filter
				$check    = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE code = %s LIMIT 1",
						$code
					)
				);
				if ( ! empty( $check ) || is_numeric( $code ) ) {
					$code = null;
				}
			}

			return strtoupper( $code );
		}

		/**
		 * Update the status of the order in the database.
		 *
		 * @param string $newstatus The new order status to set for this order
		 *
		 * @return bool
		 *
		 * @filter pmpro_update_order
		 * @filter pmpro_updated_order
		 *
		 * @access public
		 */
		public function updateStatus( $newstatus ) {
			global $wpdb;

			$id = $this->get( 'id' );
			if ( empty( $id ) ) {
				return false;
			}

			do_action( 'pmpro_update_order', $this );

			if ( false !== $wpdb->update( $wpdb->pmpro_membership_orders, array( 'status' => $newstatus ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ) ) {
				$this->set( 'status', $newstatus );
				do_action( 'pmpro_updated_order', $this );

				return true;
			} else {
				return false;
			}
		}

		/**
		 * Call the process method of the gateway class.
		 *
		 * @return object|bool
		 *
		 * @access public
		 */
		public function process() {
			if ( is_object( $this->data->Gateway ) ) {
				return $this->data->Gateway->process( $this );
			}

			return false;
		}

		/**
		 * For offsite gateways with a confirm step.
		 *
		 * @return object|bool
		 * @since 1.8
		 *
		 * @access public
		 */
		public function confirm() {
			if ( is_object( $this->data->Gateway ) ) {
				return $this->data->Gateway->confirm( $this );
			}

			return false;
		}

		/**
		 * Cancel an order and call the cancel step of the gateway class if needed.
		 *
		 * @return bool
		 *
		 * @access public
		 */
		public function cancel() {
			global $wpdb;

			if ( empty( $wpdb ) ) {
				return false;
			}

			//only need to cancel on the gateway if there is a subscription id
			if ( empty( $this->data->subscription_transaction_id ) ) {
				//just mark as cancelled
				$this->updateStatus( 'cancelled' );

				return true;
			} else {
				//get some data
				$order_user = get_userdata( $this->get( 'user_id' ) );

				//cancel orders for the same subscription
				//Note: We do this early to avoid race conditions if and when the
				//gateway send the cancel webhook after cancelling the subscription.
				do_action( 'pmpro_update_order', $this );

				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->pmpro_membership_orders
						SET `status` = 'cancelled'
						WHERE `user_id` = %d
							AND `membership_id` = %d
							AND `gateway` = %s
							AND `gateway_environment` = %s
							AND `subscription_transaction_id` = %s
							AND `status` IN( 'success', '' )",
						$this->get( 'user_id' ),
						$this->get( 'membership_id' ),
						$this->get( 'gateway' ),
						$this->get( 'gateway_environment' ),
						$this->get( 'subscription_transaction_id' )
					)
				);
				do_action( 'pmpro_updated_order', $this );

				//cancel the gateway subscription first
				if ( is_object( $this->data->Gateway ) ) {
					$result = $this->data->Gateway->cancel( $this );
				} else {
					$result = false;
				}

				if ( false === $result ) {
					//there was an error, but cancel the order no matter what
					$this->updateStatus( 'cancelled' );

					if ( class_exists( 'PMProEmail' ) ) {
						//we should probably notify the admin
						$pmproemail                = new PMProEmail();
						$pmproemail->template      = 'subscription_cancel_error';
						$pmproemail->data          = array(
							'body' => '<p>' .
									sprintf(
										esc_attr__(
											'There was an error canceling the subscription for user with ID=%s. You will want to check your payment gateway to see if their subscription is still active.',
											'paid-memberships-pro'
										),
										strval(
											$this->get( 'user_id' )
										) .
										  '</p><p>Error: ' . $this->get( 'error' ) . '</p>'
									),
						);
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'User Email', 'paid-memberships-pro' ) . ': ' . $order_user->user_email . '</p>';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'Username', 'paid-memberships-pro' ) . ': ' . $order_user->user_login . '</p>';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'User Display Name', 'paid-memberships-pro' ) . ': ' . $order_user->display_name . '</p>';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'Order', 'paid-memberships-pro' ) . ': ' . $this->get( 'code' ) . '</p>';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'Gateway', 'paid-memberships-pro' ) . ': ' . $this->get( 'gateway' ) . '</p>';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'Subscription Transaction ID', 'paid-memberships-pro' ) . ': ' . $this->get( 'subscription_transaction_id' ) . '</p>';
						$pmproemail->data['body'] .= '<hr />';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'Edit User', 'paid-memberships-pro' ) . ': ' .
													esc_url(
														add_query_arg(
															'user_id',
															$this->get( 'user_id' ),
															self_admin_url( 'user-edit.php' )
														)
													) . '</p>';
						$pmproemail->data['body'] .= '<p>' . esc_attr__( 'Edit Order', 'paid-memberships-pro' ) . ': ' . esc_url(
							add_query_arg(
								array(
									'page'  => 'pmpro-orders',
									'order' => $this->get( 'id' ),
								),
								admin_url( 'admin.php' )
							)
						) . '</p>';
						$pmproemail->sendEmail( get_bloginfo( 'admin_email' ) );
					}
				} else {
					//Note: status would have been set to cancelled by the gateway class. So we don't have to update it here.

					//remove billing numbers in pmpro_memberships_users if the membership is still active
					$wpdb->update(
						$wpdb->pmpro_membership_users,
						array(
							'initial_payment' => 0,
							'billing_amount'  => 0,
							'cycle_number'    => 0,
						),
						array(
							'user_id'       => $this->get( 'user_id' ),
							'membership_id' => $this->get( 'membership_id' ),
							'status'        => 'active',
						),
						array( '%d', '%d', '%d' ),
						array( '%d', '%d', '%s' )
					);
				}

				return $result;
			}
		}

		/**
		 * Call the update method of the gateway class.
		 *
		 * @return bool
		 *
		 * @access public
		 */
		public function updateBilling() {
			if ( is_object( $this->data->Gateway ) ) {
				return $this->data->Gateway->update( $this );
			}

			return false;
		}

		/**
		 * Call the getSubscriptionStatus method of the gateway class.
		 *
		 * @return string|bool
		 *
		 * @access public
		 */
		public function getGatewaySubscriptionStatus() {
			if ( is_object( $this->data->Gateway ) ) {
				return $this->data->Gateway->getSubscriptionStatus( $this );
			}

			return false;
		}

		/**
		 * Call the getTransactionStatus method of the gateway class.
		 *
		 * @return bool|string
		 *
		 * @access public
		 */
		public function getGatewayTransactionStatus() {
			if ( is_object( $this->data->Gateway ) ) {
				return $this->data->Gateway->getTransactionStatus( $this );
			}

			return false;
		}

		/**
		 * Get TOS consent information.
		 * @return bool|array
		 * @since  1.9.5
		 *
		 * @access public
		 */
		function get_tos_consent_log_entry() {
			$id = $this->get( 'id' );
			if ( empty( $id ) || ! function_exists( 'pmpro_get_consent_log' ) ) {
				return false;
			}

			$consent_log = pmpro_get_consent_log( $this->get( 'user_id' ) );
			foreach ( $consent_log as $entry ) {
				if ( $entry['order_id'] === $id ) {
					return $entry;
				}
			}

			return false;
		}

		/**
		 * Sets the billing address fields on the order object.
		 * Checks the last order for the same sub or pulls from user meta.
		 * @return void
		 * @since 2.5.5
		 *
		 * @access public
		 */
		function find_billing_address() {

			if ( empty( $this->data->billing ) || empty( $this->data->billing->street ) ) {
				// We do not already have a billing address.
				$last_subscription_order = new MemberOrder();
				$last_subscription_order->getLastMemberOrderBySubscriptionTransactionID( $this->get( 'subscription_transaction_id' ) );
				$this->setDefaultBilling();
				$user = $this->getUserObject( $this->get( 'user_id' ) );

				// FIXME: Refactor to avoid what's essentially code duplication
				if ( ! empty( $last_subscription_order->billing ) && ! empty( $last_subscription_order->billing->street ) ) {
					// Last order in subscription has billing information. Pull the data from there.
					$this->set( 'Address1', $last_subscription_order->billing->street );
					$this->set( 'City', $last_subscription_order->billing->city );
					$this->set( 'State', $last_subscription_order->billing->state );
					$this->set( 'Zip', $last_subscription_order->billing->zip );
					$this->set( 'CountryCode', $last_subscription_order->billing->country );
					$this->set( 'PhoneNumber', $last_subscription_order->billing->phone );
					$this->set( 'Email', $user->user_email );

					$this->set( 'billing_name', $last_subscription_order->billing->name );
					$this->set( 'billing_street', $last_subscription_order->billing->street );
					$this->set( 'billing_city', $last_subscription_order->billing->city );
					$this->set( 'billing_state', $last_subscription_order->billing->state );
					$this->set( 'billing_zip', $last_subscription_order->billing->zip );
					$this->set( 'billing_country', $last_subscription_order->billing->country );
					$this->set( 'billing_phone', $last_subscription_order->billing->phone );
				} else {
					// Last order did not have billing information. Try to pull from usermeta.
					$this->set( 'FirstName', get_user_meta( $user->ID, 'pmpro_bfirstname', true ) );
					$this->set( 'LastName', get_user_meta( $user->ID, 'pmpro_blastname', true ) );
					$this->set( 'Address1', get_user_meta( $user->ID, 'pmpro_baddress1', true ) );
					$this->set( 'City', get_user_meta( $user->ID, 'pmpro_bcity', true ) );
					$this->set( 'State', get_user_meta( $user->ID, 'pmpro_bstate', true ) );
					$this->set( 'Zip', get_user_meta( $user->ID, 'pmpro_bzip', true ) );
					$this->set( 'CountryCode', get_user_meta( $user->ID, 'pmpro_bcountry', true ) );
					$this->set( 'PhoneNumber', get_user_meta( $user->ID, 'pmpro_bphone', true ) );
					$this->set( 'Email', $user->user_email );

					$this->set(
						'billing_name',
						sprintf(
							'%1$s %2$s',
							get_user_meta( $user->ID, 'pmpro_bfirstname', true ),
							get_user_meta( $user->ID, 'pmpro_blastname', true )
						)
					);
					$this->set( 'billing_street', $this->get( 'Address1' ) );
					$this->set( 'billing_city', $this->get( 'City' ) );
					$this->set( 'billing_state', $this->get( 'State' ) );
					$this->set( 'billing_zip', $this->get( 'Zip' ) );
					$this->set( 'billing_country', $this->get( 'CountryCode' ) );
					$this->set( 'billing_phone', $this->get( 'PhoneNumber' ) );
				}
			}
		}

		/**
		 * Delete an order and associated data.
		 *
		 * @return bool
		 *
		 * @access public
		 */
		public function deleteMe() {
			if ( empty( $this->data->id ) ) {
				return false;
			}

			global $wpdb;

			if ( empty( $wpdb ) ) {
				return false;
			}

			// "DELETE FROM $wpdb->pmpro_membership_orders WHERE id = '" . $this->id . "' LIMIT 1"
			$result = $wpdb->delete(
				$wpdb->pmpro_membership_orders,
				array( 'id' => $this->get( 'id' ) ),
				array( '%d' )
			);
			if ( false !== $result ) {
				do_action( 'pmpro_delete_order', $this->data->id, $this );

				return true;
			} else {
				return false;
			}
		}

		/**
		 * Generates a test order on the fly for orders.
		 *
		 * @return mixed|void|null|MemberOrder
		 *
		 * @access public
		 */
		public function get_test_order( $user = null ) {
			global $current_user;

			if ( null === $user && ! empty( $current_user ) ) {
				$user = $current_user;
			}

			if ( function_exists( 'pmpro_getAllLevels' ) ) {
				$all_levels = pmpro_getAllLevels();
			} else {
				$fake_level                  = new stdClass();
				$fake_level->id              = 1;
				$fake_level->initial_payment = 100.00;
				$fake_level->billing_amount  = 0.00;
				$fake_level->name            = 'Default membership level';
				$fake_level->description     = 'This is a mocked membership level for test purposes only!';

				// Build a fake membership level array
				$all_levels = array(
					$fake_level->id => $fake_level,
				);
			}

			if ( ! empty( $all_levels ) ) {
				$first_level          = array_shift( $all_levels );
				$this->membership_id  = $first_level->id;
				$this->InitialPayment = $first_level->initial_payment;
			}

			$this->user_id             = $user->ID;
			$this->cardtype            = 'Visa';
			$this->accountnumber       = '4111111111111111';
			$this->expirationmonth     = date( 'm', time() );
			$this->expirationyear      = ( intval( date( 'Y', time() ) ) + 1 );
			$this->ExpirationDate      = $this->expirationmonth . $this->expirationyear;
			$this->CVV2                = '123';
			$this->FirstName           = 'Jane';
			$this->LastName            = 'Doe';
			$this->Address1            = '123 Street';
			$this->billing             = new stdClass();
			$this->billing->name       = 'Jane Doe';
			$this->billing->street     = '123 Street';
			$this->billing->city       = 'City';
			$this->billing->state      = 'ST';
			$this->billing->country    = 'US';
			$this->billing->zip        = '12345';
			$this->billing->phone      = '5558675309';
			$this->gateway_environment = 'sandbox';
			$this->timestamp           = time();
			$this->notes               = esc_attr__( 'This is a test order used with the PMPro Email Templates addon.', 'paid-memberships-pro' );

			return apply_filters( 'pmpro_test_order_data', $this );
		}
	} // End of Class
}
