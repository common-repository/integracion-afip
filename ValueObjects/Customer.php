<?php

namespace CRPlugins\Afip\ValueObjects;

class Customer {

	/**
	 * @var string
	 */
	private $first_name;
	/**
	 * @var string
	 */
	private $last_name;
	/**
	 * @var string
	 */
	private $company_name;
	/**
	 * @var string
	 */
	private $condition;
	/**
	 * @var Address
	 */
	private $address;
	/**
	 * @var Document
	 */
	private $document;

	public function __construct(
		string $first_name,
		string $last_name,
		string $company_name,
		string $condition,
		Address $address,
		Document $document
	) {
		$this->first_name   = $first_name;
		$this->last_name    = $last_name;
		$this->company_name = $company_name;
		$this->address      = $address;
		$this->condition    = $condition;
		$this->document     = $document;
	}

	public function get_first_name(): string {
		return $this->first_name;
	}

	public function get_last_name(): string {
		return $this->last_name;
	}

	public function get_company_name(): string {
		return $this->company_name;
	}

	public function get_full_name(): string {
		return trim(
			sprintf(
				'%s %s',
				$this->get_first_name(),
				$this->get_last_name()
			)
		);
	}

	public function get_address(): Address {
		return $this->address;
	}

	public function get_document(): Document {
		return $this->document;
	}

	public function get_condition(): string {
		return $this->condition;
	}

	public function set_first_name( string $first_name ): void {
		$this->first_name = $first_name;
	}

	public function set_last_name( string $last_name ): void {
		$this->last_name = $last_name;
	}

	public function set_company_name( string $company_name ): void {
		$this->company_name = $company_name;
	}

	public function set_condition( string $condition ): void {
		$this->condition = $condition;
	}

	public function set_address( Address $address ): void {
		$this->address = $address;
	}

	public function set_document( Document $document ): void {
		$this->document = $document;
	}
}
