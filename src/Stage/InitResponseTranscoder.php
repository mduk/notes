<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitResponseTranscoder implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $contentType = $app->getConfig( 'http.response.content_type' );
    $transcoders = $app->getConfig( 'http.response.transcoders' );

    $transcoder = $app->getService( 'transcoder' )
      ->get( $transcoders[ $contentType ] );

    $app->setConfig( 'http.response.transcoder', $transcoder );
  }

}
