<?php

namespace Mduk\Stage\Response;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

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
