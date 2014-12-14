<?php

namespace Mduk;

use Mduk\Mapper\Query;

interface Mapper {
	public function executeCount( Query $query );
	public function executeFind( Query $query );
	public function executeLoad( Query $query );
}

