<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class DecodeRequestBody implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $content = $req->getContent();
    if ( $content ) {
      $transcoder = $app->getConfig('request.transcoder');
      $payload = $transcoder->decode( $content );
      $app->setConfig( 'request.payload', $payload );
    }
  }

}
