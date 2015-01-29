<?php

namespace Mduk;

class User extends Model implements Identity {
	public $user_id;
	public $name;
	public $email;
	public $role;

	public $note;

	public function getIdentity() {
		return 'urn:user:' . $this->user_id;
	}
}
