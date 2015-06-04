<?php

namespace Mduk\Transcoder;

use Mduk\Transcoder;

class Html implements Transcoder {

  public function __construct( $params ) {
  }

	public function encode( $in ) {
		return json_encode( $in );
	}

	public function decode( $in ) {
		return json_decode( $in );
	}

}
