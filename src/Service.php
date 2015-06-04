<?php

namespace Mduk;

use Mduk\Service\Request as ServiceRequest;
use Mduk\Service\Response as ServiceResponse;

interface Service {
  public function request( $call );
  public function execute( ServiceRequest $q, ServiceResponse $r);
}

