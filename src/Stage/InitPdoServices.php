<?php

namespace Mduk\Stage;

use PDO;

use Mduk\Service\Pdo as PdoService;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitPdoServices implements Stage {

  protected $application;
  protected $factories = [];
  protected $connections = [];

  public function execute( Application $app, Request $req, Response $res ) {
    $this->application = $app;
    $this->prepareConnections( $app->getConfig( 'pdo.connections' ) );

    foreach ( $app->getConfig( 'pdo.services', [] ) as $service => $spec ) {
      $app->debugLog( function() use ( $service, $spec ) {
        return __CLASS__ . ": Building Pdo Service: {$service} " . print_r( $spec, true );
      } );
      $app->setService(
        $service,
        new PdoService(
          $this->getConnection( $spec['connection'] ),
          $spec['queries']
        )
      );
    }
  }

  protected function prepareConnections( $connections ) {
    foreach ( $connections as $name => $spec ) {
      $this->application->debugLog( function() use ( $name, $spec ) {
        return __CLASS__ . ": Preparing connection factory for: {$name}. " . print_r( $spec, true );
      } );
      $dsn = $spec['dsn'];
      $username = isset( $spec['username'] ) ? $spec['username'] : null;
      $password = isset( $spec['password'] ) ? $spec['password'] : null;
      $options = isset( $spec['options'] ) ? $spec['options'] : [];

      $this->factories[ $name ] = function() use ( $dsn, $username, $password, $options ) {
        $pdo = new PDO( $dsn, $username, $password, $options );
        $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        return $pdo;
      };
    }
  }

  protected function getConnection( $name ) {
    if ( !isset( $this->connections[ $name ] ) ) {
      $this->connections[ $name ] = $this->factories[ $name ]();
    }

    return $this->connections[ $name ];
  }

}
