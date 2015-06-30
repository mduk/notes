<?php

namespace Mduk\Service;

use Mduk\Gowi\Service\Request\Exception\RequiredParameterMissing as RequiredParameterMissingException;

class PdoTest extends \PHPUnit_Framework_TestCase {
  public function testConstruct() {
    global $pdo;

    $service = new Pdo( $pdo, [
     'findUsers' => [] 
    ] );

    $this->assertInstanceOf( '\\Mduk\\Gowi\\Service\\Request', $service->request( 'findUsers' ),
      "Request call should return a request object" );
  }

  public function testRequiredParameters() {
    global $pdo;

    $params = [ 'email', 'password' ];
    $service = new Pdo( $pdo, [
      'findUserByEmailAndPassword' => [
        'required' => $params
      ]
    ] );

    $this->assertEquals( $params, $service->request( 'findUserByEmailAndPassword' )->getRequiredParameters(),
      "Service request required parameters should equal those specified in the query config" );
  }

  public function testSelectHappyPath() {
    global $pdo;

    $service = new Pdo( $pdo, [
      'findByUserId' => [
        'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
        'required' => [ 'user_id' ]
      ]
    ] );

    $result = $service->request( 'findByUserId' )
      ->setParameter( 'user_id', 1 )
      ->execute()
      ->getResults()
      ->shift();

    $this->assertInstanceOf( '\\stdClass', $result,
      "First (and only) result should have been a stdClass" );

    $this->assertEquals( 'Daniel', $result->name,
      "Should have been daniel's record" );
  }

  public function testSelect_RequiredParameterMissing() {
    global $pdo;

    $service = new Pdo( $pdo, [
      'findByUserId' => [
        'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
        'required' => [ 'user_id' ]
      ]
    ] );

    try {
      $result = $service->request( 'findByUserId' )
        ->execute();
      $this->fail();
    }
    catch ( RequiredParameterMissingException $e ) {

    }
  }
}

