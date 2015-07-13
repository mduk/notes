<?php

namespace Mduk\Stage;

use Mduk\Stage\Response\NotFound as NotFoundResponseStage;
use Mduk\Stage\Response\MethodNotAllowed as MethodNotAllowedResponseStage;
use Mduk\Stage\Builder as BuilderStage;

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
        'debug' => $app->getConfig( 'debug' ),
        'route' => [
          'pattern' => $activeRoute['route'],
          'parameters' => $activeRoute['params'],
        ]
      ];

      $builderFactory = new Factory( [
        'service-invocation' => function() {
          return new \Mduk\ServiceInvocationApplicationBuilder;
        },
        'webtable' => function() {
          return new \Mduk\WebTableApplicationBuilder;
        },
        'page' => function() {
          return new \Mduk\PageApplicationBuilder;
        },
        'static-page' => function() {
          return new \Mduk\StaticPageApplicationBuilder;
        }
      ] );

      return new BuilderStage(
        $builderFactory,
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
