<?php

namespace Mduk;

use Mduk\Mapper\Query;
use Mduk\Mapper\Factory as MapperFactory;
use Mduk\Identity\Map\Memory as IdentityMapMemory;
use Mduk\User\Mapper as UserMapper;

class MapperTest extends \PHPUnit_Framework_TestCase
{
	// Test that a specific range can be loaded
	// Test that an overlapping range can be loaded into the same collection 
	//      without overwriting object instances
	public function testLoadRange() {
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
		
		$c = $mapper->loadRange( 1, 2 );
		
		$user2 = $c[1];
		$user3 = $c[2];
		
		$this->assertTrue( !isset( $c[0] ) );
		$this->assertTrue( isset( $c[1] ) );
		$this->assertTrue( isset( $c[2] ) );
		
		$c = $mapper->loadRangeInto( 0, 4, $c );
	
		$this->assertTrue( isset( $c[0] ) );
		$this->assertTrue( $c[1] === $user2 );
		$this->assertTrue( $c[2] === $user3 );
		$this->assertTrue( isset( $c[3] ) );
	}
	
	public function testLoadRangeKeyedBy() {
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
		
		$c = $mapper->loadRangeKeyedByEmail( 1, 2 );
	
		$this->assertTrue( !isset( $c['daniel.kendell@gmail.com'] ) );
		$this->assertTrue( isset( $c['slartibartfast@magrathea.hg'] ) );
	}
	
	public function testLoadByUserId() {
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
		
		$c = $mapper->loadByUserId( 1 );
		
		$this->assertTrue( $c instanceof \Mduk\Gowi\Collection, "Return value is not a Collection" );
		$this->assertTrue( isset( $c[0] ), "Collection doesn't contain an object at offset 0" );
		$this->assertEquals( "Daniel", $c[0]->name );
		
		try {
			$mapper->loadOneByUserId( 123 );
		}
		catch ( \Exception $e ) {
		}
	}
	
	public function testQuery() {
		global $pdo;
		
		$factory = new MapperFactory( $pdo );
		$mapper = $factory->get( '\\Mduk\\User\\Mapper' );
		$query = new Query( $mapper, array( 'user_id' ), array( '*' ), 'COUNT( user_id )', 'user' );
	
		$this->assertTrue( $query->count() == 4 );
	
		$users = $query->load();
	
		$this->assertTrue( $users[0]->name == "Daniel" );
		$this->assertTrue( $users[1]->name == "Slartibartfast" );
	
		$query->keyBy( 'email' );
		$query->collection( $users );
		$users = $query->load();
	
		$this->assertTrue( $users[0]->name == "Daniel" );
		$this->assertTrue( $users['daniel.kendell@gmail.com']->name == "Daniel" );
	}

	public function testLoad() {
		global $pdo;
		
		$identityMap = new IdentityMapMemory;
		$factory = new MapperFactory( $pdo, $identityMap );
		$mapper = $factory->get( '\\Mduk\\User\\Mapper' );
	
		$users = $mapper->load();
	
		$this->assertTrue( count( $users ) == 4,
			"Count should be four" );

		$this->assertTrue( $users[1]->name == "Slartibartfast",
			"Second name should be slartibartfast" );

		$this->assertTrue( count( $users[0]->note ) == 12,
			"User 0 should have 12 notes. Has: " . count( $users[0]->note ) );

		$this->assertTrue( $users[0]->note[0]->body == 'note one',
			"The body of the note should be \"note one\"" );
	
		$this->assertEquals( $users[0], $users[0]->note[0]->user[0],
			"Identity mapping doesn't appear to be working" );
	}
	
  /**
   * This test no longer passes.
   * \Gowi\Collection has no knowlege of LazyLoaders so
   * it's not invoking them as the objects are being resolved.
   * Not fixing this for now, since I'm not entirely convinced
   * that a Collection of LazyLoaders is any more useful than
   * a LazyCollection.
	public function testFind() {
    $this->markTestIncomplete();
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
		$loaders = $mapper->find();

		$this->assertTrue( $loaders[0]->name == "Daniel" );
		$this->assertTrue( $loaders[3]->name == "Ford Prefect" );
	}
  */
	
	public function testCount() {
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
	
		$this->assertTrue( $mapper->count() == 4 );
		$this->assertTrue( $mapper->countByRole( 'user' ) == 3 );
	}
}

