<?php

namespace Mduk\Identity;

use Mduk\Identity;

interface Map {
	public function has( Identity $object );
	public function set( Identity $object );
	public function get( Identity $object );
}
