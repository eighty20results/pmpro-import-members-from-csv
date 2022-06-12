<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 Luca Tumedei
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package E20R\Tests\Fixtures\Factory\E20R_IntegrationTest_Generator_Sequence
 */
namespace E20R\Tests\Fixtures\Factory;

use WP_Error;

abstract class E20R_IntegrationTest_Factory_For_Thing extends E20R_IntegrationTest_Factory {

	public $default_generation_definitions;
	public $factory;

	/**
	 * Creates a new factory, which will create objects of a specific Thing
	 *
	 * @param object $factory Global factory that can be used to create other objects on the system
	 * @param array $default_generation_definitions Defines what default values should the properties of the object have. The default values
	 * can be generators -- an object with next() method. There are some default generators: {@link E20R_IntegrationTest_Generator_Sequence},
	 * {@link WP_UnitTest_Generator_Locale_Name}, {@link E20R_IntegrationTest_Factory_Callback_After_Create}.
	 */
	public function __construct( $factory, $default_generation_definitions = array() ) {
		parent::__construct( $factory, $default_generation_definitions );
		$this->factory                        = $factory;
		$this->default_generation_definitions = $default_generation_definitions;
	}

	abstract public function create_object( $args );
	abstract public function update_object( $object, $fields );

	public function create( $args = array(), $generation_definitions = null ) {
		if ( is_null( $generation_definitions ) ) {
			$generation_definitions = $this->default_generation_definitions;
		}

		$generated_args = $this->generate_args( $args, $generation_definitions, $callbacks );
		$created        = $this->create_object( $generated_args );
		if ( ! $created || is_wp_error( $created ) ) {
			return $created;
		}

		if ( $callbacks ) {
			$updated_fields = $this->apply_callbacks( $callbacks, $created );
			$save_result    = $this->update_object( $created, $updated_fields );
			if ( ! $save_result || is_wp_error( $save_result ) ) {
				return $save_result;
			}
		}
		return $created;
	}

	public function create_and_get( $args = array(), $generation_definitions = null ) {
		$object_id = $this->create( $args, $generation_definitions );
		return $this->get_object_by_id( $object_id );
	}

	abstract public function get_object_by_id( $object_id );

	public function create_many( $count, $args = array(), $generation_definitions = null ) {
		$results = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$results[] = $this->create( $args, $generation_definitions );
		}
		return $results;
	}

	public function generate_args( $args = array(), $generation_definitions = null, &$callbacks = null ) {
		$callbacks = array();
		if ( is_null( $generation_definitions ) ) {
			$generation_definitions = $this->default_generation_definitions;
		}

		// Use the same incrementor for all fields belonging to this object.
		$gen  = new E20R_IntegrationTest_Generator_Sequence();
		$incr = $gen->get_incr();

		foreach ( array_keys( $generation_definitions ) as $field_name ) {
			if ( ! isset( $args[ $field_name ] ) ) {
				$generator = $generation_definitions[ $field_name ];
				if ( is_scalar( $generator ) ) {
					$args[ $field_name ] = $generator;
				} elseif ( is_object( $generator ) && method_exists( $generator, 'call' ) ) {
					$callbacks[ $field_name ] = $generator;
				} elseif ( is_object( $generator ) ) {
					$args[ $field_name ] = sprintf( $generator->get_template_string(), $incr );
				} else {
					return new WP_Error( 'invalid_argument', 'Factory default value should be either a scalar or an generator object.' );
				}
			}
		}

		return $args;
	}

	public function apply_callbacks( $callbacks, $created ) {
		$updated_fields = array();
		foreach ( $callbacks as $field_name => $generator ) {
			$updated_fields[ $field_name ] = $generator->call( $created );
		}
		return $updated_fields;
	}

	public function callback( $function ) {
		return new E20R_IntegrationTest_Factory_Callback_After_Create( $function );
	}

	public function addslashes_deep( $value ) {
		if ( is_array( $value ) ) {
			$value = array_map( array( $this, 'addslashes_deep' ), $value );
		} elseif ( is_object( $value ) ) {
			$vars = get_object_vars( $value );
			foreach ( $vars as $key => $data ) {
				$value->{$key} = $this->addslashes_deep( $data );
			}
		} elseif ( is_string( $value ) ) {
			$value = addslashes( $value );
		}

		return $value;
	}
}
