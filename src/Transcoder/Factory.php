<?php

namespace Mduk\Transcoder;

class Factory {
	protected $mimes = array(
		'application/json' => '\\Mduk\\Transcoder\\Json',
		'text/html' => '\\Mduk\\Transcoder\\Html'
	);
	protected $transcoders = array();

  public function getTranscoder( $uri ) {
    $bits = parse_url( $uri );
    $class = $bits['path'];
    $params = [];
    if ( isset( $bits['query'] ) ) {
      parse_str( $bits['query'], $params );
    }

    return new $class( $params );
  }

	public function getForMimeType( $mime ) {
		if ( !isset( $this->mimes[ $mime ] ) ) {
			throw new FactoryException( "No transcoder for mime: {$mime}" );
		}

		$class = $this->mimes[ $mime ];

		if ( !isset( $this->transcoders[ $class ] ) ) {
			$this->transcoders[ $class ] = new $class;
		}

		return $this->transcoders[ $class ];
	}
}

class FactoryException extends \Exception {}

