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
    $parameterBindings = $app->getConfig( 'bind', [] );

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

    if ( $app->getConfig( 'http.request.payload', false ) ) {
      $payload = $app->getConfig( 'http.request.payload' );
      $serviceRequest->setPayload( $payload );
    }

    return $serviceRequest;
  }

  protected function mapRequestParams( $parameters, $parameterBindings, $app, $req ) {
    foreach ( $parameterBindings as $source => $bind ) {
      switch ( $source ) {
        case 'payload':
          $this->mapRequestParamsFromPayload( $parameters, $bind, $app->getConfig( 'http.request.payload' ) );
          break;

        case 'query':
          $this->mapRequestParamsFromArray( $parameters, $bind, $req->query->all() );
          break;

        case 'route':
          $this->mapRequestParamsFromArray( $parameters, $bind, $app->getConfig( 'route.parameters' ) );
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
