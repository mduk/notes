<?php

namespace Mduk\Stage\Response;

use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request as HttpRequest;
use Mduk\Gowi\Http\Response as HttpResponse;

class MethodNotAllowedResponseStage implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 405 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent(
      "405 Method Not Allowed\n" .
      $req->getMethod() . ' is not allowed on ' . $req->getUri()
    );
    return $res;
  }
}
