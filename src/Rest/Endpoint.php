<?php

namespace Mduk\Rest;

use Mduk\Repository\Factory as RepositoryFactory;

use Mduk\Identity\Stub as IdentityStub;

use Mduk\Transcoder\Json as JsonTranscoder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class Endpoint {

	protected $routes;
	protected $repositoryFactory;

	public function __construct( array $routes, RepositoryFactory $repositoryFactory ) {
		$this->routes = $this->initialiseRoutes( $routes );
		$this->repositoryFactory = $repositoryFactory;
	}

	public function handle( Request $request ) {
		switch ( $request->getMethod() ) {
			case Request::METHOD_GET:
				return $this->handleGet( $request );

			default:
				throw new \Exception( "Unsupported method" );
		}
	}

	public function handleGet( $request ) {

		// Which Route matches this request?
		$route = $this->matchRoute( $request );

		// What is being requested?
		$identity = $this->resolveIdentity( $request, $route ); 

		// Where can we find it?
		$repository = $this->resolveRepository( $request, $route );

		// How should we encode it?
		$transcoder = $this->resolveTranscoder( $request, $route );

		// Get the object.
		$object = $repository->retrieve( $identity );

		// Encode it.
		$encoded = $transcoder->encode( $object );

		$response = new Response();
		$response->setStatusCode(200);
		$response->setContent( $encoded );
		return $response;
	}

	protected function resolveIdentity( $request, $route ) {
		$path = $request->getPathInfo();
		$urn = 'urn:' . str_replace( '/', ':', $path );
		return new IdentityStub( $urn );
	}

	protected function resolveRepository( $request, $route ) {
		return $this->repositoryFactory->get( $route['repository'] );
	}

	protected function resolveTranscoder( $request, $route ) {
		return new JsonTranscoder();
	}

	protected function initialiseRoutes( $routeConfig ) {
		$routes = new RouteCollection();
		foreach ( $routeConfig as $routePattern => $routeParams ) {
			$route = new Route( $routePattern, $routeParams );
			$routes->add( $routePattern, $route );
		}
		return $routes;
	}

	protected function matchRoute( $request ) {
		$context = new RequestContext();
	//	$context->fromRequest( $request );
		$matcher = new UrlMatcher($this->routes, $context);
		return $matcher->matchRequest( $request );
	}

}

