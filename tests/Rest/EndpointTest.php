<?php

namespace Mduk\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mduk\Repository\Factory as RepositoryFactory;
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
		$mapperFactory = new MapperFactory( $pdo );
		$this->endpoint = new Endpoint( $routes, $mapperFactory );
	}

	public function testGetUser() {
		$request = Request::create( 'http://localhost/user/3' );
		$response = $this->endpoint->handle( $request );

		$this->assertTrue( $response instanceof Response );

		$content = $response->getContent();
		$decoded = json_decode( $content );

		$this->assertEquals( $decoded->user_id, 3 );
	}

	public function testGetNotes() {
		$request = Request::create( 'http://localhost/user/3/note' );
		$response = $this->endpoint->handle( $request );

		$content = $response->getContent();
		$decoded = json_decode( $content );

		$this->assertTrue( is_array( $decoded ), 'Decoded result was not an array' );
	}

}

