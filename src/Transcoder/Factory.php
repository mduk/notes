<?php

namespace Mduk\Transcoder;

use Mduk\Gowi\Factory as GowiFactory;
use Mduk\Transcoder\Mustache as MustacheTranscoder;

class Factory extends GowiFactory {

  protected $templateDir;

  public function __construct( $templateDir ) {
    $this->templateDir = $templateDir;
    parent::__construct( [
      'generic:text' => function() {
        return new \Mduk\Gowi\Transcoder\Generic\Text;
      },
      'generic:json' => function() {
        return new \Mduk\Gowi\Transcoder\Generic\Json;
      },
      'generic:form' => function() {
        return new \Mduk\Gowi\Transcoder\Generic\Form;
      },
    ] );
  }

  public function has( $factory ) {
    try {
      $path = $this->templatePath( $factory );
      return realpath( $path );
    }
    catch ( \Exception $e ) {
      return parent::has( $factory );
    }
  }

  public function get( $factory ) {
    try {
      $path = $this->templatePath( $factory );
      return new MustacheTranscoder( $path );
    }
    catch ( \Exception $e ) {
      return parent::has( $factory );
    }
  }

  protected function templatePath( $factory ) {
    if ( strpos( $factory, 'template:' ) !== 0 ) {
      throw new \Exception( 'Not a template factory' );
    }

    $shrapnel = explode( ':', $factory );
    array_shift( $shrapnel );
    return $this->templateDir . '/' . implode( '/', $shrapnel ) . '.mustache';
  }

}
