<?php

namespace Mduk\Stage;

use Mduk\Stage\Response\NotFound as NotFoundResponseStage;
use Mduk\Stage\Response\InternalServerError as InternalServerErrorResponseStage;

use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class ExecuteServiceRequest implements Stage {
  public function execute( Application $app, Request $req, Response $res ) {
    $multiplicity = $app->getConfig( 'service.multiplicity', 'many' );

    $result = $app->getConfig( 'service.request' )
      ->execute()
      ->getResults();

    switch ( $multiplicity ) {
      case 'none':
        if ( $result->shift() !== null ) {
          return new InternalServerErrorResponseStage( 'Multiplicity mismatch.' );
        }
        break;

      case 'one':
        $result = $result->shift();

        if ( $result === null ) {
          return new NotFoundResponseStage;
        }

        $app->setConfig( 'service.result', $result );
        break;

      case 'many':
        $app->setConfig( 'service.result', $result );
        break;

      case 'none':
        break;

      default:
          return new InternalServerErrorResponseStage( "Unknown multiplicity: {$multiplicity}" );
    }

  }
}
