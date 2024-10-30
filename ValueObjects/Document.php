<?php

namespace CRPlugins\Afip\ValueObjects;

class Document {

	/** @var int */
	private $type;

	/** @var string */
	private $number;

	public function __construct( int $type, string $number ) {
		$this->type   = $type;
		$this->number = $number;
	}

	public function get_type(): int {
		return $this->type;
	}

	public function get_number(): string {
		return $this->number;
	}
}
