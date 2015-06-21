<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class EncodeServiceResponse implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $transcoder = $app->getConfig('response.transcoder');
    $multiplicity = $app->getConfig('response.multiplicity', 'many' );
    $encode = $app->getConfig('service.results');
    $context = $app->getConfig('context', []);

    if ( $multiplicity == 'one' ) {
      $encode = $encode->shift();
    }
    else {
      $page = (int) $req->query->get( 'page', 1 );
      $encode = [
        'total' => $encode->count(),
        'page' => $page,
        'pages' => $encode->numPages(),
        'objects' => $encode->page( $page )->getAll()
      ];
    }

    $app->setConfig( 'response.body', $transcoder->encode( $encode, $context ) );
  }

}
