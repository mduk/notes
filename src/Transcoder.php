<?php

namespace Mduk;

interface Transcoder {
	public function encode( Identity $identity );
	public function decode( $encoded );
}

