<?php

namespace Mduk\Note\Query;

use Mduk\Note\Query as NoteQuery;
use Mduk\Mapper\Factory as MapperFactory;

class ByUserId extends NoteQuery {

	public function __construct( MapperFactory $mapperFactory ) {
		parent::__construct( $mapperFactory );
		$this->setOperation( 'find' );
		$this->where( 'user_id = :user_id' );
	}

}

