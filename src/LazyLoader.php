<?php

namespace Mduk;

class LazyLoader {
	protected $callback;
	protected $arguments;
	
	public function __construct( $callback, $arguments ) {
		$this->callback = $callback;
		$this->arguments = $arguments;
	}
	
	public function load() {
		return call_user_func_array( $this->callback, $this->arguments );
	}
}
