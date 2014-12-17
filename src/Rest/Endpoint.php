<?php

namespace Mduk\Rest;

use Mduk\Mapper\Factory as MapperFactory;
use Mduk\Transcoder\Factory as TranscoderFactory;

use Mduk\Identity\Stub as IdentityStub;

use Mduk\Transcoder\Json as JsonTranscoder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Endpoint {

	protected $routes;
	protected $mapperFactory;
	protected $transcoderFactory;

	public function __construct( array $routes, MapperFactory $mapperFactory, TranscoderFactory $transcoderFactory ) {
		$this->routes = $this->initialiseRoutes( $routes );
		$this->mapperFactory = $mapperFactory;
		$this->transcoderFactory = $transcoderFactory;
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

		try {
			// Which Route matches this request?
			$route = $this->matchRoute( $request );

			// What is being requested?
			$query = $this->resolveQuery( $request, $route ); 

			// How should we encode it?
			$transcoder = $this->resolveTranscoder( $request, $route );

			// Get the objects.
			$collection = $query->load();

			// What to encode?
			$encode = $collection->page( 0 );
			if ( isset( $route['multiplicity'] ) && $route['multiplicity'] == 'one' ) {
				$encode = $collection->shift();
			}

			// Encode them.
			$encoded = $transcoder->encode( $encode );

			// Respond
			$response = new Response();
			$response->setStatusCode( 200 );
			$response->setContent( $encoded );
			return $response;
		}
		catch ( ResourceNotFoundException $e ) {
			$response = new Response();
			$response->setStatusCode( 404 );
			return $response;
		}
	}

	protected function resolveQuery( $request, $route ) {
		$class = $route['query'];
		$query = new $class( $this->mapperFactory );
		foreach ( $route['bind'] as $bind ) {
			$query->bindValue( $bind, $route[ $bind ] );
		}
		return $query;
	}

	protected function resolveTranscoder( $request, $route ) {
		$mimes = $request->getAcceptableContentTypes();
		foreach ( $mimes as $mime ) {
			try {
				return $this->transcoderFactory->getForMimeType( $mime );
			}
			catch ( \Exception $e ) {

			}
		}
		
		throw new \Exception("No transcoder found for any acceptable mime types: " . implode( ', ', $mimes ) );
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

