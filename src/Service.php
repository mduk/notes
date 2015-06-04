<?php

namespace Mduk;

use Mduk\Service\Query as ServiceQuery;
use Mduk\Service\Response as ServiceResponse;

interface Service {
  public function query( $call );
  public function execute( ServiceQuery $q, ServiceResponse $r);
}

