<?php

namespace Mduk\Service;

class Factory {
  public function get( $type ) {
    if ( !isset( $this->factories[ $type ] ) ) {
      throw new \Exception( "No factory for type: {$type}" );
    }

    $factory = $this->factories[ $type ];

    return $factory();
  }

  public function setFactory( $type, \Closure $factory ) {
    $this->factories[ $type ] = $factory;
  }
}

