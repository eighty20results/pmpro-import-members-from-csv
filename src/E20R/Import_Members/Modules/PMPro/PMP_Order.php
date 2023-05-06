<?php

namespace E20R\Import\src\E20R\Import_Members\Modules\PMPro;

use MemberOrder;
use stdClass;

/**
 * Wrapper/Extender of the PMPro MemberOrder class
 */
class PMP_Order extends MemberOrder {

	/**
	 * The PMPro Member Order object we're extending (wrapping)
	 *
	 * @var null|MemberOrder
	 */
	private $pmp_order = null;

	/**
	 * Pass-through getter for the PMPro MemberObject parameter
	 *
	 * @param string $param
	 *
	 * @return false|int|MemberOrder|mixed|stdClass|string|void|null
	 *
	 * @uses MemberOrder::__get()
	 */
	public function get( $param = null ) {

		if ( is_null( $param ) ) {
			return $this->pmp_order;
		}

		return $this->pmp_order->{$param};
	}

	/**
	 * Pass-through setter for the PMPro MemberObject parameter
	 *
	 * @param string $param The MemberOrder class parameter to set
	 * @param mixed $value The value to set the $param to
	 *
	 * @return void
	 * @uses MemberOrder::__set()
	 */
	public function set( $param = null, $value ) {
		// Assuming tje
		if ( is_null( $param ) && is_a( $value, \MemberOrder::class ) ) {
			$this->pmp_order = $value;
			return;
		}
		$this->pmp_order->{$param} = $value;
	}
}
