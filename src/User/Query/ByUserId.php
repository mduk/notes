<?php

namespace Mduk\User\Query;

use Mduk\User\Query as UserQuery;
use Mduk\Mapper\Factory as MapperFactory;

class ByUserId extends UserQuery {

	public function __construct( MapperFactory $mapperFactory ) {
		parent::__construct( $mapperFactory );
		$this->where( 'user_id = :user_id' );
		$this->expect( 1 );
	}

}

