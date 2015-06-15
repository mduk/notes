<?php

namespace Mduk\Stage\Response;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
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
