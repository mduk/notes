<?php

namespace Mduk\Application\Stage\Respond;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class MethodNotAllowed implements Stage {
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
