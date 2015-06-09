<?php

namespace Mduk\Rest;

use Mduk\Factory;
use Mduk\Service\Exception as ServiceException;

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
    Factory $serviceFactory,
    Factory $transcoderFactory
  ) {
    $this->routes = $this->initialiseRoutes( $routes );
    $this->serviceFactory = $serviceFactory;
    $this->transcoderFactory = $transcoderFactory;
  }

  public function handle( Request $request ) {
    try {

// ------------------------------------------------------------------------------------------------------------------------
      // Which Route matches this request?

      $route = $this->matchRoute( $request );

// ------------------------------------------------------------------------------------------------------------------------
      // Deny all HTTP methods that weren't declared

      if ( !isset( $route[ $request->getMethod() ] ) ) {
        throw new EndpointException(
          "Unsupported Method",
          EndpointException::METHOD_NOT_ALLOWED
        );
      }

// ------------------------------------------------------------------------------------------------------------------------
      // Drill down into configuration

      $routeMethod = $route[ $request->getMethod() ];
      $incomingTranscoders = ( isset( $routeMethod['transcoders']['incoming'] ) )
        ? $routeMethod['transcoders']['incoming']
        : [];
      $outgoingTranscoders = ( isset( $routeMethod['transcoders']['outgoing'] ) )
        ? $routeMethod['transcoders']['outgoing']
        : [];

// ------------------------------------------------------------------------------------------------------------------------
      // How should we encode the response?

      $outgoingTranscoder = $this->resolveTranscoder(
        $request->getAcceptableContentTypes(),
        $outgoingTranscoders,
        EndpointException::NOT_ACCEPTABLE
      );

// ------------------------------------------------------------------------------------------------------------------------
      // Get the Service object

      $serviceRequest = $this->serviceFactory->get( $route['service'] )
        ->request( $routeMethod['call'] );

// ------------------------------------------------------------------------------------------------------------------------
      // Bind parameters to the Service Request

      foreach ( $route['bind'] as $bind ) {
        $serviceRequest->setParameter( $bind, $route[ $bind ] );
      }

// ------------------------------------------------------------------------------------------------------------------------
      // If the HTTP request carries a body, decode it and assign it as the Service Request Payload

      $content = $request->getContent();
      if ( $content ) {
        $incomingTranscoder = $this->resolveTranscoder(
          [ $request->headers->get( 'Content-Type' ) ],
          $incomingTranscoders,
          EndpointException::UNSUPPORTED_MEDIA_TYPE
        );
        $payload = $incomingTranscoder->decode( $content );
        $serviceRequest->setPayload( $payload );
      }

// ------------------------------------------------------------------------------------------------------------------------
      // Execute the Service Request and get the Results Collection

      $collection = $serviceRequest->execute()->getResults();

// ------------------------------------------------------------------------------------------------------------------------
      // Are we only expecting one Result object, or many?

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

// ------------------------------------------------------------------------------------------------------------------------
      // Encode them.

      $encoded = $outgoingTranscoder->encode( $encode );

// ------------------------------------------------------------------------------------------------------------------------
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
        case EndpointException::NOT_ACCEPTABLE:
          $response = new Response();
          $response->setStatusCode( 406 );
          return $response;

        case EndpointException::METHOD_NOT_ALLOWED:
          $response = new Response();
          $response->setStatusCode( 501 );
          return $response;
        
        default:
          throw $e;
      }
    }
  }

  protected function resolveTranscoder( $acceptedContentTypes, $mimeTranscoders, $exceptionCode ) {
    foreach ( $acceptedContentTypes as $mime ) {
      if ( !isset( $mimeTranscoders[ $mime ] ) ) {
        continue;
      }

      try {
        return $this->transcoderFactory->get( $mimeTranscoders[ $mime ] );
      }
      catch ( \Exception $e ) {

      }
    }

    throw new EndpointException(
      "{$exceptionCode} No transcoder found for given mime types: " . print_r( $mimeTranscoders, true ),
      $exceptionCode
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
  const METHOD_NOT_ALLOWED = 405;
  const NOT_ACCEPTABLE = 406;
  const UNSUPPORTED_MEDIA_TYPE = 415;
}

