<?php

namespace Mduk\Application\Stage\Response;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class BadRequest implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 400 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent(
      "400 Bad Request"
    );
    return $res;
  }
}
