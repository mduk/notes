<?php

namespace Mduk\Transcoder;

class Factory {
	protected $mimes = array(
		'application/json' => '\\Mduk\\Transcoder\\Json'
	);
	protected $transcoders = array();

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

