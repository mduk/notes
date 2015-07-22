<?php

namespace Mduk\Application\Stage\Response;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class UnsupportedMediaType implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 415 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent(
      "415 Unsupported Media Type\n" .
      $req->headers->get( 'Content-Type' )
    );
    return $res;
  }
}
