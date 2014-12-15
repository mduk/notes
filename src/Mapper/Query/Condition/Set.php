<?php

namespace Mduk\Mapper\Query\Condition;

use Mduk\Mapper\Query\Condition;

class Set {

	protected $query;
	protected $conditions = array();

	public function __construct( $query ) {
		$this->query = $query;
	}

	public function field( $field ) {
		$condition = new Condition( $this, $field );
		$this->conditions[] = $condition;
		return $condition;
	}

	public function done() {
		return $this->query;
	}

	public function __toString() {
		$sql = '( ';
		foreach ( $this->conditions as $condition ) {
			$sql .= $condition;
		}
		return $sql . ' )';
	}

}

