<?php

class MapperFactory {
	protected $mappers = array();
	protected $pdo;
	protected $identityMap;

	public function __construct( $pdo, $identityMap = null ) {
		$this->pdo = $pdo;
		$this->identityMap = $identityMap;
	}

	public function get( $class ) {
		if ( !isset( $this->mappers[ $class ] ) ) {
			$this->mappers[ $class ] = new $class( $this, $this->pdo, $this->identityMap );
		}

		return $this->mappers[ $class ];
	}
}
