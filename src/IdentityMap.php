<?php

namespace Mduk;

interface IdentityMap {
	public function has( Identity $object );
	public function set( Identity $object );
	public function get( Identity $object );
}
