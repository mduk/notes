<?php

namespace Mduk\Stage\Response;

use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request as HttpRequest;
use Mduk\Gowi\Http\Response as HttpResponse;

class NotAcceptable implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 406 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent( "406 Not Acceptable\n" .
      $req->headers->get( 'Accept' ) );
    return $res;
  }
}
