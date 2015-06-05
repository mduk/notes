<?php

namespace Mduk\Transcoder;

class Factory {
	protected $transcoders = [
    'generic/json' => '\\Mduk\\Transcoder\\Json'
  ];

  public function getTranscoder( $transcoder ) {
    $class = $this->transcoders[ $transcoder ];
    return new $class( $params );
  }
}

class FactoryException extends \Exception {}

