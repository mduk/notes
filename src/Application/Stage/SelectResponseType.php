<?php

namespace Mduk\Application\Stage;

use Mduk\Application\Stage\Respond\NotAcceptable as NotAcceptableResponseStage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class SelectResponseType implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    try {
      $supportedTypes = array_keys( $app->getConfig( 'http.response.transcoders' ) );
    }
    catch ( Application\Exception $e ) {
      $app->debugLog( function() {
        return __CLASS__ . ': No http.response.transcoders config key. Assuming there will be no response.';
      } );

      return null;
    }

    $supportedTypes[] = '*/*';
    $acceptedTypes = $req->getAcceptableContentTypes();
    $selectedType = false;

    foreach ( $acceptedTypes as $aType ) {
      if ( in_array( $aType, $supportedTypes ) ) {
        $selectedType = $aType;
        break;
      }
    }

    if ( $selectedType == '*/*' ) {
      $selectedType = array_shift( $supportedTypes );
    }

    $app->debugLog( function() use ( $selectedType ) {
      return __CLASS__ . ': Selected response type: ' . $selectedType;
    } );

    if ( !$selectedType ) {
      return new NotAcceptableResponseStage;
    }

    $app->setConfig( 'http.response.content_type', $selectedType );
  }

}
