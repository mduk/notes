<?php

namespace Mduk;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
	public function testGet() {
		$c = new Collection( array( 1,2,3,4,5,6,7,8,9,10 ) );
		
		$this->assertEquals( 5, $c->get( 4 ) == 5 );
		$this->assertEquals( array( 5, 6, 7 ), $c->get( 4, 3 ) );
	}
	
	public function testGet_InvalidOffset() {
		$this->setExpectedException('\\Mduk\\CollectionException');	
		
		$c = new Collection;
		$c->get( 4 );
	}
	
	// Test that the number of pages is calculated correctly
	public function testNumPages() {
		$c = new Collection( array(
			1,2,3,4,5,6,7,8,9,10,
			11,12,13,14,15,16,17,18,19,20,
			21,22,23,24,25,26,27,28,29,30,
			31,32,33,34,35,36,37
		) );
		
		$this->assertEquals( 4, $c->numPages() );
		$this->assertEquals( 2, $c->numPages( 20 ) );
	}
	
	// Test that pages are retrieved properly
	public function testPage() {
		$c = new Collection( array(
			1,2,3,4,5,6,7,8,9,10,
			11,12,13,14,15,16,17,18,19,20,
			21,22,23,24,25,26,27,28,29,30,
			31,32,33,34,35,36,37
		) );
		
		$this->assertEquals( array( 1,2,3,4,5,6,7,8,9,10 ), $c->page( 0 ) );
		$this->assertEquals( array( 11,12,13,14,15,16,17,18,19,20 ), $c->page( 1 ) );
		$this->assertEquals( array( 21,22,23,24,25,26,27,28,29,30 ), $c->page( 2 ) );
		$this->assertEquals( array( 31,32,33,34,35,36,37 ), $c->page( 3 ) );
		
		$this->assertEquals( array( 1,2,3,4,5 ), $c->page( 0, 5 ) );
		$this->assertEquals( array( 6,7,8,9,10 ), $c->page( 1, 5 ) );
		
		$this->assertEquals( array( 31,32,33,34,35,36,37 ), $c->page( 1, 30 ) );
	}
	
	public function testCalculatePage() {
		$c = new Collection( array(
			1,2,3,4,5,6,7,8,9,10,
			11,12,13,14,15,16,17,18,19,20,
			21,22,23,24,25,26,27,28,29,30,
			31,32,33,34,35,36,37
		) );
		
		$this->assertEquals( 1, $c->calculatePage( 4 ) );
		$this->assertEquals( 3, $c->calculatePage( 24 ) );
	}
	
	public function testShift() {
		$c = new Collection( array() );
		$this->assertEquals( null, $c->shift() );
		
		$c = new Collection( array( 1, 2 ) );
		
		$this->assertEquals( 2, $c->count() );
		$this->assertEquals( 1, $c->shift() );
		$this->assertEquals( 1, $c->count() );
	}

	// Test that the collection can be iterated
	public function testIteration() {
		$expected = array( 1, 2, 3 );
		$c = new Collection( $expected );
		$i = 0;
		foreach ( $c as $e ) {
			$this->assertEquals( $expected[ $i ], $e );
			$i++;
		}
	}
	
	// Test that the collection works as an array
	public function testArrayAccess() {
		$c = new Collection( array( 1, 2, 3 ) );
		$c[3] = 4;
		
		$this->assertEquals( 4, $c[3] );
		
		unset( $c[3] );
		
		$this->assertFalse( isset( $c[3] ) );
		$this->assertTrue( $c[0] == 1 );
		$this->assertTrue( isset( $c[1] ) );
		$this->assertFalse( isset( $c[4] ) );
		
		$c[] = 5;
		$this->assertTrue( isset( $c[4] ) );
	}
	
	public function testCount() {
		$c = new Collection( array( 1, 2, 3 ) );
		
		$this->assertEquals( 3, count( $c ) );
		$this->assertEquals( 3, $c->count() );
		
		$c = new Collection( array(), 123 );
		$this->assertEquals( 123, count( $c ) );
		$this->assertEquals( 123, $c->count() );
	}
}

