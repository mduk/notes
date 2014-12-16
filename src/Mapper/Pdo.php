<?php

namespace Mduk\Mapper;

use Mduk\Mapper;
use Mduk\Mapper\Factory as Factory;
use Mduk\MagicQueryBuilder;

use Mduk\Collection;
use Mduk\LazyCollection;

use Mduk\Identity\Map as IdentityMap;
use Mduk\Identity\Map\Memory as IdentityMapMemory;

abstract class Pdo implements Mapper {
	use MagicQueryBuilder;

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
	
	public function __construct( Factory $mapperFactory = null, \PDO $pdo, IdentityMap $identityMap = null ) {
		$this->mapperFactory = $mapperFactory;
		$this->db = $pdo;
		$this->identityMap = $identityMap ?: new IdentityMapMemory;
	}

	public function query() {
		return new Query( $this, $this->findSelect, $this->loadSelect, $this->countSelect, $this->table );
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
