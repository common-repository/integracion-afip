<?php

namespace CRPlugins\Afip\ValueObjects;

class Items {

	/** @var Item[] */
	private $items;

	/**
	 * @param Item[] $items
	 */
	public function __construct( array $items ) {
		$this->items = $items;
	}

	/**
	 * @return Item[]
	 */
	public function get(): array {
		return $this->items;
	}

	public function get_first(): Item {
		return $this->items[0];
	}

	public function add( Item $item ): void {
		$this->items[] = $item;
	}
}
