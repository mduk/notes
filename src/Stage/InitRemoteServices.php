<?php

namespace Mduk\Stage;

use Mduk\Service\Remote as RemoteService;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitRemoteServices implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    foreach ( $app->getConfig( 'remote.services', [] ) as $service => $url ) {
      $app->setService( $service, new RemoteService( $url ) );
    }
  }

}
