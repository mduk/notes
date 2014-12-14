<?php

namespace Mduk;

class Note implements Identity {
	public $note_id;
	public $user_id;
	public $body;

	public $user;

	public function getIdentity() {
		return 'urn:user:' . $this->user_id . ':note:' . $this->note_id;
	}
}
