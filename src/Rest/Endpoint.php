<?php

namespace Mduk\Rest;

use Mduk\Mapper\Factory as MapperFactory;
use Mduk\Mapper\Exception as MapperException;
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
		try {
			// Which Route matches this request?
			$route = $this->matchRoute( $request );

			// What is being requested?
			$collection = $this->resolveQuery( $request, $route )->load();

			switch ( $request->getMethod() ) {
				case Request::METHOD_GET:
					// How should we encode the response?
					$transcoder = $this->resolveTranscoder( $request, $route );

					// What to encode?
					if ( isset( $route['multiplicity'] ) && $route['multiplicity'] == 'one' ) {
						$encode = $collection->shift();
					}
					else {
						$page = $request->query->get( 'page', 1 );
						$encode = $collection->page( $page - 1 );
					}

					// Encode them.
					$encoded = $transcoder->encode( $encode );

					// Respond
					$response = new Response();
					$response->setStatusCode( 200 );
					$response->setContent( $encoded );
					return $response;

				default:
					throw new EndpointException(
						"Unsupported method",
						EndpointException::UNSUPPORTED_METHOD
					);
			}
		}
		catch ( ResourceNotFoundException $e ) {
			$response = new Response();
			$response->setStatusCode( 404 );
			return $response;
		}
		catch ( MapperException $e ) {
			switch ( $e->getCode() ) {
				case MapperException::UNEXPECTED_ROW_COUNT:
					$response = new Response();
					$status = ( $e->rowCount == 0 ) ? 404 : 500;
					$response->setStatusCode( $status );
					return $response;
			}
		}
		catch ( EndpointException $e ) {
			switch ( $e->getCode() ) {
				case EndpointException::CAN_NOT_FULFIL_ACCEPT_HEADER:
					$response = new Response();
					$response->setStatusCode( 406 );
					return $response;

				case EndpointException::UNSUPPORTED_METHOD:
					$response = new Response();
					$response->setStatusCode( 501 );
					return $response;
				
				default:
					throw $e;
			}
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
		$providedContentTypes = isset( $route['content_types'] ) ? $route['content_types'] : array();
		$acceptedContentTypes = $request->getAcceptableContentTypes();
		foreach ( $acceptedContentTypes as $mime ) {
			if ( !in_array( $mime, $providedContentTypes ) ) {
				continue;
			}

			try {
				return $this->transcoderFactory->getForMimeType( $mime );
			}
			catch ( \Exception $e ) {

			}
		}
		
		throw new EndpointException(
			"No transcoder found for any acceptable mime types",
			EndpointException::CAN_NOT_FULFIL_ACCEPT_HEADER
		);
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
		$matcher = new UrlMatcher($this->routes, $context);
		return $matcher->matchRequest( $request );
	}

}

class EndpointException extends \Exception {
	const CAN_NOT_FULFIL_ACCEPT_HEADER = 1;
	const UNSUPPORTED_METHOD = 2;
}

