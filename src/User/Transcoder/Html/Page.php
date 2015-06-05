<?php

namespace Mduk\User\Transcoder\Html;

use Mduk\Transcoder\Html as HtmlTranscoder;

class Page extends HtmlTranscoder {
  public function encode( $in ) {
    return $this->render( 'user_page', $in );
  }
}
