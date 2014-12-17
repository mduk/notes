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
				'multiplicity' => 'one'
			),
			'/user/{user_id}/note' => array(
				'query' => '\\Mduk\\Note\\Query\\ByUserId',
				'bind' => array( 'user_id' )
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

	public function testGetUser() {
		$request = Request::create( 'http://localhost/user/3' );
		$request->headers->set( 'Accept', 'application/json' );
		$response = $this->endpoint->handle( $request );

		$this->assertTrue( $response instanceof Response );

		$content = $response->getContent();
		$decoded = json_decode( $content );

		$this->assertEquals( $decoded->user_id, 3 );
	}

	public function testGetNotes() {
		$request = Request::create( 'http://localhost/user/3/note' );
		$request->headers->set( 'Accept', 'application/json' );
		$response = $this->endpoint->handle( $request );

		$content = $response->getContent();
		$decoded = json_decode( $content );

		$this->assertTrue( is_array( $decoded ),
			'Decoded result was not an array' );

		$this->assertEquals( 10, count( $decoded ),
			'Decoded result should be a first page of 10 items' );
	}

}

