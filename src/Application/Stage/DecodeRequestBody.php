<?php

namespace Mduk\Application\Stage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class DecodeRequestBody implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $content = $req->getContent();
    if ( $content ) {
      $transcoder = $app->getConfig('http.request.transcoder');
      $payload = $transcoder->decode( $content );
      $app->setConfig( 'http.request.payload', $payload );
    }
  }

}
