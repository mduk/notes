<?php

namespace Mduk\Stage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Collection\Paged as PagedCollection;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class EncodeServiceResponse implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $transcoder = $app->getConfig('http.response.transcoder');
    $encode = $app->getConfig('service.result');
    $context = $app->getConfig('context', []);

    if ( $encode instanceof PagedCollection ) {
      $page = (int) $req->query->get( 'page', 1 );
      $encode = [
        'total' => $encode->count(),
        'page' => $page,
        'pages' => $encode->numPages(),
        'objects' => $encode->page( $page )->getAll()
      ];
    }

    $app->setConfig( 'http.response.body', $transcoder->encode( $encode, $context ) );
  }

}
