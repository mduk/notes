<?php

namespace Mduk\Identity\Map;

use Mduk\Identity;
use Mduk\Identity\Map as IdentityMap;

class Memory extends \ArrayObject implements IdentityMap {
	public function has( Identity $object ) {
		return isset( $this[ $object->getIdentity() ] );
	}
	
	public function set( Identity $object ) {
		$this[ $object->getIdentity() ] = $object;
	}
	
	public function get( Identity $object ) {
		return $this[ $object->getIdentity() ];
	}
}
