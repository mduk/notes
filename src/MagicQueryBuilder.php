<?php

namespace Mduk;

use Mduk\Mapper\Query;

/**
 * Method Naming:
 *   query*()        : Build and return a query object
 *   lazy*()         : Return a LazyCollection for this query
 *   find*()         : Locate records and return lazy loaders
 *   load*()         : Locate and read records in their entirety, return collection of objects.
 *   count*()        : Count the number of records
 *   *One*()         : Ensure one object was found, throw an exception if none or more than one object were found. 
 *   *Range*()       : Retrieve from a given offset and limit the number of records retrieved
 *   *By<field>*     : Use a particular field to locate the record(s), exact match
 *   *And<field>*    : Use an additional field to locate the record(s), exact match
 *   *KeyedBy<field> : Return a collection where the objects have been keyed by a particular field.
 *                     It is up to the collection how duplicates are handled.
 *   *Into           : Insert objects into a specific collection instance
 */
trait MagicQueryBuilder {

	/**
	 * Build a Query object based on the name of the method and the arguments
	 * passed in, execute the Query, and return the result.
	 */
	public function __call( $method, $arguments ) {
		$query = new Query(
			$this,
			$this->findSelect,
			$this->loadSelect,
			$this->countSelect,
			$this->table
		);
		
		// What will be done with the query
		if ( strpos( $method, 'load' ) === 0 ) {
			$exec = 'load';
			$method = substr( $method, 4 );
		}
		else if ( strpos( $method, 'find' ) === 0 ) {
			$exec = 'find';
			$method = substr( $method, 4 );
		}
		else if ( strpos( $method, 'count' ) === 0 ) {
			$exec = 'count';
			$method = substr( $method, 5 );
		}
		else if ( strpos( $method, 'query' ) === 0 ) {
			$exec = 'query';
			$method = substr( $method, 5 );
		}
		else if ( strpos( $method, 'lazy' ) === 0 ) {
			$exec = 'lazy';
			$method = substr( $method, 4 );
		}
		else {
			throw new \Exception( "Unknown method: $method" );
		}
		
		// Limit the number of records retrieved
		if ( strpos( $method, 'One' ) === 0 ) {
			$query->expect( 1 );
			$query->limit( 1 );
			$method = substr( $method, 3 );
		}
		else if ( strpos( $method, 'Range' ) === 0 ) {
			$query->offset( array_shift( $arguments ) );
			$query->limit( array_shift( $arguments ) );
			$method = substr( $method, 5 );
		}
		
		// Find particular records by matching fields to arguments
		if ( strpos( $method, 'By' ) === 0 ) {
			$method = substr( $method, 2 );
			$fields = explode( 'And', $method );
			$where = array();
			for ( $i = 0; $i < count( $fields ); $i++ ) {
				$field = $this->camel2snake( $fields[ $i ] );
				$where[] .= $field . ' = :' . $field;
				$value = array_shift( $arguments );

				if ( $value === null ) {
					throw new Exception( 'Missing field value: ' . $field );
				}

				$query->bindValue( $field, $value );
			}
			$where = implode( ' AND ', $where );
			$query->where( $where );
		}
		
		// When inserting the object into the collection, key them by a particular field
		if ( strpos( $method, 'KeyedBy' ) === 0 ) {
			$method = substr( $method, 7 );
			$field = '';
			$intoPos = strpos( $method, 'Into' );

			if ( $intoPos === 0 || strlen( $method ) == 0 ) {
				throw new Exception( "Missing field to key by!" );
			}

			$keyField = $this->camel2snake( $method );
			$query->keyBy( $keyField );
		}

		// Insert objects into a specific collection instance
		if ( strpos( $method, 'Into' ) === 0 ) {
			$collection = array_shift( $arguments );

			if ( $collection === null ) {
				throw new Exception( 'Missing collection to load into' );
			}

			$query->collection( $collection );
		}

		// Exec: Query - Just return the query object
		if ( $exec == 'query' ) {
			return $query;
		}

		// Exec: Lazy - Return a lazy collection containing this query
		if ( $exec == 'lazy' ) {
			return new LazyCollection( $query );
		}

		// Exec: Count - Return the count
		if ( $exec == 'count' ) {
			return $query->count();
		}

		// Exec: Load/Find
		if ( $exec == 'load' ) {
			return $query->load();
		}

		return $query->find();
	}

}

