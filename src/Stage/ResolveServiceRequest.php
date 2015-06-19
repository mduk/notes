<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class ResolveServiceRequest implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $service = $app->getConfig( 'active_route.config.service' );
    $call = $app->getConfig( 'active_route.config.call' );
    $parameters = $app->getConfig( 'active_route.config.parameters', [] );
    $parameterBindings = $app->getConfig( 'active_route.config.bind', [] );

    $app->setConfig(
      'service.request',
      $this->buildServiceRequest( $service, $call, $parameters, $parameterBindings, $app, $req )
    );
  }

  protected function buildServiceRequest( $service, $call, $parameters, $parameterBindings, $app, $req ) {
    $serviceRequest = $app->getService( $service )
      ->request( $call );

    $parameters = $this->mapRequestParams( $parameters, $parameterBindings, $app, $req );

    $requiredParameters = $serviceRequest->getRequiredParameters();
    foreach ( $requiredParameters as $required ) {
      if ( !isset( $parameters[ $required ] ) ) {
        throw new \Exception( "SERVICE REQUEST: Parameter {$required} is required" );
      }
    }

    foreach ( $parameters as $pk => $pv ) {
      $serviceRequest->setParameter( $pk, $pv );
    }

    if ( $app->getConfig( 'request.payload', false ) ) {
      $payload = $app->getConfig( 'request.payload' );
      $serviceRequest->setPayload( $payload );
    }

    return $serviceRequest;
  }

  protected function mapRequestParams( $parameters, $parameterBindings, $app, $req ) {
    foreach ( $parameterBindings as $source => $bind ) {
      switch ( $source ) {
        case 'payload':
          $this->mapRequestParamsFromPayload( $parameters, $bind, $app->getConfig( 'request.payload' ) );
          break;

        case 'query':
          $this->mapRequestParamsFromArray( $parameters, $bind, $req->query->all() );
          break;

        case 'route':
          $this->mapRequestParamsFromArray( $parameters, $bind, $app->getConfig( 'active_route.params' ) );
          break;

        default:
          throw new \Exception("SERVICE REQUEST: Unknown bind '{$bind}'");
      }
    }

    return $parameters;
  }

  protected function mapRequestParamsFromPayload( &$parameters, $bind, $payload ) {
    foreach ( $bind as $param ) {
      if ( is_array( $payload ) ) {
        if ( !isset( $payload[ $param ] ) ) {
          throw new \Exception( "SERVICE REQUEST: {$param} not found." );
        }

        $value = $payload[ $param ];
      }
      else if ( is_object( $payload ) ) {
        if ( !isset( $payload->$param ) ) {
          throw new \Exception( "SERVICE REQUEST: {$param} not found." );
        }

        $value = $payload->$param;
      }

      $parameters[ $param ] = $value;
    }
  }

  protected function mapRequestParamsFromArray( &$parameters, $bind, $array ) {
    foreach ( $bind as $param ) {
      if ( !isset( $array[ $param ] ) ) {
        throw new \Exception( "SERVICE REQUEST: {$param} not found." );
      }

      $parameters[ $param ] = $array[ $param ];
    }
  }
}
