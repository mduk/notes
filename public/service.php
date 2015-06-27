<?php

namespace Mduk;

require_once '../vendor/autoload.php';

use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Response\MethodNotAllowed as MethodNotAllowedResponseStage;
use Mduk\Stage\Response\BadRequest as BadRequestStage;
use Mduk\Stage\Respond as RespondStage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
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
// Just telling it what service we want to use and the response type
// ----------------------------------------------------------------------------------------------------
$app->setConfig( 'service', 'calculator' );
$app->setConfig( 'http.response.content_type', 'applicatiion/gowi.service.response+json' );

// ----------------------------------------------------------------------------------------------------
// Bootstrap: Set up the service
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  $calculator = new Calculator;

  $shim = new ServiceShim( 'A simple calculator' );
  $shim->setCall( 'add', [ $calculator, 'add' ], [ 'x', 'y' ],
    "Add two numbers together." );
  $shim->setCall( 'multiply', [ $calculator, 'multiply' ], [ 'x', 'y' ],
    "Multiply two numbers." );

  $app->setService( 'calculator', $shim );
} ) );

// ----------------------------------------------------------------------------------------------------
// Serve Service Description
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  if ( $req->getMethod() == 'GET' ) {
    $serviceName = $app->getConfig( 'service' );
    $description = $app->getService( $serviceName )
      ->describe();

    $h1 = '<h1>Service: ' . $serviceName . '</h1>';
    $callItems = [];
    foreach ( $description['calls'] as $call => $callSpec ) {
      $callItems[] = <<<EOF
<li>
  <h2>{$call}</h1>
  <p>{$callSpec['description']}</p>
</li>
EOF;
    }
    $callList = '<ul>' . implode( '', $callItems ) . '</ul>';
    $body = '<html><body>' . $h1 . $callList . '</body></html>';

    $res->headers->set( 'Content-Type', 'text/html' );
    $res->setContent( $body );
    return $res;
  }
} ) );

// ----------------------------------------------------------------------------------------------------
// Reject invalid requests
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $req, $res ) {
  if ( $req->getMethod() != 'POST' ) {
    return new MethodNotAllowedResponseStage;
  }

  if ( !$req->getContent() ) {
    return new BadRequestStage;
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
  $resultsCollection = $app->getConfig( 'service.result' );
  $resultsArray = [];
  foreach ( $resultsCollection as $result ) {
    $resultsArray[] = $result;
  }
  $app->setConfig( 'http.response.body', json_encode( $resultsArray ) );
} ));

$app->addStage( new RespondStage ); // Send HTTP Response


$app->run()->send();
