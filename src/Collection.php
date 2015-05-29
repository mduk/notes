<?php

namespace Mduk;

class Collection implements \Iterator, \ArrayAccess, \Countable {
	protected $objects = array();
	protected $count = null;
	protected $pointer = 0;

	public function __construct( array $objects = array(), $count = null ) {
		$this->objects = $objects;
		$this->count = $count;
	}

	/**
	 * Retrieve one page of objects, if a page extends beyond
	 * the end of the collection, the last page is cut short.
	 *
	 * @param int $page Which page you want.
	 * @param int $limit How many objects in each page. Default 10.
	 * @return array Objects
	 */
	public function page( $page, $limit = 10 ) {
		$offset = $page * $limit;
		$end = $offset + $limit;

		if ( $end > $this->count() ) {
			$difference = $end - $this->count();
			$limit = $limit - $difference;
		}

		return $this->get( $offset, $limit );
	}

	/**
	 * Retrieve an object or a range of objects from the collection.
	 * If the range extends beyond the end of the collection, an
	 * exception will be thrown.
	 *
	 * If no $limit is provided (in other words, is null), the method will understand that to mean
	 * you want a single object at a specified $offset to be returned.
	 * 
	 * If you do specify a $limit, then you will always get an array back, even if you limited it
	 * to only one object. It is a range of n to n+1 only.
	 *
	 * @param mixed $offset The offset to retrieve from. Either a numeric key, or a string key if $limit == 1.
	 * @param null|int $limit The number of objects to retrieve.
	 * @throws CollectionException
	 * @return mixed Either an array of retrieved objects, or a single object
	 */
	public function get( $offset, $limit = null ) {
		if ( $limit === null ) {
			return $this->resolveObject( $offset );
		}

		$objects = array();

		for ( $i = $offset; $i < $offset + $limit; $i++ ) {
			$objects[] = $this->resolveObject( $i );
		}

		return $objects;
	}

	/**
	 * Get the number of pages
	 */
	public function numPages( $size = 10 ) {
		return ceil( ( $this->count() - 1 ) / $size );
	}

	/**
	 * Calculate what page an offset is on
	 */
	public function calculatePage( $offset, $size = 10 ) {
		return ceil( $offset / $size );
	}

	/**
	 * Shift the first object off the collection
	 */
	public function shift() {
		if ( count( $this ) == 0 ) {
			return null;
		}

		$object = array_shift( $this->objects );
		$this->count--;

		if ( $object instanceof LazyLoader ) {
			$object = $object->load();
		}

		return $object;
	}

	// Iterator Interface

	public function current() {
		return $this->get( $this->pointer );
	}

	public function key() {
		return $this->pointer;
	}

	public function next() {
		$this->pointer++;
	}

	public function rewind() {
		$this->pointer = 0;
	}

	public function valid() {
		return isset( $this->objects[ $this->pointer ] );
	}

	// ArrayAccess Interface

	/**
	 * Check if an offset exists
	 * TODO: This'll load any lazy objects, should probably avoid that.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->objects[ $offset ] );
	}

	/**
	 * Retrieve an offset
	 */
	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * Add an item, if it's a new offset then
	 * also increment the count.
	 */
	public function offsetSet( $offset, $value ) {
		if ( $offset === null ) {
			$this->count++;
			$this->objects[] = $value;
			return;
		}
		
		if ( !$this->offsetExists( $offset ) ) {
			$this->count++;
		}
		
		$this->objects[ $offset ] = $value;
	}

	/**
	 * Remove an item, decrement count
	 */
	public function offsetUnset( $offset ) {
		if ( $this->offsetExists( $offset ) ) {
			unset( $this->objects[ $offset ] );
			$this->count();
		}
	}

	// Countable Interface

	public function count() {
		if ( $this->count ) {
			return $this->count;
		}

		return count( $this->objects );
	}

	/**
	 * Resolve an object within the collection.
	 * If the object is an instance of LazyLoader, then
	 * the real object will be loaded, the lazy loader
	 * will be swapped out for it and the real object
	 * will be returned.
	 *
	 * @param mixed $offset The offset to resolve
	 * @return mixed The object stored at the specified offset
	 */
	protected function resolveObject( $offset ) {
		if ( !isset( $this->objects[ $offset ] ) ) {
			throw new CollectionException(
				"Offset $offset doesn't exist",
				CollectionException::INVALID_OFFSET
			);
		}

		$object = $this->objects[ $offset ];

		if ( $object instanceof LazyLoader ) {
			$object = $object->load();
			$this->objects[ $offset ] = $object;
		}

		return $object;
	}
}

class CollectionException extends \Exception {
	const INVALID_OFFSET = 1;
}
