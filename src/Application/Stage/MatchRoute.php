<?php

namespace Mduk\Application\Stage;

use Mduk\Application\Stage\Respond\NotFound as NotFoundResponseStage;
use Mduk\Application\Stage\Respond\MethodNotAllowed as MethodNotAllowedResponseStage;
use Mduk\Application\Stage\Builder as BuilderStage;

use Mduk\Service\Router\Exception as RouterException;

use Mduk\Gowi\Factory;
use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class MatchRoute implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    try {
      $activeRoute = $app->getService( 'router' )
        ->request( 'route' )
        ->setParameter( 'path', $req->getPathInfo() )
        ->setParameter( 'method', $req->getMethod() )
        ->execute()
        ->getResults()
        ->shift();

      if ( !isset( $activeRoute['config']['builder'] ) ) {
        throw new \Exception( "Route config doesn't contain a builder name.\n" . print_r( $activeRoute['config'], true ) );
      }

      $builder = $activeRoute['config']['builder'];

      if ( !isset( $activeRoute['config']['config'] ) ) {
        throw new \Exception( "Route config doesn't contain any builder config" );
      }

      $builderConfig = $activeRoute['config']['config'];
      $newAppConfig = [

        // Things being persisted through Applications (pending rename)
        'debug' => $app->getConfig( 'debug' ),

        // Things that are new to the new appliaction
        'route' => [
          'pattern' => $activeRoute['route'],
          'parameters' => $activeRoute['params'],
        ]

      ];

      return new BuilderStage(
        $builder,
        $builderConfig,
        $newAppConfig
      );
    }
    catch ( RouterException\NotFound $e ) {
      return new NotFoundResponseStage;
    }
    catch ( RouterException\MethodNotAllowed $e ) {
      return new MethodNotAllowedResponseStage;
    }
  }

}
