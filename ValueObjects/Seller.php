<?php

namespace CRPlugins\Afip\ValueObjects;

class Seller {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $fantasy_name;

	/**
	 * @var string
	 */
	private $cuit;

	/**
	 * @var int
	 */
	private $point_of_sale;

	/**
	 * @var string
	 */
	private $condition;

	/**
	 * @var string
	 */
	private $tax_condition;

	/**
	 * @var string
	 */
	private $start_date;

	/**
	 * @var string
	 */
	private $address;

	public function __construct(
		string $name,
		string $fantasy_name,
		string $cuit,
		string $point_of_sale,
		string $condition,
		string $tax_condition,
		string $start_date,
		string $address
	) {
		$this->name          = $name;
		$this->fantasy_name  = $fantasy_name;
		$this->cuit          = $cuit;
		$this->point_of_sale = $point_of_sale;
		$this->condition     = $condition;
		$this->tax_condition = $tax_condition;
		$this->start_date    = $start_date;
		$this->address       = $address;
	}

	public function get_address(): string {
		return $this->address;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_fantasy_name(): string {
		return $this->fantasy_name;
	}

	public function get_cuit(): string {
		return $this->cuit;
	}

	public function get_point_of_sale(): int {
		return $this->point_of_sale;
	}

	public function get_condition(): string {
		return $this->condition;
	}

	public function get_tax_condition(): string {
		return $this->tax_condition;
	}

	public function get_start_date(): string {
		return $this->start_date;
	}
}
