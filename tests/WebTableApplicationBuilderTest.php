<?php

namespace Mduk;

use Mduk\Gowi\Http\Request as HttpRequest;

class WebTableApplicationBuilderTest extends \PHPUnit_Framework_TestCase {
  protected $builder;

  public function setUp() {
    $this->dbFile = '/tmp/webtabletest.db';
    if ( file_exists( $this->dbFile ) ) {
      unlink( $this->dbFile );
    }

    $dsn = 'sqlite:' . $this->dbFile;
    $pdo = new \PDO( $dsn );
    $pdo->exec( file_get_contents( dirname( __FILE__ ) . '/../db.sql' ) );

    $this->builder = new WebTableApplicationBuilder;
    $this->builder->setPdoConnection( $dsn );
    $this->builder->addTable( 'user', 'user_id', [ 'name', 'email', 'role' ] );
  }

  public function tearDown() {
    unlink( $this->dbFile );
  }
  
  public function testGetMany() {
    $response = $this->builder->build()
      ->run( HttpRequest::create( 'http://whatever/user' ) );

    $this->assertEquals( 200, $response->getStatusCode(),
      "Response code should have been 200" );

    $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ),
      "Content-Type should have been application/json" );

    $json = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'objects', $json,
      "Json should have contained an objects key" );

    $this->assertGreaterThan( 2, count( $json->objects ),
      "Json objects array should contain more than two items" );
  }

  public function testGetOne() {
    $response = $this->builder->build()
      ->run( HttpRequest::create( 'http://whatever/user/1' ) );

    $this->assertEquals( 200, $response->getStatusCode(),
      "Response code should have been 200" );

    $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ),
      "Content-Type should have been application/json" );

    $json = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'name', $json,
      "Json should have contained an name key" );

    $this->assertObjectHasAttribute( 'email', $json,
      "Json should have contained an email key" );

    $this->assertObjectHasAttribute( 'role', $json,
      "Json should have contained an role key" );
  }
}
