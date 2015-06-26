<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class Respond implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    try {
      $res->setContent( $app->getConfig( 'http.response.body' ) );
      $res->headers->set( 'Content-Type', $app->getConfig( 'http.response.content_type' ) );
    }
    catch ( Application\Exception $e ) {
      if ( $e->getCode() == Application\Exception::INVALID_CONFIG_KEY ) {
        $res->setContent( '' );
      }
      else {
        throw $e;
      }
    }

    return $res;
  }

}
