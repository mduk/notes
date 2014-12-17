<?php

namespace Mduk;

use Mduk\Mapper\Query;

class LazyCollection extends Collection {
	protected $query;
	
	public function __construct( Query $query ) {
		$this->query = $query;
		$this->query->collection( $this );
		parent::__construct();
	}
	
	/**
	 * Load a range of objects. If the range overlaps
	 * already loaded objects, they will be queried, but
	 * not overwritten in the collection.
	 */
	public function get( $offset, $limit = null ) {
		// Since the count will be incremented when the objects 
		// are inserted, it is necessary to subtract the number
		// of objects to be inserted first
		$this->count -= $limit;

		$this->query->offset( $offset );
		$this->query->limit( $limit );
		$this->query->load();
		
		return parent::get( $offset, $limit );
	}

	public function count() {
		if ( $this->count === null ) {
			$this->count = $this->query->count();
		}
		return parent::count();
	}
}
