<?php

namespace CRPlugins\Afip\Container;

use Exception;

use function array_key_exists;
use function gettype;
use function is_callable;
use function sprintf;

/**
 * @see https://github.com/PHPWatch/simple-container
 */
class Container implements ContainerInterface {
	/**
	 * @var ?self
	 */
	protected static $instance = null;
	/**
	 * @var array<string,callable|string>
	 */
	private $definitions = array();
	/**
	 * @var array<string,mixed>
	 */
	private $generated = array();
	/**
	 * @var array<string,bool>
	 */
	private $protected = array();
	/**
	 * @var array<string,bool>
	 */
	private $factories = array();

	private function __construct() {
	}

	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return mixed
	 */
	private function getService( string $id ) {
		if ( ! $this->has( $id ) ) {
			throw new Exception( esc_html( sprintf( 'Container key "%s" is not defined', $id ) ) );
		}

		if ( array_key_exists( $id, $this->generated ) ) {
			return $this->generated[ $id ];
		}

		if ( ! is_callable( $this->definitions[ $id ] ) || isset( $this->protected[ $id ] ) ) {
			return $this->definitions[ $id ];
		}

		if ( isset( $this->factories[ $id ] ) ) {
			return $this->definitions[ $id ]( $this );
		}

		$this->generated[ $id ] = $this->definitions[ $id ]( $this );

		return $this->generated[ $id ];
	}

	public function set( string $id, callable $value ): void {
		if ( array_key_exists( $id, $this->definitions ) ) {
			unset( $this->generated[ $id ], $this->factories[ $id ], $this->protected[ $id ] );
		}
		$this->definitions[ $id ] = $value;
	}

	public function setProtected( string $id, ?callable $value = null ): void {
		if ( null === $value ) {
			$value = $this->getDefaultDefinition( $id, sprintf( 'Attempt to set container ID "%s" as protected, but it is not already set nor provided in the function call.', $id ) );
		}

		$this->set( $id, $value );
		$this->protected[ $id ] = true;
	}

	public function setFactory( string $id, ?callable $value = null ): void {
		if ( null === $value ) {
			$value = $this->getDefaultDefinition( $id, sprintf( 'Attempt to set container ID "%s" as factory, but it is not already set nor provided in the function call', $id ) );
		}

		$this->set( $id, $value );
		$this->factories[ $id ] = true;
	}

	private function getDefaultDefinition( string $id, string $exception_message ): callable {
		if ( ! $this->has( $id ) ) {
			throw new Exception( esc_html( $exception_message ) );
		}
		if ( ! is_callable( $this->definitions[ $id ] ) ) {
			throw new Exception(
				sprintf(
					'Definition for "%s" expected to be a callable, "%s" found',
					esc_html( $id ),
					esc_html( gettype( $this->definitions[ $id ] ) )
				)
			);
		}

		return $this->definitions[ $id ];
	}

	/**
	 * @param string $offset
	 * @param callable $value
	 */
	public function offsetSet( $offset, $value ): void {
		$this->set( $offset, $value );
	}

	/**
	 * @param string $offset
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->definitions[ $offset ], $this->generated[ $offset ], $this->factories[ $offset ], $this->protected[ $offset ] );
	}

	/**
	 * @param string $offset
	 */
	public function offsetExists( $offset ): bool {
		return array_key_exists( $offset, $this->definitions );
	}

	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->getService( $offset );
	}

	/**
	 * @inheritDoc
	 */
	public function get( string $id ) {
		return $this->getService( $id );
	}

	/**
	 * @inheritDoc
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->definitions );
	}
}
