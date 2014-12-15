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
				'repository' => '\\Mduk\\User\\Repository'
			)
		);
		$mapperFactory = new MapperFactory( $pdo );
		$repositoryFactory = new RepositoryFactory( $mapperFactory );
		$request = Request::create( 'http://localhost/user/3' );
		$endpoint = new Endpoint( $routes, $repositoryFactory );
		$response = $endpoint->handle( $request );

		$this->assertTrue( $response instanceof Response );
		$decoded = json_decode( $response->getContent() );
		$this->assertEquals( $decoded->user_id, 3 );
	}

}

