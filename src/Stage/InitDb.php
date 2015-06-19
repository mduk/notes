<?php

namespace Mduk\Stage;

use PDO;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitDb implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {
    $dbPath = dirname( __FILE__ ) . '/../db.sq3';
    $initDb = !file_exists( $dbPath );
    $pdo = new PDO( 'sqlite:' . $dbPath ); 
    $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    if ( $initDb ) {
      $pdo->exec( file_get_contents( 'db.sql' ) );
    }

    $app->setService( 'pdo', $pdo );
  }

}
