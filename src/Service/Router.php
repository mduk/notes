<?php

namespace Mduk\Service;

use Mduk\Gowi\Service as GowiService;
use Mduk\Gowi\Service\Request as GowiServiceRequest;
use Mduk\Gowi\Service\Response as GowiServiceResponse;

use Symfony\Component\Routing\Matcher\UrlMatcher as SfUrlMatcher;
use Symfony\Component\Routing\RequestContext as SfRequestContext;
use Symfony\Component\Routing\RouteCollection as SfRouteCollection;
use Symfony\Component\Routing\Route as SfRoute;
use Symfony\Component\Routing\Exception\ResourceNotFoundException as SfResourceNotFoundException;

class Router implements GowiService {
  protected $config;
  protected $routes;

  public function __construct( $config ) {
    $this->config = $config;
    $this->initialiseRouter();
  }

  public function request( $call ) {
    return new GowiServiceRequest( $this, $call );
  }

  public function execute( GowiServiceRequest $request, GowiServiceResponse $response ) {
    switch ( $request->getCall() ) {
      case 'route':
        $path = $request->getParameter( 'path' );
        return $this->route( $path, $response );
    }
  }
  
  protected function initialiseRouter() {
    $this->routes = new SfRouteCollection();
    foreach ( $this->config as $routePattern => $routeParams ) {
      $route = new SfRoute( $routePattern, $routeParams );
      $this->routes->add( $routePattern, $route );
    }
  }

  protected function route( $path, $response ) {
    try {
      $matcher = new SfUrlMatcher(
        $this->routes,
        new SfRequestContext()
      );
      $route = $matcher->match( $path );
    }
    catch ( SfResourceNotFoundException $e ) {
      throw new Router\Exception("There is no route for {$path}");
    }

    return $response->addResult( $route );
  }
}
