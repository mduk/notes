<?php

namespace Mduk\Application\Stage\Respond;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InternalServerError implements Stage {
  public function __construct( $message ) {
    $this->message = $message;
  }

  public function execute( Application $app, Request $req, Response $res ) {
    $res->setStatusCode( 500 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent(
      "500 Internal Server Error\n" .
      $this->message
    );
    return $res;
  }
}
