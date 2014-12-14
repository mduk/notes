<?php

namespace Mduk;

class User implements Identity {
	public $user_id;
	public $name;
	public $email;
	public $role;

	public $note;

	public function getIdentity() {
		return 'urn:user:' . $this->user_id;
	}
}
