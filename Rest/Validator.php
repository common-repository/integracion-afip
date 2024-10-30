<?php

/*
 * This file is part of the webmozart/assert package v1.9.1
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CRPlugins\Afip\Rest;

use ArrayAccess;
use Closure;
use Countable;
use DateTime;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use ResourceBundle;
use SimpleXMLElement;
use Throwable;
use Traversable;

defined( 'ABSPATH' ) || exit;

/**
 * Efficient assertions to validate the input/output of your methods.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Validator {

	/**
	 * @psalm-pure
	 * @psalm-assert string $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function string( $value, $message = '' ) {
		if ( ! \is_string( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a string. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert non-empty-string $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function string_not_empty( $value, $message = '' ) {
		static::string( $value, $message );
		static::not_eq( $value, '', $message );
	}

	/**
	 * @psalm-pure
	 * @psalm-assert int $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function integer( $value, $message = '' ) {
		if ( ! \is_int( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an integer. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert numeric $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function integerish( $value, $message = '' ) {
		if ( ! \is_numeric( $value ) || $value != (int) $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an integerish value. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert float $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function float( $value, $message = '' ) {
		if ( ! \is_float( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a float. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert numeric $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function numeric( $value, $message = '' ) {
		if ( ! \is_numeric( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a numeric. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert int $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function natural( $value, $message = '' ) {
		if ( ! \is_int( $value ) || $value < 0 ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a non-negative integer. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert bool $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function boolean( $value, $message = '' ) {
		if ( ! \is_bool( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a boolean. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert scalar $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function scalar( $value, $message = '' ) {
		if ( ! \is_scalar( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a scalar. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert object $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function object( $value, $message = '' ) {
		if ( ! \is_object( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an object. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert resource $value
	 *
	 * @param mixed       $value
	 * @param string|null $type    type of resource this should be. @see https://www.php.net/manual/en/function.get-resource-type.php
	 * @param string      $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function resource( $value, $type = null, $message = '' ) {
		if ( ! \is_resource( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a resource. Got: %s',
					static::type_to_string( $value )
				)
			);
		}

		if ( $type && $type !== \get_resource_type( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a resource of type %2$s. Got: %s',
					static::type_to_string( $value ),
					$type
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert callable $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_callable( $value, $message = '' ) {
		if ( ! \is_callable( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a callable. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert array $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_array( $value, $message = '' ) {
		if ( ! \is_array( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an array. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert iterable $value
	 *
	 * @deprecated use "isIterable" or "isInstanceOf" instead
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_traversable( $value, $message = '' ) {
		@\trigger_error(
			\sprintf(
				'The "%s" assertion is deprecated. You should stop using it, as it will soon be removed in 2.0 version. Use "isIterable" or "isInstanceOf" instead.',
				__METHOD__
			),
			\E_USER_DEPRECATED
		);

		if ( ! \is_array( $value ) && ! ( $value instanceof Traversable ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a traversable. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert array|ArrayAccess $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_array_accessible( $value, $message = '' ) {
		if ( ! \is_array( $value ) && ! ( $value instanceof ArrayAccess ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an array accessible. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert countable $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_countable( $value, $message = '' ) {
		if (
			! \is_array( $value )
			&& ! ( $value instanceof Countable )
			&& ! ( $value instanceof ResourceBundle )
			&& ! ( $value instanceof SimpleXMLElement )
		) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a countable. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert iterable $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_iterable( $value, $message = '' ) {
		if ( ! \is_array( $value ) && ! ( $value instanceof Traversable ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an iterable. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-template ExpectedType of object
	 * @psalm-param class-string<ExpectedType> $class
	 * @psalm-assert ExpectedType $value
	 *
	 * @param mixed         $value
	 * @param string|object $class
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_instance_of( $value, $class, $message = '' ) {
		if ( ! ( $value instanceof $class ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an instance of %2$s. Got: %s',
					static::type_to_string( $value ),
					$class
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-template ExpectedType of object
	 * @psalm-param class-string<ExpectedType> $class
	 * @psalm-assert !ExpectedType $value
	 *
	 * @param mixed         $value
	 * @param string|object $class
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_instance_of( $value, $class, $message = '' ) {
		if ( $value instanceof $class ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an instance other than %2$s. Got: %s',
					static::type_to_string( $value ),
					$class
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-param array<class-string> $classes
	 *
	 * @param mixed                $value
	 * @param array<object|string> $classes
	 * @param string               $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_instance_of_any( $value, array $classes, $message = '' ) {
		foreach ( $classes as $class ) {
			if ( $value instanceof $class ) {
				return;
			}
		}

		static::report_invalid_argument(
			\sprintf(
				$message ?: 'Expected an instance of any of %2$s. Got: %s',
				static::type_to_string( $value ),
				\implode( ', ', \array_map( array( 'static', 'value_to_string' ), $classes ) )
			)
		);
	}

	/**
	 * @psalm-pure
	 * @psalm-template ExpectedType of object
	 * @psalm-param class-string<ExpectedType> $class
	 * @psalm-assert ExpectedType|class-string<ExpectedType> $value
	 *
	 * @param object|string $value
	 * @param string        $class
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_a_of( $value, $class, $message = '' ) {
		static::string( $class, 'Expected class as a string. Got: %s' );

		if ( ! \is_a( $value, $class, \is_string( $value ) ) ) {
			static::report_invalid_argument(
				sprintf(
					$message ?: 'Expected an instance of this class or to this class among his parents %2$s. Got: %s',
					static::type_to_string( $value ),
					$class
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-template UnexpectedType of object
	 * @psalm-param class-string<UnexpectedType> $class
	 * @psalm-assert !UnexpectedType $value
	 * @psalm-assert !class-string<UnexpectedType> $value
	 *
	 * @param object|string $value
	 * @param string        $class
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_not_a( $value, $class, $message = '' ) {
		static::string( $class, 'Expected class as a string. Got: %s' );

		if ( \is_a( $value, $class, \is_string( $value ) ) ) {
			static::report_invalid_argument(
				sprintf(
					$message ?: 'Expected an instance of this class or to this class among his parents other than %2$s. Got: %s',
					static::type_to_string( $value ),
					$class
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-param array<class-string> $classes
	 *
	 * @param object|string $value
	 * @param string[]      $classes
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_any_of( $value, array $classes, $message = '' ) {
		foreach ( $classes as $class ) {
			static::string( $class, 'Expected class as a string. Got: %s' );

			if ( \is_a( $value, $class, \is_string( $value ) ) ) {
				return;
			}
		}

		static::report_invalid_argument(
			sprintf(
				$message ?: 'Expected an any of instance of this class or to this class among his parents other than %2$s. Got: %s',
				static::type_to_string( $value ),
				\implode( ', ', \array_map( array( 'static', 'value_to_string' ), $classes ) )
			)
		);
	}

	/**
	 * @psalm-pure
	 * @psalm-assert empty $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_empty( $value, $message = '' ) {
		if ( ! empty( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an empty value. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert !empty $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_empty( $value, $message = '' ) {
		if ( empty( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a non-empty value. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert null $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function null( $value, $message = '' ) {
		if ( null !== $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected null. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert !null $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_null( $value, $message = '' ) {
		if ( null === $value ) {
			static::report_invalid_argument(
				$message ?: 'Expected a value other than null.'
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert true $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function true( $value, $message = '' ) {
		if ( true !== $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to be true. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert false $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function false( $value, $message = '' ) {
		if ( false !== $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to be false. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert !false $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_false( $value, $message = '' ) {
		if ( false === $value ) {
			static::report_invalid_argument(
				$message ?: 'Expected a value other than false.'
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function ip( $value, $message = '' ) {
		if ( false === \filter_var( $value, \FILTER_VALIDATE_IP ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to be an IP. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function ipv4( $value, $message = '' ) {
		if ( false === \filter_var( $value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to be an IPv4. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function ipv6( $value, $message = '' ) {
		if ( false === \filter_var( $value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6 ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to be an IPv6. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function email( $value, $message = '' ) {
		if ( false === \filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to be a valid e-mail address. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * Does non strict comparisons on the items, so ['3', 3] will not pass the assertion.
	 *
	 * @param array  $values
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function unique_values( array $values, $message = '' ) {
		$allValues    = \count( $values );
		$uniqueValues = \count( \array_unique( $values ) );

		if ( $allValues !== $uniqueValues ) {
			$difference = $allValues - $uniqueValues;

			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an array of unique values, but %s of them %s duplicated',
					$difference,
					( 1 === $difference ? 'is' : 'are' )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param mixed  $expect
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function eq( $value, $expect, $message = '' ) {
		if ( $expect != $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value equal to %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $expect )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param mixed  $expect
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_eq( $value, $expect, $message = '' ) {
		if ( $expect == $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a different value than %s.',
					static::value_to_string( $expect )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $expect
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function same( $value, $expect, $message = '' ) {
		if ( $expect !== $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value identical to %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $expect )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $expect
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_same( $value, $expect, $message = '' ) {
		if ( $expect === $value ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value not identical to %s.',
					static::value_to_string( $expect )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $limit
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function greater_than( $value, $limit, $message = '' ) {
		if ( $value <= $limit ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value greater than %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $limit )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $limit
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function greater_than_eq( $value, $limit, $message = '' ) {
		if ( $value < $limit ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value greater than or equal to %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $limit )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $limit
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function less_than( $value, $limit, $message = '' ) {
		if ( $value >= $limit ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value less than %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $limit )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $limit
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function less_than_eq( $value, $limit, $message = '' ) {
		if ( $value > $limit ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value less than or equal to %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $limit )
				)
			);
		}
	}

	/**
	 * Inclusive range, so Assert::(3, 3, 5) passes.
	 *
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param mixed  $min
	 * @param mixed  $max
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function range( $value, $min, $max, $message = '' ) {
		if ( $value < $min || $value > $max ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value between %2$s and %3$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $min ),
					static::value_to_string( $max )
				)
			);
		}
	}

	/**
	 * A more human-readable alias of Assert::in_array().
	 *
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param array  $values
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function one_of( $value, array $values, $message = '' ) {
		static::in_array( $value, $values, $message );
	}

	/**
	 * Does strict comparison, so Assert::in_array(3, ['3']) does not pass the assertion.
	 *
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param array  $values
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function in_array( $value, array $values, $message = '' ) {
		if ( ! \in_array( $value, $values, true ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected one of: %2$s. Got: %s',
					static::value_to_string( $value ),
					\implode( ', ', \array_map( array( 'static', 'value_to_string' ), $values ) )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $subString
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function contains( $value, $subString, $message = '' ) {
		if ( false === \strpos( $value, $subString ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $subString )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $subString
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_contains( $value, $subString, $message = '' ) {
		if ( false !== \strpos( $value, $subString ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: '%2$s was not expected to be contained in a value. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $subString )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_whitespace_only( $value, $message = '' ) {
		if ( \preg_match( '/^\s*$/', $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a non-whitespace string. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $prefix
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function starts_with( $value, $prefix, $message = '' ) {
		if ( 0 !== \strpos( $value, $prefix ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to start with %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $prefix )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $prefix
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_starts_with( $value, $prefix, $message = '' ) {
		if ( 0 === \strpos( $value, $prefix ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value not to start with %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $prefix )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function starts_with_letter( $value, $message = '' ) {
		static::string( $value );

		$valid = isset( $value[0] );

		if ( $valid ) {
			$locale = \setlocale( LC_CTYPE, 0 );
			\setlocale( LC_CTYPE, 'C' );
			$valid = \ctype_alpha( $value[0] );
			\setlocale( LC_CTYPE, $locale );
		}

		if ( ! $valid ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to start with a letter. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $suffix
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function ends_with( $value, $suffix, $message = '' ) {
		if ( $suffix !== \substr( $value, -\strlen( $suffix ) ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to end with %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $suffix )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $suffix
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_ends_with( $value, $suffix, $message = '' ) {
		if ( $suffix === \substr( $value, -\strlen( $suffix ) ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value not to end with %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $suffix )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $pattern
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function regex( $value, $pattern, $message = '' ) {
		if ( ! \preg_match( $pattern, $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The value %s does not match the expected pattern.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $pattern
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function not_regex( $value, $pattern, $message = '' ) {
		if ( \preg_match( $pattern, $value, $matches, PREG_OFFSET_CAPTURE ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The value %s matches the pattern %s (at offset %d).',
					static::value_to_string( $value ),
					static::value_to_string( $pattern ),
					$matches[0][1]
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function unicode_letters( $value, $message = '' ) {
		static::string( $value );

		if ( ! \preg_match( '/^\p{L}+$/u', $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain only Unicode letters. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function alpha( $value, $message = '' ) {
		static::string( $value );

		$locale = \setlocale( LC_CTYPE, 0 );
		\setlocale( LC_CTYPE, 'C' );
		$valid = ! \ctype_alpha( $value );
		\setlocale( LC_CTYPE, $locale );

		if ( $valid ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain only letters. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function digits( $value, $message = '' ) {
		$locale = \setlocale( LC_CTYPE, 0 );
		\setlocale( LC_CTYPE, 'C' );
		$valid = ! \ctype_digit( $value );
		\setlocale( LC_CTYPE, $locale );

		if ( $valid ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain digits only. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function alnum( $value, $message = '' ) {
		$locale = \setlocale( LC_CTYPE, 0 );
		\setlocale( LC_CTYPE, 'C' );
		$valid = ! \ctype_alnum( $value );
		\setlocale( LC_CTYPE, $locale );

		if ( $valid ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain letters and digits only. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert lowercase-string $value
	 *
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function lower( $value, $message = '' ) {
		$locale = \setlocale( LC_CTYPE, 0 );
		\setlocale( LC_CTYPE, 'C' );
		$valid = ! \ctype_lower( $value );
		\setlocale( LC_CTYPE, $locale );

		if ( $valid ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain lowercase characters only. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert !lowercase-string $value
	 *
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function upper( $value, $message = '' ) {
		$locale = \setlocale( LC_CTYPE, 0 );
		\setlocale( LC_CTYPE, 'C' );
		$valid = ! \ctype_upper( $value );
		\setlocale( LC_CTYPE, $locale );

		if ( $valid ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain uppercase characters only. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param int    $length
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function length( $value, $length, $message = '' ) {
		if ( $length !== static::strlen( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain %2$s characters. Got: %s',
					static::value_to_string( $value ),
					$length
				)
			);
		}
	}

	/**
	 * Inclusive min.
	 *
	 * @psalm-pure
	 *
	 * @param string    $value
	 * @param int|float $min
	 * @param string    $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function min_length( $value, $min, $message = '' ) {
		if ( static::strlen( $value ) < $min ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain at least %2$s characters. Got: %s',
					static::value_to_string( $value ),
					$min
				)
			);
		}
	}

	/**
	 * Inclusive max.
	 *
	 * @psalm-pure
	 *
	 * @param string    $value
	 * @param int|float $max
	 * @param string    $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function max_length( $value, $max, $message = '' ) {
		if ( static::strlen( $value ) > $max ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain at most %2$s characters. Got: %s',
					static::value_to_string( $value ),
					$max
				)
			);
		}
	}

	/**
	 * Inclusive , so Assert::lengthBetween('asd', 3, 5); passes the assertion.
	 *
	 * @psalm-pure
	 *
	 * @param string    $value
	 * @param int|float $min
	 * @param int|float $max
	 * @param string    $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function length_between( $value, $min, $max, $message = '' ) {
		$length = static::strlen( $value );

		if ( $length < $min || $length > $max ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a value to contain between %2$s and %3$s characters. Got: %s',
					static::value_to_string( $value ),
					$min,
					$max
				)
			);
		}
	}

	/**
	 * Will also pass if $value is a directory, use Assert::file() instead if you need to be sure it is a file.
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function file_exists( $value, $message = '' ) {
		static::string( $value );

		if ( ! \file_exists( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The file %s does not exist.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function file( $value, $message = '' ) {
		static::file_exists( $value, $message );

		if ( ! \is_file( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The path %s is not a file.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function directory( $value, $message = '' ) {
		static::file_exists( $value, $message );

		if ( ! \is_dir( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The path %s is no directory.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function readable( $value, $message = '' ) {
		if ( ! \is_readable( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The path %s is not readable.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function writable( $value, $message = '' ) {
		if ( ! \is_writable( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'The path %s is not writable.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-assert class-string $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function class_exists( $value, $message = '' ) {
		if ( ! \class_exists( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an existing class name. Got: %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-template ExpectedType of object
	 * @psalm-param class-string<ExpectedType> $class
	 * @psalm-assert class-string<ExpectedType>|ExpectedType $value
	 *
	 * @param mixed         $value
	 * @param string|object $class
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function subclass_of( $value, $class, $message = '' ) {
		if ( ! \is_subclass_of( $value, $class ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected a sub-class of %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $class )
				)
			);
		}
	}

	/**
	 * @psalm-assert class-string $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function interface_exists( $value, $message = '' ) {
		if ( ! \interface_exists( $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an existing interface name. got %s',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-template ExpectedType of object
	 * @psalm-param class-string<ExpectedType> $interface
	 * @psalm-assert class-string<ExpectedType> $value
	 *
	 * @param mixed  $value
	 * @param mixed  $interface
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function implements_interface( $value, $interface, $message = '' ) {
		if ( ! \in_array( $interface, \class_implements( $value ) ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an implementation of %2$s. Got: %s',
					static::value_to_string( $value ),
					static::value_to_string( $interface )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-param class-string|object $classOrObject
	 *
	 * @param string|object $classOrObject
	 * @param mixed         $property
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function property_exists( $classOrObject, $property, $message = '' ) {
		if ( ! \property_exists( $classOrObject, $property ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected the property %s to exist.',
					static::value_to_string( $property )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-param class-string|object $classOrObject
	 *
	 * @param string|object $classOrObject
	 * @param mixed         $property
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function property_not_exists( $classOrObject, $property, $message = '' ) {
		if ( \property_exists( $classOrObject, $property ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected the property %s to not exist.',
					static::value_to_string( $property )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-param class-string|object $classOrObject
	 *
	 * @param string|object $classOrObject
	 * @param mixed         $method
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function method_exists( $classOrObject, $method, $message = '' ) {
		if ( ! ( \is_string( $classOrObject ) || \is_object( $classOrObject ) ) || ! \method_exists( $classOrObject, $method ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected the method %s to exist.',
					static::value_to_string( $method )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-param class-string|object $classOrObject
	 *
	 * @param string|object $classOrObject
	 * @param mixed         $method
	 * @param string        $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function method_not_exists( $classOrObject, $method, $message = '' ) {
		if ( ( \is_string( $classOrObject ) || \is_object( $classOrObject ) ) && \method_exists( $classOrObject, $method ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected the method %s to not exist.',
					static::value_to_string( $method )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param array      $array
	 * @param string|int $key
	 * @param string     $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function key_exists( $array, $key, $message = '' ) {
		if ( ! ( isset( $array[ $key ] ) || \array_key_exists( $key, $array ) ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected the key %s to exist.',
					static::value_to_string( $key )
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 *
	 * @param array      $array
	 * @param string|int $key
	 * @param string     $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function key_not_exists( $array, $key, $message = '' ) {
		if ( isset( $array[ $key ] ) || \array_key_exists( $key, $array ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected the key %s to not exist.',
					static::value_to_string( $key )
				)
			);
		}
	}

	/**
	 * Checks if a value is a valid array key (int or string).
	 *
	 * @psalm-pure
	 * @psalm-assert array-key $value
	 *
	 * @param mixed  $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function valid_array_key( $value, $message = '' ) {
		if ( ! ( \is_int( $value ) || \is_string( $value ) ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected string or integer. Got: %s',
					static::type_to_string( $value )
				)
			);
		}
	}

	/**
	 * Does not check if $array is countable, this can generate a warning on php versions after 7.2.
	 *
	 * @param Countable|array $array
	 * @param int             $number
	 * @param string          $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function count( $array, $number, $message = '' ) {
		static::eq(
			\count( $array ),
			$number,
			\sprintf(
				$message ?: 'Expected an array to contain %d elements. Got: %d.',
				$number,
				\count( $array )
			)
		);
	}

	/**
	 * Does not check if $array is countable, this can generate a warning on php versions after 7.2.
	 *
	 * @param Countable|array $array
	 * @param int|float       $min
	 * @param string          $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function min_count( $array, $min, $message = '' ) {
		if ( \count( $array ) < $min ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an array to contain at least %2$d elements. Got: %d',
					\count( $array ),
					$min
				)
			);
		}
	}

	/**
	 * Does not check if $array is countable, this can generate a warning on php versions after 7.2.
	 *
	 * @param Countable|array $array
	 * @param int|float       $max
	 * @param string          $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function max_count( $array, $max, $message = '' ) {
		if ( \count( $array ) > $max ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an array to contain at most %2$d elements. Got: %d',
					\count( $array ),
					$max
				)
			);
		}
	}

	/**
	 * Does not check if $array is countable, this can generate a warning on php versions after 7.2.
	 *
	 * @param Countable|array $array
	 * @param int|float       $min
	 * @param int|float       $max
	 * @param string          $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function count_between( $array, $min, $max, $message = '' ) {
		$count = \count( $array );

		if ( $count < $min || $count > $max ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Expected an array to contain between %2$d and %3$d elements. Got: %d',
					$count,
					$min,
					$max
				)
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert list $array
	 *
	 * @param mixed  $array
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_list( $array, $message = '' ) {
		if ( ! \is_array( $array ) || $array !== \array_values( $array ) ) {
			static::report_invalid_argument(
				$message ?: 'Expected list - non-associative array.'
			);
		}
	}

	/**
	 * @psalm-pure
	 * @psalm-assert non-empty-list $array
	 *
	 * @param mixed  $array
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_non_empty_list( $array, $message = '' ) {
		static::is_list( $array, $message );
		static::not_empty( $array, $message );
	}

	/**
	 * @param mixed  $array
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_map( $array, $message = '' ) {
		if (
			! \is_array( $array ) ||
			\array_keys( $array ) !== \array_filter( \array_keys( $array ), '\is_string' )
		) {
			static::report_invalid_argument(
				$message ?: 'Expected map - associative array with string keys.'
			);
		}
	}

	/**
	 * @param mixed  $array
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function is_non_empty_map( $array, $message = '' ) {
		static::is_map( $array, $message );
		static::not_empty( $array, $message );
	}

	/**
	 * @psalm-pure
	 *
	 * @param string $value
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function uuid( $value, $message = '' ) {
		$value = \str_replace( array( 'urn:', 'uuid:', '{', '}' ), '', $value );

		// The nil UUID is special form of UUID that is specified to have all
		// 128 bits set to zero.
		if ( '00000000-0000-0000-0000-000000000000' === $value ) {
			return;
		}

		if ( ! \preg_match( '/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/', $value ) ) {
			static::report_invalid_argument(
				\sprintf(
					$message ?: 'Value %s is not a valid UUID.',
					static::value_to_string( $value )
				)
			);
		}
	}

	/**
	 * @psalm-param class-string<Throwable> $class
	 *
	 * @param Closure $expression
	 * @param string  $class
	 * @param string  $message
	 *
	 * @throws InvalidArgumentException
	 */
	public static function throws( Closure $expression, $class = 'Exception', $message = '' ) {
		static::string( $class );

		$actual = 'none';

		try {
			$expression();
		} catch ( Exception $e ) {
			$actual = \get_class( $e );
			if ( $e instanceof $class ) {
				return;
			}
		} catch ( Throwable $e ) {
			$actual = \get_class( $e );
			if ( $e instanceof $class ) {
				return;
			}
		}

		static::report_invalid_argument(
			$message ?: \sprintf(
				'Expected to throw "%s", got "%s"',
				$class,
				$actual
			)
		);
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected static function value_to_string( $value ) {
		if ( null === $value ) {
			return 'null';
		}

		if ( true === $value ) {
			return 'true';
		}

		if ( false === $value ) {
			return 'false';
		}

		if ( \is_array( $value ) ) {
			return 'array';
		}

		if ( \is_object( $value ) ) {
			if ( \method_exists( $value, '__toString' ) ) {
				return \get_class( $value ) . ': ' . self::value_to_string( $value->__toString() );
			}

			if ( $value instanceof DateTime || $value instanceof DateTimeImmutable ) {
				return \get_class( $value ) . ': ' . self::value_to_string( $value->format( 'c' ) );
			}

			return \get_class( $value );
		}

		if ( \is_resource( $value ) ) {
			return 'resource';
		}

		if ( \is_string( $value ) ) {
			return '"' . $value . '"';
		}

		return (string) $value;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected static function type_to_string( $value ) {
		return \is_object( $value ) ? \get_class( $value ) : \gettype( $value );
	}

	protected static function strlen( $value ) {
		if ( ! \function_exists( 'mb_detect_encoding' ) ) {
			return \strlen( $value );
		}

		if ( false === $encoding = \mb_detect_encoding( $value ) ) {
			return \strlen( $value );
		}

		return \mb_strlen( $value, $encoding );
	}

	/**
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 *
	 * @psalm-pure this method is not supposed to perform side-effects
	 */
	protected static function report_invalid_argument( $message ) {
		throw new InvalidArgumentException( $message );
	}

	private function __construct() {
	}
}
