<?php

class Query {
	protected $mapper;
	protected $boundValues = array();
	
	protected $findSelect;
	protected $loadSelect;
	protected $countSelect;
	protected $from;
	protected $where;
	protected $limit;
	protected $offset = 0;
	
	protected $collection;
	protected $keyBy;

	/**
	 * @param Mapper $mapper The Mapper object used to fulfil this query
	 * @param string $find The SELECT part of the query for finding objects
	 * @param string $load The SELECT part of the query for loading objects
	 * @param string $count The SELECT part of the query for counting objects
	 */
	public function __construct( Mapper $mapper, array $find, array $load, $count, $from, $where = null ) {
		$this->mapper = $mapper;
		$this->select = array(
			'find' => $find,
			'load' => $load,
			'count' => $count
		);
		$this->from = $from;
		$this->where = $where;
	}

	public function where( $where = null ) {
		$this->where = $where;
	}

	public function limit( $limit = null ) {
		if ( !$limit ) {
			return $this->limit;
		}

		$this->limit = $limit;
		return $this;
	}

	public function offset( $offset = null ) {
		if ( !$offset ) {
			return $this->offset;
		}

		$this->offset = $offset;
		return $this;
	}

	public function bindValue( $placeholder, $value ) {
		$this->boundValues[ $placeholder ] = $value;
	}

	public function boundValues() {
		return $this->boundValues;
	}

	/**
	 * Set/Get the collection object to insert objects into
	 *
	 * @param Collection $collection The collection instance to use. Omit this to retrieve instance.
	 */
	public function collection( Collection $collection = null ) {
		if ( !$collection ) {
			return $this->collection;
		}

		$this->collection = $collection;
		return $this;
	}

	/**
	 * Set/Get the field to key the objects in the result collection by.
	 * This value will be injected into the SELECT statement if not already present
	 *
	 * @param string $field The field to use. Omit this to retrieve field.
	 */
	public function keyBy( $field = null ) {
		if ( !$field ) {
			return $this->keyBy;
		}

		$this->keyBy = $field;
		return $this;
	}

	/**
	 * Assemble SQL query
	 *
	 * @param string $mode 'count', 'find', 'load' Which query mode to build the SQL for.
	 * @param boolean $bind Substitute any bound values into the SQL string. Used for Exceptions only. Default: false
	 * @return string The completed SQL query string
	 */
	public function toSql( $mode, $bind = false ) {
		if ( !isset( $this->select[ $mode ] ) ) {
			throw new Exception( "Invalid mode: $mode" );
		}

		$isCount = ( $mode == 'count' );
		$select = $this->select[ $mode ];

		if ( $this->keyBy && !$isCount ) {
			$select[] = $this->keyBy;
		}

		if ( $isCount ) {
			$fields = $select . ' AS count';
		}
		else {
			$fields = implode( ', ', $select );
		}

		$sql  = 'SELECT ' . $fields;
		$sql .= ' FROM ' . $this->from;
		$sql .= $this->where ? ' WHERE ' . $this->where : '';
		$sql .= $this->limit ? ' LIMIT ' . $this->limit : '';
		$sql .= $this->offset ? ' OFFSET ' . $this->offset : '';

		if ( $bind ) {
			foreach ( $this->boundValues as $key => $value ) {
				$supposedlySafe = ( is_int( $value ) ? $value : '"' . $value . '"' );
				$sql = str_replace( $key, $supposedlySafe, $sql );
			}
		}

		return $sql;
	}

	/**
	 * Find objects matching this query
	 */
	public function find() {
		return $this->mapper->executeFind( $this );
	}

	/**
	 * Load objects matching this query
	 */
	public function load() {
		return $this->mapper->executeLoad( $this );
	}

	/**
	 * Count objects matching this query
	 */
	public function count() {
		return $this->mapper->executeCount( $this );
	}
}
