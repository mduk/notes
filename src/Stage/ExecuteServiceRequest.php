<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class ExecuteServiceRequest implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $multiplicity = $app->getConfig( 'service.multiplicity', 'many' );

    $result = $app->getConfig( 'service.request' )
      ->execute()
      ->getResults();

    if ( $multiplicity == 'one' ) {
      $result = $result->shift();
    }

    $app->setConfig( 'service.result', $result );
  }
}
