<?php

namespace CRPlugins\Afip\ValueObjects;

class Item {
	/**
	 * @var float
	 */
	private $price;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int
	 */
	private $quantity;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $sku;

	/**
	 * @var float
	 */
	private $discount;

	/**
	 * @var string
	 */
	private $tax_class;

	/**
	 * @var ?int
	 */
	private $tax_type;

	public function __construct(
		float $price,
		string $name,
		int $quantity,
		int $id,
		string $sku,
		float $discount,
		string $tax_class
	) {
		$this->price     = $price;
		$this->name      = $name;
		$this->quantity  = $quantity;
		$this->id        = $id;
		$this->sku       = $sku;
		$this->discount  = $discount;
		$this->tax_class = '' === $tax_class ? 'default' : $tax_class;
		$this->tax_type  = null; // to fill later
	}

	public function set_price( float $price ): void {
		$this->price = $price;
	}

	public function set_discount( float $discount ): void {
		$this->discount = abs( $discount ); // WooCommerce returns discounts as negative
	}

	public function get_price(): float {
		return $this->price;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_quantity(): int {
		return $this->quantity;
	}

	public function get_id(): int {
		return $this->id;
	}

	public function get_sku(): string {
		return $this->sku;
	}

	public function get_discount(): float {
		return $this->discount;
	}

	public function get_tax_type(): ?int {
		return $this->tax_type;
	}

	public function set_tax_type( $tax_type ): void {
		if ( isset( $tax_type ) ) {
			$tax_type = (int) $tax_type;
		}

		$this->tax_type = $tax_type;
	}

	public function get_tax_class(): string {
		return $this->tax_class;
	}
}
