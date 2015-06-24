<?php

namespace Mduk\Service;

use PDO as PhpDataObject;

use Mduk\Gowi\Service;
use Mduk\Gowi\Service\Request;
use Mduk\Gowi\Service\Response;

class Pdo implements Service {

  protected $pdo;
  protected $queries = [];

  public function __construct( PhpDataObject $pdo, array $queries = [] ) {
    $this->pdo = $pdo;
    $this->queries = $queries;
  }

  public function request( $call ) {
    if ( !isset( $this->queries[ $call ] ) ) {
      throw new \Exception( "Unknown Service Request call: {$call}" );
    }

    $query = $this->queries[ $call ];
    $required = isset( $query['required'] ) ? $query['required'] : [];
    return new Request( $this, $call, $required );
  }

  public function execute( Request $req, Response $res ) {
    $sql = $this->queries[ $req->getCall() ]['sql'];
    $stmt = $this->pdo->prepare( $sql );
    foreach ( $req->getParameters() as $k => $v ) {
      $stmt->bindValue( ":{$k}", $v );
    }
    $results = $stmt->execute();

    while ( $obj = $stmt->fetchObject() ) {
      $res->addResult( $obj );
    }

    return $res;
  }

}
