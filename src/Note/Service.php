<?php

namespace Mduk\Note;

use Mduk\Service as ServiceInterface;
use Mduk\Service\Request as ServiceRequest;
use Mduk\Service\Response as ServiceResponse;

class Service implements ServiceInterface {

  protected $noteMapper;

  public function __construct( Mapper $mapper ) {
    $this->noteMapper = $mapper;
  }

  public function request( $call ) {
    return new ServiceRequest( $this, $call );
  }

  public function execute( ServiceRequest $q, ServiceResponse $r ) {
    switch ( $q->getCall() ) {

      case 'getByUserId':
        $user_id = $q->getParameter( 'user_id' );
        return $this->getByUserId( $user_id, $r );

    }
  }

  protected function getByUserId( $user_id, ServiceResponse $r ) {
    return $r->setResults( $this->noteMapper->findByUserId( $user_id ) );
  }

}

