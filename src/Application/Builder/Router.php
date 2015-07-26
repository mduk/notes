<?php

namespace Mduk\Application\Builder;

use Mduk\Application\Stage\InitRouter as InitRouterStage;
use Mduk\Application\Stage\MatchRoute as MatchRouteStage;
use Mduk\Application\Stage\SelectResponseType as SelectResponseTypeStage;
use Mduk\Application\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Application\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Application\Stage\DecodeRequestBody as DecodeRequestBodyStage;

use Mduk\ChainBuilder;

use Mduk\Gowi\Http\Application;

/**
 * Build a Router Application.
 *
 * The Router Application simply uses a 'router' service
 * to get information on what kind of Application should
 * handle the routed Request.
 */
class Router extends \Mduk\ChainBuilder {

  protected $config;

  public function buildRoute( $type, $pathMethod, $config ) {
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

    if ( !isset( $this->routes[ $path ] ) ) {
      $this->routes[ $path ] = [];
    }

    $this->routes[ $path ][ $method ] = [
      'builder' => $type,
      'config' => $config
    ];
  }

  public function build( $app = null ) {
    if ( $app === null ) {
      $app = new Application;
    }

    $app->addStage( new InitRouterStage );
    $app->addStage( new MatchRouteStage );

    $this->configure( $app );
    $app->setConfig( 'routes', $this->routes );

    return $app;
  }

}
