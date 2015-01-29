<?php

namespace Mduk;

class ModelTest extends \PHPUnit_Framework_TestCase {

	public function testToPrimitive() {

		global $pdo;
		
		$mapper = new User\Mapper( new Mapper\Factory( $pdo ), $pdo );
		
		$collection = $mapper->loadByUserId( 1 );
		$model = $collection[0];
		
		$primitive = $model->toPrimitive();

		$this->assertInstanceOf( '\\stdClass', $primitive );
		$this->assertEquals( 'Daniel', $primitive->name );
		$this->assertEquals( array(), $primitive->note );
	}

}

