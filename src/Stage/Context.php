<?php

namespace Mduk\Stage;

use Mduk\Stage\ResolveServiceRequest as ResolveServiceRequestStage;
use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class Context extends ResolveServiceRequestStage {
  public function execute( Application $app, Request $req, Response $res ) {
    $contextQueries = $app->getConfig( 'active_route.config.context', [] );
    $context = [];
    
    foreach ( $contextQueries as $contextKey => $querySpec ) {
      $service = $querySpec['service'];
      $call = $querySpec['call'];
      $parameters = ( isset( $querySpec['parameters'] ) ) ? $querySpec['parameters'] : [];
      $parameterBindings = ( isset( $querySpec['bind'] ) ) ? $querySpec['bind'] : [];
      $multiplicity = ( isset( $querySpec['multiplicity'] ) ) ? $querySpec['multiplicity'] : 'many';

      $contextValue = $this->buildServiceRequest( $service, $call, $parameters, $parameterBindings, $app, $req );

      $app->setConfig( "context.{$contextKey}", $contextValue );
    }
  }
}

