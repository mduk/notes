<?php

namespace Mduk\User;

use Mduk\Service as ServiceInterface;
use Mduk\Service\Request as ServiceRequest;
use Mduk\Service\Response as ServiceResponse;
use Mduk\Service\Exception as ServiceException;

class Service implements ServiceInterface {

  protected $userMapper;

  public function __construct( Mapper $mapper ) {
    $this->userMapper = $mapper;
  }

  public function request( $call ) {
    return new ServiceRequest( $this, $call );
  }

  public function execute( ServiceRequest $req, ServiceResponse $r ) {
    switch ( $req->getCall() ) {

      case 'getById':
        $user_id = $req->getParameter( 'user_id' );
        return $this->getById( $user_id, $r );

      default:
        throw new \Exception( "unknown call to user service: {$q->getCall()}" );

    }
  }

  protected function getById( $user_id, ServiceResponse $r ) {
    $collection = $this->userMapper->findByUserId( $user_id );

    if ( $collection->count() == 0 ) {
      throw new Service\Exception(
        "Invalid User ID: {$user_id}",
        Service\Exception::RESOURCE_NOT_FOUND
      );
    }

    return $r->setResults( $collection );
  }

}

