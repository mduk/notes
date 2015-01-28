<?php

namespace Mduk\Note;

use Mduk\Mapper\Query as MapperQuery;
use Mduk\Mapper\Factory as MapperFactory;

class Query extends MapperQuery {
	public function __construct( MapperFactory $mapperFactory ) {
		$mapper = $mapperFactory->get( '\\Mduk\\Note\\Mapper' );
		parent::__construct( $mapper, array('note_id', 'user_id'), array('*'), 'COUNT(*)', 'note' );
	}
}
