<?php

namespace CRPlugins\Afip\ValueObjects;

class Invoice {

	/** @var string */
	private $type;

	/** @var string */
	private $term;

	/** @var string */
	private $unit;

	/** @var string */
	private $product_type;

	/** @var string */
	private $tax_type;

	public function __construct(
		string $type,
		string $term,
		string $unit,
		string $product_type,
		string $tax_type
	) {
		$this->type         = $type;
		$this->term         = $term;
		$this->unit         = $unit;
		$this->product_type = $product_type;
		$this->tax_type     = $tax_type;
	}

	public function get_type(): int {
		return $this->type;
	}

	public function get_term() {
		return $this->term;
	}

	public function get_unit() {
		return $this->unit;
	}

	public function get_product_type() {
		return $this->product_type;
	}

	public function get_tax_type() {
		return $this->tax_type;
	}

	public function set_type( string $type ): void {
		$this->type = $type;
	}

	public function set_term( string $term ): void {
		$this->term = $term;
	}

	public function set_unit( string $unit ): void {
		$this->unit = $unit;
	}

	public function set_product_type( string $product_type ): void {
		$this->product_type = $product_type;
	}

	public function set_tax_type( string $tax_type ): void {
		$this->tax_type = $tax_type;
	}
}
