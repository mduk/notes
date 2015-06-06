<?php

namespace Mduk\Service;

use Mduk\Collection;

use Mduk\Service;
use Mduk\Service\Request as ServiceRequest;
use Mduk\Service\Response as ServiceResponse;

class Shim implements Service {
  public function request( $call ) {
    return new ServiceRequest( $this, $call );
  }

  public function execute( ServiceRequest $request, ServiceResponse $response ) {
    $call = $request->getCall();

    if ( !isset( $this->calls[ $call ] ) ) {
      throw new \Exception( "Invalid call: {$call}" );
    }

    $call = $this->calls[ $call ];

    $callback = $call->callback;
    $args = $this->getArgs( $request, $call->arguments );

    $result = call_user_func_array( $callback, $args );

    if ( is_array( $result ) ) {
      $results = new Collection( $result );
    }
    else {
      $results = new Collection;
      $results[] = $result;
    }

    $response->setResults( $results );

    return $response;
  }

  public function setCall( $call, $callback, $arguments ) {
    $this->calls[ $call ] = (object) [
      'callback' => $callback,
      'arguments' => $arguments
    ];
  }

  protected function getArgs( $request, $argList ) {
    $args = [];
    foreach ( $argList as $arg ) {
      if ( $arg == '__payload' ) {
        $args[] = $request->getPayload();
        continue;
      }

      $args[] = $request->getParameter( $arg );
    }
    return $args;
  }
}
