<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class ExecuteServiceRequest implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $app->setConfig( 'service.results',
      $app->getConfig( 'service.request' )
        ->execute()
        ->getResults()
    );
  }
}
