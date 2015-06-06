<?php

namespace Mduk;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

class Factory implements LoggerAwareInterface {

  protected $factories = [];
  protected $logger;

  public function __construct( $factories = [], LoggerInterface $logger = null ) {
    $this->factories = $factories;
    $this->logger = $logger;
  }

  public function get( $type ) {
    if ( !isset( $this->factories[ $type ] ) ) {
      throw new \Exception( "No factory for type: {$type}" );
    }

    $factory = $this->factories[ $type ];

    $object = $factory();

    if ( $object instanceof LoggerAwareInterface ) {
      $object->setLogger( $this->logger );
    }

    return $object;
  }

  public function setFactory( $type, \Closure $factory ) {
    $this->factories[ $type ] = $factory;
  }

  public function setLogger( LoggerInterface $logger ) {
    $this->logger = $logger;
  }

}

