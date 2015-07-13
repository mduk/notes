<?php

namespace Mduk;

use Mduk\Gowi\Http\Request as HttpRequest;

class WebTableApplicationBuilderTest extends \PHPUnit_Framework_TestCase {
  protected $builder;
  protected $dsn;
  protected $buildConfig = [
    'table' => 'user',
    'pk' => 'user_id',
    'fields' => [ 'name', 'email', 'role' ]
  ];

  protected function buildConfig() {
    return array_replace_recursive( $this->buildConfig, [
      'connection' => [
        'dsn' => $this->dsn
      ]
    ] );
  }

  public function setUp() {
    $this->dbFile = '/tmp/webtabletest.db';
    if ( file_exists( $this->dbFile ) ) {
      unlink( $this->dbFile );
    }

    $this->dsn = 'sqlite:' . $this->dbFile;
    $pdo = new \PDO( $this->dsn );
    $pdo->exec( file_get_contents( dirname( __FILE__ ) . '/../db.sql' ) );

    $this->builder = new WebTableApplicationBuilder;
  }

  public function tearDown() {
    unlink( $this->dbFile );
  }
  
  public function testGetMany() {
    $response = $this->builder->build( $this->buildConfig() )
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
    $response = $this->builder->build( $this->buildConfig() )
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

  public function testCreate() {
    $request = HttpRequest::create( 'http://whatever/user', 'POST', [], [], [], [], json_encode( (object) [
      'name' => 'zaphod beeblebrox',
      'email' => 'zaphod.beeblebrox@president.hg',
      'role' => 'president'
    ] ) );
    $request->headers->set( 'Content-Type', 'application/json' );

    $response = $this->builder->build( $this->buildConfig() )
      ->run( $request );

    $this->assertEquals( 200, $response->getStatusCode(),
      "Response code should have been 200" );
  }

  public function testDelete() {
    $response = $this->builder->build( $this->buildConfig() )
      ->run( HttpRequest::create( 'http://whatever/user/1', 'DELETE' ) );

    $this->assertEquals( 200, $response->getStatusCode(),
      "Response code should have been 200" );
  }

  public function testUpdate() {
    $originalUser = json_decode(
      $this->builder->build( $this->buildConfig() )
        ->run( HttpRequest::create( 'http://whatever/user/1' ) )
        ->getContent()
    );

    $request = HttpRequest::create( 'http://whatever/user/1', 'PUT', [], [], [], [], json_encode( (object) [
      'name' => 'changed',
      'email' => 'changed',
      'role' => 'changed'
    ] ) );
    $request->headers->set( 'Content-Type', 'application/json' );

    $response = $this->builder->build( $this->buildConfig() )
      ->run( $request );

    $this->assertEquals( 200, $response->getStatusCode(),
      "Response code should have been 200" );

    $updatedUser = json_decode(
      $this->builder->build( $this->buildConfig() )
        ->run( HttpRequest::create( 'http://whatever/user/1' ) )
        ->getContent()
    );

    $this->assertNotEquals( $originalUser, $updatedUser,
      "User record is unchanged" );

    $this->assertEquals( 'changed', $updatedUser->name,
      "The name field didn't get updated" );

  }

  public function testUpdatePatch() {
    $originalUser = json_decode(
      $this->builder->build( $this->buildConfig() )
        ->run( HttpRequest::create( 'http://whatever/user/2' ) )
        ->getContent()
    );

    $request = HttpRequest::create( 'http://whatever/user/2', 'PATCH', [], [], [], [], json_encode( (object) [
      'name' => 'changed',
    ] ) );
    $request->headers->set( 'Content-Type', 'application/json' );

    $response = $this->builder->build( $this->buildConfig() )
      ->run( $request );

    $this->assertEquals( 200, $response->getStatusCode(),
      "Response code should have been 200" );

    $updatedUser = json_decode(
      $this->builder->build( $this->buildConfig() )
        ->run( HttpRequest::create( 'http://whatever/user/2' ) )
        ->getContent()
    );

    $this->assertNotEquals( $originalUser, $updatedUser,
      "User record is unchanged" );

    $this->assertEquals( 'changed', $updatedUser->name,
      "The name field didn't get updated" );

  }
}
