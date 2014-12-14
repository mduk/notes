<?php

namespace Mduk;

class IdentityStub implements Identity {
	protected $identity;

	public function __construct( $identity ) {
		$this->identity = $identity;
	}

	public function getIdentity() {
		return $this->identity;
	}
}
