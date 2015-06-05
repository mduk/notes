<?php

namespace Mduk\Rest;

use Mduk\Service\Factory as ServiceFactory;
use Mduk\Service\Exception as ServiceException;

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
  protected $serviceFactory;
  protected $transcoderFactory;

  public function __construct(
    array $routes,
    ServiceFactory $serviceFactory,
    TranscoderFactory $transcoderFactory
  ) {
    $this->routes = $this->initialiseRoutes( $routes );
    $this->serviceFactory = $serviceFactory;
    $this->transcoderFactory = $transcoderFactory;
  }

  public function handle( Request $request ) {
    try {
      // Which Route matches this request?
      $route = $this->matchRoute( $request );

      // Deny all methods that weren't declared
      if ( !isset( $route[ $request->getMethod() ] ) ) {
        throw new EndpointException(
          "Unsupported Method",
          EndpointException::UNSUPPORTED_METHOD
        );
      }

      $routeMethod = $route[ $request->getMethod() ];

      $service = $this->serviceFactory->get( $route['service'] );
      $routeMethod = $route[ $request->getMethod() ];

      // How should we encode the response?
      $transcoder = $this->resolveTranscoder( $request, $routeMethod );

      $serviceRequest = $service->request( $routeMethod['call'] );

      foreach ( $route['bind'] as $bind ) {
        $serviceRequest->setParameter( $bind, $route[ $bind ] );
      }

      $collection = $serviceRequest->execute()->getResults();

      // What to encode? Just one, or a page.
      if ( isset( $routeMethod['multiplicity'] ) && $routeMethod['multiplicity'] == 'one' ) {
        $encode = $collection->shift();

        // If the multiplicity is one, then we expect one.
        if ( !$encode ) {
          $response = new Response();
          $response->setStatusCode( 404 );
          return $response;
        }
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
    }
    catch ( ResourceNotFoundException $e ) {
      $response = new Response();
      $response->setStatusCode( 404 );
      return $response;
    }
    catch ( ServiceException $e ) {
      switch ( $e->getCode() ) {
        case ServiceException::RESOURCE_NOT_FOUND:
          $response = new Response();
          $response->setStatusCode( 404 );
          return $response;

        default:
          $response = new Response();
          $response->setStatusCode( 500 );
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

  protected function resolveTranscoder( $request, $route ) {
    $providedContentTypes = isset( $route['transcoders'] ) ? $route['transcoders'] : array();
    $acceptedContentTypes = $request->getAcceptableContentTypes();
    foreach ( $acceptedContentTypes as $mime ) {
      if ( !isset( $providedContentTypes[ $mime ] ) ) {
        continue;
      }

      try {
        return $this->transcoderFactory->get( $providedContentTypes[ $mime ] );
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

