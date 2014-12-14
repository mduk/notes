<?php

namespace Mduk;

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
		
		$this->assertTrue( $c instanceof Collection );
		$this->assertTrue( isset( $c[0] ) );
		$this->assertTrue( $c[0]->name == "Daniel" );
		
		try {
			$mapper->loadOneByUserId( 123 );
		}
		catch ( \Exception $e ) {
			$this->assertTrue( $e->getMessage() == 'Object not found! SQL: SELECT * FROM user WHERE user_id = 123 LIMIT 1' );
		}
	}
	
	public function testQuery() {
		global $pdo;
		
		$factory = new MapperFactory( $pdo );
		$mapper = $factory->get( '\\Mduk\\UserMapper' );
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
		
		$identityMap = new IdentityMapArray;
		$factory = new MapperFactory( $pdo, $identityMap );
		$mapper = $factory->get( '\\Mduk\\UserMapper' );
	
		$users = $mapper->load();
	
		$this->assertTrue( count( $users ) == 4 );
		$this->assertTrue( $users[1]->name == "Slartibartfast" );
	
		$this->assertTrue( count( $users[0]->note ) == 12 );
		$this->assertTrue( $users[0]->note[0]->body == 'note one' );
	
		$this->assertTrue( $users[0]->note[0]->user[0] == $users[0] );
	}
	
	public function testFind() {
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
		$loaders = $mapper->find();
		
		$this->assertTrue( $loaders[0]->name == "Daniel" );
		$this->assertTrue( $loaders[3]->name == "Ford Prefect" );
	}
	
	public function testCount() {
		global $pdo;
		
		$mapper = new UserMapper( new MapperFactory( $pdo ), $pdo );
	
		$this->assertTrue( $mapper->count() == 4 );
		$this->assertTrue( $mapper->countByRole( 'user' ) == 3 );
	}
}

