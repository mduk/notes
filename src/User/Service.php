<?php

namespace Mduk\User;

use Mduk\Gowi\Service as ServiceInterface;
use Mduk\Gowi\Service\Request as ServiceRequest;
use Mduk\Gowi\Service\Response as ServiceResponse;
use Mduk\Gowi\Service\Exception as ServiceException;

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

      case 'listAll':
        return $this->listAll( $r );

      case 'getById':
        $user_id = $req->getParameter( 'user_id' );
        return $this->getById( $user_id, $r );

      default:
        throw new \Exception( "unknown call to user service: {$q->getCall()}" );

    }
  }

  protected function listAll( ServiceResponse $r ) {
    return $r->setResults(
      $this->userMapper->load()
    );
  }

  protected function getById( $user_id, ServiceResponse $r ) {
    $collection = $this->userMapper->loadByUserId( $user_id );

    if ( $collection->count() == 0 ) {
      throw new Service\Exception(
        "Invalid User ID: {$user_id}",
        Service\Exception::RESOURCE_NOT_FOUND
      );
    }

    return $r->setResults( $collection );
  }

}

