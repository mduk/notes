<?php

namespace Mduk\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mduk\Repository\Factory as RepositoryFactory;
use Mduk\Mapper\Factory as MapperFactory;

class EndpointTest extends \PHPUnit_Framework_TestCase {

	public function testGetUser() {
		global $pdo;

		$routes = array(
			'/user/{user_id}' => array(
				'query' => '\\Mduk\\User\\Query\\ByUserId',
				'bind' => array( 'user_id' ),
				'multiplicity' => 'one'
			)
		);
		$mapperFactory = new MapperFactory( $pdo );
		$request = Request::create( 'http://localhost/user/3' );
		$endpoint = new Endpoint( $routes, $mapperFactory );
		$response = $endpoint->handle( $request );

		$this->assertTrue( $response instanceof Response );

		$content = $response->getContent();
		$decoded = json_decode( $content );

		$this->assertEquals( $decoded->user_id, 3 );
	}

}
