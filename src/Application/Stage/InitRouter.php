<?php

namespace Mduk\Application\Stage;

use Mduk\Service\Router as RouterService;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitRouter implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $app->setService( 'router', new RouterService( $app->getConfig( 'routes' ) ) );
  }

}
