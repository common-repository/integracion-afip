<?php

namespace CRPlugins\Afip\ValueObjects;

class Address {
	/**
	 * @var string
	 */
	private $street;
	/**
	 * @var string
	 */
	private $number;
	/**
	 * @var string
	 */
	private $floor;
	/**
	 * @var string
	 */
	private $apartment;
	/**
	 * @var string
	 */
	private $postcode;
	/**
	 * @var string
	 */
	private $city;
	/**
	 * @var string
	 */
	private $state;
	/**
	 * @var string
	 */
	private $extra_info;
	/**
	 * @param string
	 */
	private $full_address;

	public function __construct(
		string $street,
		string $number,
		string $floor,
		string $apartment,
		string $postcode,
		string $city,
		string $state,
		string $extra_info = ''
	) {
		$this->street       = $street;
		$this->number       = $number;
		$this->floor        = $floor;
		$this->apartment    = $apartment;
		$this->postcode     = $postcode;
		$this->city         = $city;
		$this->state        = $state;
		$this->extra_info   = $extra_info;
		$this->full_address = '';
	}

	public function get_full_address(): string {
		if ( $this->full_address ) {
			return $this->full_address;
		}

		$full_address = $this->get_street();
		if ( ! empty( $this->get_number() ) ) {
			$full_address = sprintf( '%s %s', $full_address, $this->get_number() );
		}
		if ( ! empty( $this->get_floor() ) ) {
			$full_address = sprintf( '%s, %s', $full_address, $this->get_floor() );
			if ( ! empty( $this->get_apartment() ) ) {
				$full_address = sprintf( '%s %s', $full_address, $this->get_apartment() );
			}
		}

		$result = '';

		if ( $full_address ) {
			$result .= sprintf( '%s. ', $full_address );
		}

		if ( $this->get_city() ) {
			$result .= sprintf( '%s ', $this->get_city() );
		}

		if ( $this->get_postcode() ) {
			$result .= sprintf( '%s, ', $this->get_postcode() );
		}

		if ( $this->get_state() ) {
			$result .= sprintf( '%s', $this->get_state() );
		}

		return trim( $result );
	}

	public function set_full_address( string $full_address ): void {
		$this->full_address = $full_address;
	}

	public function get_street(): string {
		return $this->street;
	}

	public function get_number(): string {
		return $this->number;
	}

	public function get_floor(): string {
		return $this->floor;
	}

	public function get_apartment(): string {
		return $this->apartment;
	}

	public function has_postcode(): bool {
		return '' !== $this->get_postcode();
	}

	public function get_postcode(): string {
		return $this->postcode;
	}

	public function get_city(): string {
		return $this->city;
	}

	public function get_state(): string {
		return $this->state;
	}

	public function get_extra_info(): string {
		return $this->extra_info;
	}
}
