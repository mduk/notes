<?php

namespace Mduk\Mapper;

class Exception extends \Exception {
	const UNEXPECTED_ROW_COUNT = 1;

	public $rowCount;
}

