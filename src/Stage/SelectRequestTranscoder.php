<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class SelectRequestTranscoder implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    if ( $req->getContent() ) {
      $log = $app->getService( 'log' );

      $requestContentType = $req->headers->get( 'Content-Type' );
      $requestTranscoders = $app->getConfig( 'request.transcoders' );
      $requestTranscoder = $app->getService( 'transcoder' )
        ->get( $requestTranscoders[ $requestContentType ] );

      $app->setConfig( 'request.content_type', $requestContentType );
      $app->setConfig( 'request.transcoder', $requestTranscoder );
    }
  }

}
