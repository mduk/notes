<?php

namespace Mduk\Identity;

use Mduk\Identity;

class Stub implements Identity {
	protected $identity;

	public function __construct( $identity ) {
		$this->identity = $identity;
	}

	public function getIdentity() {
		return $this->identity;
	}
}
