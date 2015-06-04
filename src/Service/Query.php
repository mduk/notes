<?php

namespace Mduk\Service;

use Mduk\Service;

class Query {
  protected $service;
  protected $call;

  public function __construct( Service $service, $call ) {
    $this->service = $service;
    $this->call = $call;
  }

  public function setParameter( $key, $value ) {
    $this->parameters[ $key ] = $value;
    return $this;
  }

  public function setParameters( array $params ) {
    foreach ( $params as $k => $v ) {
      $this->setParameter( $k, $v );
    }
    return $this;
  }

  public function setPayload( $payload ) {
    $this->payload = $payload;
  }

  public function getCall() {
    return $this->call;
  }

  public function getParameter( $key ) {
    return $this->parameters[ $key ];
  }

  public function execute() {
    return $this->service->execute( $this, new Response( $this ) );
  }
}
