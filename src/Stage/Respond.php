<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class Respond implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $res->headers->set( 'Content-Type', $app->getConfig( 'response.content_type' ) );
    $res->setContent( $app->getConfig( 'response.body' ) );
    return $res;
  }

}
