<?php

namespace Mduk;

use Mduk\Gowi\Http\Application as App;
use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;

require '../vendor/autoload.php';

/*
  Use case: Static Routing

  Like most typical frameworks, all the routing information is baked into the application via configuration.

  This requires a stage to Initialise the Router Service prior to routing.
*/

$app = new App( __DIR__ );
$app->setConfig( 'debug', true );


$builder = new ApplicationBuilder( $app );

$builder->setBuilder( 'service-invocation', new ServiceInvocationApplicationBuilder );
$builder->setBuilder( 'webtable', new WebTableApplicationBuilder );
$builder->setBuilder( 'page', new PageApplicationBuilder );

$builder->buildRoute( 'webtable', '/api/tables/user', [
  'connection' => [
    'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
  ],
  'table' => 'user',
  'pk' => 'user_id',
  'fields' => [ 'name', 'email', 'role' ]
] );

$builder->buildRoute( 'page', '/about-us', [
  'layout' => 'single-column',
  'regions' => [
    'main' => [
      'cards' => [ 'about-our-origin', 'about-our-values', 'about-our-aquisition', 'work-for-us' ]
    ]
  ]
] );

$builder->buildRoute( 'service-invocation', [ 'GET', '/users/{user_id}/updates' ], [
  'service' => [
    'name' => 'user-updates',
    'call' => 'getMostRecent',
    'multiplicity' => 'many'
  ],
  'bind' => [
    'route' => [ 'user_id' ]
  ],
  'http' => [
    'response' => [
      'text/html' => 'html:user_updates',
      'application/rss+xml' => 'rss:user_updates'
    ]
  ]
] );

$app = $builder->build();

$app->addStage( new StubStage( function( $app, $req, $res ) {
  return $res->ok()->html( '<h1>OK</h1><pre>' . print_r( $app->getConfigArray(), true ) . '</pre>' );
} ) );

$app->run()->send();
