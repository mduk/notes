<?php

namespace Mduk\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mduk\Transcoder\Factory as TranscoderFactory;
use Mduk\Mapper\Factory as MapperFactory;

class EndpointTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
		global $pdo;

		$routes = array(
			'/user/{user_id}' => array(
				'query' => '\\Mduk\\User\\Query\\ByUserId',
				'bind' => array( 'user_id' ),
        'GET' => [
          'multiplicity' => 'one',
          'content_types' => [
            'text/html' => '\\Mduk\\Transcoder\\Html?template=user',
            'application/json' => '\\Mduk\\Transcoder\\Json'
          ],
        ]
			),
			'/user/{user_id}/note' => array(
				'query' => '\\Mduk\\Note\\Query\\ByUserId',
				'bind' => array( 'user_id' ),
        'GET' => [
          'content_types' => [
            'application/json' => '\\Mduk\\Transcoder\\Json'
          ]
        ]
			)
		);
		$transcoderFactory = new TranscoderFactory();
		$mapperFactory = new MapperFactory( $pdo );
		$this->endpoint = new Endpoint( $routes, $mapperFactory, $transcoderFactory );
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

	public function testThat404TakesPrecedenceOver501() {
		$request = Request::create( 'http://localhost/user/invalid', 'NYANCAT' );
		$response = $this->endpoint->handle( $request );
		$this->assertEquals( 404, $response->getStatusCode() );
	}

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

