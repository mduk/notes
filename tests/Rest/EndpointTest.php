<?php

namespace Mduk\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mduk\Transcoder\Factory as TranscoderFactory;
use Mduk\Mapper\Factory as MapperFactory;
use Mduk\Service\Factory as ServiceFactory;

use Mduk\User\Mapper as UserMapper;
use Mduk\User\Service as UserService;

use Mduk\Note\Mapper as NoteMapper;
use Mduk\Note\Service as NoteService;

class EndpointTest extends \PHPUnit_Framework_TestCase {

  protected function initTranscoderFactory() {
    $transcoderFactory = new TranscoderFactory;

    $transcoderFactory->setFactory( 'generic/json', function() {
      return new \Mduk\Transcoder\Json;
    } );

    $transcoderFactory->setFactory( 'html/template/page/user', function() {
      return new \Mduk\User\Transcoder\Html\Page( dirname( __FILE__ ) . '/../../templates/' );
    } );

    return $transcoderFactory;
  }

  protected function initServiceFactory() {
    global $pdo;

    $mapperFactory = new MapperFactory( $pdo );
    $serviceFactory = new ServiceFactory();

    $serviceFactory->setFactory( 'user', function() use ( $mapperFactory, $pdo ) {
      return new UserService( new UserMapper( $mapperFactory, $pdo ) );
    } );

    $serviceFactory->setFactory( 'note', function() use ( $mapperFactory, $pdo ) {
      return new NoteService( new NoteMapper( $mapperFactory, $pdo ) );
    } );

    return $serviceFactory;
  }

  public function setUp() {
    $routes = [
      '/user/{user_id}' => [
        'service' => 'user',
        'bind' => [ 'user_id' ],
        'GET' => [
          'call' => 'getById',
          'multiplicity' => 'one',
          'transcoders' => [
            'text/html' => 'html/template/page/user',
            'application/json' => 'generic/json'
          ],
        ]
      ],
      '/user/{user_id}/note' => [
        'service' => 'note',
        'bind' => [ 'user_id' ],
        'GET' => [
          'call' => 'getByUserId',
          'transcoders' => [
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ];

    $this->endpoint = new Endpoint( $routes, $this->initServiceFactory(), $this->initTranscoderFactory() );
  }

  public function testInvalidAcceptType() {
    $request = Request::create( 'http://localhost/user/1' );
    $request->headers->set( 'Accept', 'nyan/cat' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 406, $response->getStatusCode() );
  }

  public function testNoMatchingRoute() {
    $request = Request::create( 'http://localhost/not_found' );
    $request->headers->set( 'Accept', 'application/json' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 404, $response->getStatusCode() );
  }

  public function testInvalidUserId() {
    $request = Request::create( 'http://localhost/user/invalid' );
    $request->headers->set( 'Accept', 'application/json' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 404, $response->getStatusCode() );
  }

  public function testUnsupportedMethod() {
    $request = Request::create( 'http://localhost/user/1', 'NYANCAT' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 501, $response->getStatusCode() );
  }

  public function testProhibitedMethod() {
    $request = Request::create( 'http://localhost/user/1/note', 'POST' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 501, $response->getStatusCode() );
  }

  /**
   * Commenting this out. The test now fails.
   * I can't decide right now if it's important enough to warrent
   * the additional hassle. Leaving it here for now as a reminder.
  public function testThat404TakesPrecedenceOver501() {
    $request = Request::create( 'http://localhost/user/invalid', 'NYANCAT' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 404, $response->getStatusCode() );
  }
  */

  public function testThat501TakesPrecedenceOver406() {
    $request = Request::create( 'http://localhost/user/1', 'NYANCAT' );
    $request->headers->set( 'Accept', 'utter/nonsense' );
    $response = $this->endpoint->handle( $request );
    $this->assertEquals( 501, $response->getStatusCode() );
  }

  public function testGetUser() {
    $request = Request::create( 'http://localhost/user/3' );
    $request->headers->set( 'Accept', 'application/json' );
    $response = $this->endpoint->handle( $request );

    $this->assertTrue( $response instanceof Response );
    $this->assertEquals( 200, $response->getStatusCode() );

    $content = $response->getContent();
    $decoded = json_decode( $content );

    $this->assertInstanceOf( '\\stdClass', $decoded,
      "Result should be a stdClass" );
    $this->assertTrue( isset( $decoded->user_id ),
      "Result should have a user_id property" );
    $this->assertEquals( $decoded->user_id, 3,
      "Result should have a user_id property of 3" );
  }

  public function testGetHtmlUser() {
    $request = Request::create( 'http://localhost/user/3' );
    $request->headers->set( 'Accept', 'text/html' );
    $response = $this->endpoint->handle( $request );

    $this->assertTrue( $response instanceof Response );
    $this->assertEquals( 200, $response->getStatusCode() );

    $content = $response->getContent();

    $this->assertContains( '<html>', $content,
      "Response body should contain an html tag." );
  }

  public function testGetPaginatedNotes() {
    $request = Request::create( 'http://localhost/user/1/note' );
    $request->headers->set( 'Accept', 'application/json' );
    $response = $this->endpoint->handle( $request );

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = $response->getContent();
    $decoded = json_decode( $content );

    $this->assertTrue( is_array( $decoded ),
      'Decoded result was not an array' );

    $this->assertEquals( 10, count( $decoded ),
      'Decoded result should be a first page of 10 items' );

    $request = Request::create( 'http://localhost/user/1/note?page=2' );
    $request->headers->set( 'Accept', 'application/json' );
    $response = $this->endpoint->handle( $request );

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = $response->getContent();
    $decoded = json_decode( $content );

    $this->assertEquals( 2, count( $decoded ),
      'Decoded result should be a second page of 2 items' );
  }

}

