<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class ResolveServiceRequest implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $service = $app->getConfig( 'service.name' );
    $call = $app->getConfig( 'service.call' );
    $parameters = $app->getConfig( 'service.parameters', [] );

    $serviceRequest = $app->getService( $service )
      ->request( $call );

    $requiredParameters = $serviceRequest->getRequiredParameters();
    foreach ( $requiredParameters as $required ) {
      if ( !isset( $parameters[ $required ] ) ) {
        throw new \Exception( "SERVICE REQUEST: Parameter {$required} is required" );
      }
    }

    foreach ( $parameters as $pk => $pv ) {
      $serviceRequest->setParameter( $pk, $pv );
    }

    if ( $app->getConfig( 'http.request.payload', false ) ) {
      $payload = $app->getConfig( 'http.request.payload' );
      $serviceRequest->setPayload( $payload );
    }

    $app->setConfig( 'service.request', $serviceRequest );
  }

}
