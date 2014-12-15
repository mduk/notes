<?php

namespace Mduk\Repository;

class Factory {
	protected $repositories = array();
	protected $mapperFactory;

	public function __construct( $mapperFactory ) {
		$this->mapperFactory = $mapperFactory;
	}

	public function get( $class ) {
		if ( !isset( $this->repositories[ $class ] ) ) {
			$this->repositories[ $class ] = new $class( $this, $this->mapperFactory );
		}

		return $this->repositories[ $class ];
	}
}
