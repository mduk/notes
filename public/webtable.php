<?php

namespace Mduk;

use Mduk\Service\Router as RouterService;

use Mduk\Gowi\Application\Stage\Stub as StubStage;

error_reporting( E_ALL );

require_once 'vendor/autoload.php';

$builder = new WebTableApplicationBuilder;
$builder->setPdoConnection( 'sqlite:/Users/daniel/dev/notes/db.sq3' );
$builder->setTable( 'user' );
$builder->setPrimaryKey( 'user_id' );
$builder->setFields( [ 'name', 'email', 'role' ] );
$builder->addBootstrapStage( new StubStage( function( $app, $req, $res ) {
  $app->setService( 'router', new RouterService( $app->getConfig( 'routes' ) ) );
} ) );

$builder->build()
  ->run()
  ->send();
