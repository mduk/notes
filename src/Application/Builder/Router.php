<?php

namespace Mduk\Application\Builder;

use Mduk\Application\Stage\InitRouter as InitRouterStage;
use Mduk\Application\Stage\MatchRoute as MatchRouteStage;
use Mduk\Application\Stage\SelectResponseType as SelectResponseTypeStage;
use Mduk\Application\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Application\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Application\Stage\DecodeRequestBody as DecodeRequestBodyStage;

use Mduk\Application\Builder as AppBuilder;

use Mduk\Gowi\Http\Application;

/**
 * Build a Router Application.
 *
 * The Router Application simply uses a 'router' service
 * to get information on what kind of Application should
 * handle the routed Request.
 */
class Router extends AppBuilder {

  protected $config;

  public function defineRoute( $type, $pathMethod, $config ) {
    if ( is_array( $pathMethod ) && count( $pathMethod ) == 2 ) {
      $method = $pathMethod[0];
      $path = $pathMethod[1];
    }
    else if ( is_string( $pathMethod ) ) {
      $method = 'GET';
      $path = $pathMethod;
    }
    else {
      throw new \InvalidArgumentException(
        print_r( $pathMethod, true ) . ' is not acceptable as the $pathMethod argument'
      );
    }

    if ( $this->getDebug() ) {
      $this->getLogger()
        ->debug( __CLASS__ . ": Defining route: {$type} {$method} {$path}" );
    }

    $this->routes[] = [
      'type' => $type,
      'pathmethod' => $pathMethod,
      'config' => $config
    ];
  }

  public function build( Application $app = null, array $config = [] ) {
    $app = parent::build( $app, $config );

    $app->addStage( new InitRouterStage );
    $app->addStage( new MatchRouteStage );

    $allRoutes = [];

    foreach ( $this->routes as $routeSpec ) {
      $builtRoutes = $this->getApplicationBuilderFactory()->get( $routeSpec['type'] )
        ->buildRoutes( $routeSpec['pathmethod'], $routeSpec['config'] );
      $allRoutes = array_replace_recursive( $allRoutes, $builtRoutes );
    }

    $app->setConfig( 'routes', $allRoutes );

    return $app;
  }

}
