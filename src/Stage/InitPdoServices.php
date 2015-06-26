<?php

namespace Mduk\Stage;

use PDO;

use Mduk\Service\Pdo as PdoService;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitPdoServices implements Stage {

  protected $factories = [];
  protected $connections = [];

  public function execute( Application $app, Request $req, Response $res ) {
    $this->prepareConnections( $app->getConfig( 'pdo.connections' ) );

    foreach ( $app->getConfig( 'pdo.services', [] ) as $service => $spec ) {
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
      $dsn = $spec['dsn'];
      $username = isset( $spec['username'] ) ? $spec['username'] : null;
      $password = isset( $spec['password'] ) ? $spec['password'] : null;
      $options = isset( $spec['options'] ) ? $spec['options'] : [];

      $this->factories[ $name ] = function() use ( $dsn, $username, $password, $options ) {
        return new PDO( $dsn, $username, $password, $options );
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
