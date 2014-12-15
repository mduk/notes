<?php

namespace Mduk\Mapper\Query;

class Condition {

	protected $conditionSet;
	protected $field;
	protected $operator;
	protected $value;

	public function __construct( $conditionSet, $field ) {
		$this->conditionSet = $conditionSet;
		$this->field = $field;
	}

	public function is( $value ) {
		$this->operator = '=';
		$this->value = $value;
		return $this->conditionSet;
	}

	public function getField() {
		return $this->field;
	}

	public function getOperator() {
		return $this->operator;
	}

	public function getValue() {
		return $value;
	}

	public function __toString() {
		return "`{$this->field}` {$this->operator} \"{$this->value}\"";
	}
}
