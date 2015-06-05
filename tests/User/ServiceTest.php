<?php

namespace Mduk\User;

use Mduk\Mapper\Factory as MapperFactory;
use Mduk\Service\Exception as ServiceException;

class ServiceTest extends \PHPUnit_Framework_TestCase {
  
  public function testGetById() {
    global $pdo;

    $mapper = new Mapper( new MapperFactory( $pdo ), $pdo );
    $service = new Service( $mapper );

    $query = $service->request( 'getById' );
    $query->setParameter( 'user_id', 1 );
    $response = $query->execute();

    $this->assertEquals( $query, $response->getRequest(),
      "Service response object should contain the original query." );

    $this->assertCount( 1, $response->getResults(),
      "Response results collection should contain one object." );
  }

  public function testGetInvalidUserId() {
    global $pdo;

    $mapper = new Mapper( new MapperFactory( $pdo ), $pdo );
    $service = new Service( $mapper );

    $query = $service->request( 'getById' );
    $query->setParameter( 'user_id', 99999 );

    try {
      $response = $query->execute();
      $this->fail();
    }
    catch ( Service\Exception $e ) {
      $this->assertEquals( Service\Exception::RESOURCE_NOT_FOUND, $e->getCode(),
        "Should have been an RESOURCE_NOT_FOUND service excption." );
    }
  }

}

