<?php

namespace Mduk\Transcoder;

use Mduk\Transcoder;
use Mduk\Identity;

class Json implements Transcoder {

	public function encode( $in ) {
		return json_encode( $in );
	}

	public function decode( $in ) {
		return json_decode( $in );
	}

}
