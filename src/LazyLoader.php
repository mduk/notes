<?php

namespace Mduk;

class LazyLoader {
	protected $closure;
	
	public function __construct( $closure ) {
		$this->closure = $closure;
	}
	
	public function load() {
		return $this->closure->__invoke();
	}
}
