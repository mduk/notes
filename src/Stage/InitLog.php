<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class InitLog implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $log = new Logger( 'name' );
    $log->pushHandler( new StreamHandler( '/tmp/log' ) );
    $app->setService( 'log', $log );
  }

}
