<?php

namespace Mduk\Application\Stage\Respond;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class NotAcceptable implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 406 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent( "406 Not Acceptable\n" .
      $req->headers->get( 'Accept' ) );
    return $res;
  }
}
