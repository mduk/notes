<?php

namespace Mduk;

use Mduk\Identity;

interface Repository {
	public function retrieve( Identity $identity );
	public function query();
}

