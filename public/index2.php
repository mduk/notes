<?php

namespace Mduk;

require_once 'vendor/autoload.php';

use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Response\MethodNotAllowed as MethodNotAllowedResponseStage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Service\Shim as ServiceShim;

class Calculator {
  public function add( $x, $y ) {
    return $x + $y;
  }

  public function multiply( $x, $y ) {
    return $x * $y;
  }
}

$app = new Application( dirname( __FILE__ ) );

// ----------------------------------------------------------------------------------------------------
// Configure App
//
// Just telling it what service we want to use
// ----------------------------------------------------------------------------------------------------
$app->setConfig( 'service', 'calculator' );

// ----------------------------------------------------------------------------------------------------
// Bootstrap: Set up the service
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  $calculator = new Calculator;

  $shim = new ServiceShim;
  $shim->setCall( 'add', [ $calculator, 'add' ], [ 'x', 'y' ] );
  $shim->setCall( 'multiply', [ $calculator, 'multiply' ], [ 'x', 'y' ] );
  $app->setService( 'calculator', $shim );
} ) );

// ----------------------------------------------------------------------------------------------------
// Reject invalid requests
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  if ( $req->getMethod() != 'POST' ) {
    return new MethodNotAllowedResponseStage;
  }

  if ( !$req->getContent() ) {
    return $res->error()->text( 'Bad Request' );
  }
} ) );

// ----------------------------------------------------------------------------------------------------
// Map the HTTP Request Body to a Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  $requestJson = json_decode( $req->getContent() );
  $app->setConfig( 'service.request',
    $app->getService( $app->getConfig( 'service' ) )
      ->request( $requestJson->call )
      ->setParameters( (array) $requestJson->parameters )
      ->setPayload( $requestJson->payload )
  );
} ) );

// ----------------------------------------------------------------------------------------------------
// Execute Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new ExecuteServiceRequestStage );

if ( false ) {
  $app->addStage( new StubStage( function( $app, $req, $res ) {
    echo print_r( $app->getConfigArray(), true );
    exit;
  } ) );
}

// ----------------------------------------------------------------------------------------------------
// Encode and return Service Response Results
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  $resultsCollection = $app->getConfig( 'service.results' );
  $resultsArray = [];
  foreach ( $resultsCollection as $result ) {
    $resultsArray[] = $result;
  }
  $res->setContent( json_encode( $resultsArray ) );
  return $res;
} ));


$app->run()->send();
