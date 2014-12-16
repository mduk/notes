<?php

namespace Mduk;

interface Transcoder {
	public function encode( $identity );
	public function decode( $encoded );
}

