<?php

namespace Mduk\Transcoder;

use Mduk\Gowi\Transcoder;

class Mustache implements Transcoder {
  protected $templatePath;

  public function __construct( $templatePath ) {
    $this->templatePath = $templatePath;
  }

  public function encode( $in, array $context = null ) {
    $renderer = new \Mustache_Engine;

    if ( $context ) {
      foreach ( $context as $key => $request ) {
        $in['context'][ $key ] = $request->execute()->getResults();
      }
    }

    return $renderer->render( file_get_contents( $this->templatePath ), $in );
  }

  public function decode( $in ) {
    throw new \Exception( "Can't decode from html" );
  }
}
