<?php

namespace Mduk;

use Mduk\Collection;

class Model {

	public function toPrimitive() {
		$a = (array) $this;
		foreach ( $a as $k => $v ) {
			if ( is_a( $v, '\\Mduk\\Collection' ) ) {
				$a[ $k ] = $v->toPrimitive();
			}
		}
		return (object) $a;
	}

}

