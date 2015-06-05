<?php

namespace Mduk\Transcoder;

class Factory {
  public function getTranscoder( $transcoder ) {
    switch ( $transcoder ) {
      case 'generic/json':
        return new Json;

      case 'html/template/page/user':
        return new \Mduk\User\Transcoder\Html\Page( dirname( __FILE__ ) . '/../../templates/' );
    }
  }
}

class FactoryException extends \Exception {}

