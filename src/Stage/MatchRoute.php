<?php

namespace Mduk\Stage;

use Mduk\Stage\Response\NotFound as NotFoundResponseStage;
use Mduk\Stage\Response\MethodNotAllowed as MethodNotAllowedResponseStage;

use Mduk\Service\Router\Exception as RouterException;

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

      $appConfig = array_merge(
        $app->getConfigArray(),
        [
          'route' => [
            'pattern' => $activeRoute['route'],
            'parameters' => $activeRoute['params']
          ]
        ],
        $activeRoute['config']
      );
      $app->setConfigArray( $appConfig );
    }
    catch ( RouterException\NotFound $e ) {
      return new NotFoundResponseStage;
    }
    catch ( RouterException\MethodNotAllowed $e ) {
      return new MethodNotAllowedResponseStage;
    }
  }

}
