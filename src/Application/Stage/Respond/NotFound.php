<?php

namespace Mduk\Application\Stage\Respond;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class NotFound implements Stage {
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
