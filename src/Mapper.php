<?php

/**
 * Method Naming:
 *   query*()        : Build and return a query object
 *   lazy*()         : Return a LazyCollection for this query
 *   find*()         : Locate records and return lazy loaders
 *   load*()         : Locate and read records in their entirety, return collection of objects.
 *   count*()        : Count the number of records
 *   *One*()         : Ensure one object was found, throw an exception if none or more than one object were found. 
 *                     findOne*() : Return a collection of one object. Equivalent to findRange*( 0, 1 [, …] )
 *                     loadOne*() : Return the object itself (facilitates lazy loading)
 *   *Range*()       : Retrieve from a given offset and limit the number of records retrieved
 *   *By<field>*     : Use a particular field to locate the record(s), exact match
 *   *And<field>*    : Use an additional field to locate the record(s), exact match
 *   *KeyedBy<field> : Return a collection where the objects have been keyed by a particular field. 
 *                     It is up to the collection how duplicates are handled.
 *   *Into           : Insert objects into a specific collection instance
 */
abstract class Mapper {
	protected $db;
	protected $identityMap;
	protected $mapperFactory;

	protected $table;
	protected $countSelect = 'COUNT( id )';
	protected $findSelect = array( 'id' );
	protected $loadSelect = array( '*' );

	abstract protected function mapIdentity( $object );
	abstract protected function mapLazy( $object );
	abstract protected function mapObject( $object );
	
	public function __construct( MapperFactory $mapperFactory = null, PDO $pdo, IdentityMap $identityMap = null ) {
		$this->mapperFactory = $mapperFactory;
		$this->db = $pdo;
		$this->identityMap = $identityMap ?: new IdentityMapArray;
	}

	/**
	 * Execute a Count query
	 */
	public function executeCount( Query $query ) {
		$statement = $this->db->prepare( $query->toSql( 'count' ) );
		foreach ( $query->boundValues() as $key => $value ) {
			$statement->bindValue( $key, $value );
		}
		$statement->execute();

		return $statement->fetchObject()->count;
	}

	/**
	 * Execute a Load query
	 */
	public function executeLoad( Query $query ) {
		return $this->executeQuery( 'load', $query );
	}

	/**
	 * Execute a Find query
	 */
	public function executeFind( Query $query ) {
		return $this->executeQuery( 'find', $query );
	}

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
			throw new Exception( "Unknown method: $method" );
		}
		
		// Limit the number of records retrieved
		if ( strpos( $method, 'One' ) === 0 ) {
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

				$query->bindValue( ':' . $field, $value );
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
			$result = $query->load();
		}
		else {
			$result = $query->find();
		}

		// Looking for one record
		if ( $query->limit() == 1 ) {
			$count = $result->count();

			if ( $count == 0 ) {
				throw new Exception( 'Object not found! SQL: ' . $query->toSql( $exec, true ) );
			}

			if ( $count > 1 ) {
				throw new Exception( 'Multiple objects found! SQL: ' . $query->toSql( $exec, true ) );
			}

			if ( $exec == 'load' ) {
				$result = $result->shift();
			}
		}

		return $result;
	}

	/**
	 * TODO: Can't key by anything if finding. Find select query needs amending to include keyfield
	 */
	protected function executeQuery( $mode, Query $query ) {
		$statement = $this->db->prepare( $query->toSql( $mode ) );
		foreach ( $query->boundValues() as $key => $value ) {
			$statement->bindValue( $key, $value );
		}
		$statement->execute();

		$collection = $query->collection() ?: new Collection();
		$keyField = $query->keyBy();
		$offset = $query->offset();

		while ( $object = $statement->fetchObject() ) {

			// Set the offset key
			if ( $keyField ) {
				if ( !isset( $object->$keyField ) ) {
					throw new Exception( "No value for Key Field: $keyField" );
				}

				$offset = $object->$keyField;
			}

			// Don't overwrite objects
			if ( !isset( $collection[ $offset ] ) ) {
				$identity = $this->mapIdentity( $object );

				if ( !$this->identityMap->has( $identity ) ) {
					if ( $mode == 'find' ) {
						$object = $this->mapLazy( $object );
					}
					else {
						$object = $this->mapObject( $object );
						$this->identityMap->set( $object );
					}
				}
				else
				{
					$object = $this->identityMap->get( $identity );
				}

				$collection[ $offset ] = $object;
			}

			$offset++;
		}

		return $collection;
	}

	protected function getMapper( $class ) {
		return $this->mapperFactory->get( $class );
	}

	protected function camel2snake( $in ) {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $in ) );
	}
}
