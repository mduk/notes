<?php

namespace Mduk\User;

use Mduk\Mapper\Query as MapperQuery;
use Mduk\Mapper\Factory as MapperFactory;

class Query extends MapperQuery {

	public function __construct( MapperFactory $mapperFactory ) {
		$mapper = $mapperFactory->get( '\\Mduk\\User\\Mapper' );
		parent::__construct( $mapper, array('user_id'), array('*'), 'COUNT(*)', 'user' );
	}
}
