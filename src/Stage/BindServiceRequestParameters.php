<?php

namespace Mduk\Stage;

use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class BindServiceRequestParameters implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $call = $app->getConfig( 'service.call' );
    $parameters = $app->getConfig( 'service.parameters', [] );

    $requiredParameterBindings = $app->getConfig( 'bind.required', [] );
    $parameters = $this->mapRequestParams( $parameters, $requiredParameterBindings, $app, $req );

    $optionalParameterBindings = $app->getConfig( 'bind.optional', [] );
    $parameters = $this->mapRequestParams( $parameters, $optionalParameterBindings, $app, $req );

    $app->setConfig( 'service.parameters', $parameters );
  }

  protected function mapRequestParams( $parameters, $parameterBindings, $app, $req, $required = false ) {
    foreach ( $parameterBindings as $source => $bind ) {
      switch ( $source ) {
        case 'payload':
          try {
            $payload = $app->getConfig( 'http.request.payload' );
            foreach ( $bind as $param ) {
              if ( is_array( $payload ) ) {
                if ( !isset( $payload[ $param ] ) && !$required ) {
                  continue;
                }
                if ( !isset( $payload[ $param ] ) && $required ) {
                  throw new \Exception( "SERVICE REQUEST: {$param} not found." );
                }

                $value = $payload[ $param ];
              }
              else if ( is_object( $payload ) ) {
                if ( !isset( $payload->$param ) && !$required ) {
                  continue;
                }
                if ( !isset( $payload->$param ) && $required ) {
                  throw new \Exception( "SERVICE REQUEST: {$param} not found." );
                }

                $value = $payload->$param;
              }
              else {
                throw new \Exception( "Payload is neither an array or an object." );
              }

              $parameters[ $param ] = $value;
            }
          }
          catch ( \Exception $e ) {
            if ( $required ) {
              throw $e;
            }
          }
          break;

        case 'query':
          try {
            $this->mapRequestParamsFromArray( $parameters, $bind, $req->query->all() );
          }
          catch ( \Exception $e ) {
            if ( $required ) {
              throw $e;
            }
          }
          break;

        case 'route':
          try {
            $this->mapRequestParamsFromArray( $parameters, $bind, $app->getConfig( 'route.parameters' ) );
          }
          catch ( \Exception $e ) {
            if ( $required ) {
              throw $e;
            }
          }
          break;

        default:
          throw new \Exception("SERVICE REQUEST: Unknown bind '{$bind}'");
      }
    }

    return $parameters;
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
