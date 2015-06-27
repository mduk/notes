<?php

namespace Mduk\Stage;

use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class Context implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $contextQueries = $app->getConfig( 'context', [] );
    $context = [];
    
    foreach ( $contextQueries as $contextKey => $querySpec ) {
      $service = $querySpec['service']['name'];
      $call = $querySpec['service']['call'];

      $parentParameters = $app->getConfig( 'service.parameters' );

      $parameters = ( isset( $querySpec['service']['parameters'] ) )
        ? $querySpec['service']['parameters']
        : [];

      $parameters = array_merge( $parentParameters, $parameters );

      $contextRequest = $app->getService( $service )
        ->request( $call )
        ->setParameters( $parameters );

      $app->setConfig( "context.{$contextKey}", $contextRequest );
    }
  }
}

