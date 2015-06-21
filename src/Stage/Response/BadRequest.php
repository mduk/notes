<?php

namespace Mduk\Stage\Response;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
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
