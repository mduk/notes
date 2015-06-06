<?php

namespace Mduk\Transcoder;

use Mduk\Transcoder;

class Text implements Transcoder {

	public function encode( $in ) {
    return print_r( $in, true );
  }

	public function decode( $in ) {
    throw new \Exception( "If you want to decode HTML then be my guest." );
	}

}
