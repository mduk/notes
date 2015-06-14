<?php

namespace Mduk\Note;

use Mduk\Gowi\Service as ServiceInterface;
use Mduk\Gowi\Service\Request as ServiceRequest;
use Mduk\Gowi\Service\Response as ServiceResponse;

class Service implements ServiceInterface {

  protected $noteMapper;
  protected $requiredParameters = [
    'getByUserId' => [ 'user_id' ]
  ];

  public function __construct( Mapper $mapper ) {
    $this->noteMapper = $mapper;
  }

  public function request( $call ) {
    return new ServiceRequest( $this, $call, $this->requiredParameters[ $call ] );
  }

  public function execute( ServiceRequest $req, ServiceResponse $res ) {
    switch ( $req->getCall() ) {

      case 'getByUserId':
        $user_id = $req->getParameter( 'user_id' );
        return $this->getByUserId( $user_id, $res );

      default:
        throw new \Exception( "unknown call to user service: {$req->getCall()}" );
    }
  }

  protected function getByUserId( $user_id, ServiceResponse $r ) {
    return $r->setResults( $this->noteMapper->loadByUserId( $user_id ) );
  }

}

