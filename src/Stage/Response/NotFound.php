<?php

namespace Mduk\Stage\Response;

use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request as HttpRequest;
use Mduk\Gowi\Http\Response as HttpResponse;

class NotFoundResponseStage implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 404 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent(
      "404 Not Found\n" .
      $req->getUri()
    );
    return $res;
  }
}
