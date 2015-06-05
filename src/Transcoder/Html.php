<?php

namespace Mduk\Transcoder;

use Mduk\Transcoder;

abstract class Html implements Transcoder {

  protected $templatePath;

  public function __construct( $templatePath ) {
    $this->templatePath = $templatePath;
  }

	abstract public function encode( $in );

	public function decode( $in ) {
    throw new \Exception( "If you want to decode HTML then be my guest." );
	}

  protected function render( $template, $context ) {
    $renderer = new \Mustache_Engine( [
      'loader' => new \Mustache_Loader_FilesystemLoader( $this->templatePath )
    ] );
    return $renderer->render( $template, $context );
  }

}
