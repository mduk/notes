<?php

namespace Mduk\Service;

use Mduk\Collection;

class Response {

  protected $query;
  protected $results;

  public function __construct( Query $q ) {
    $this->query = $q;
  }

  public function setResults( Collection $c ) {
    $this->results = $c;
    return $this;
  }

  public function addResult( $o ) {
    $this->results[] = $o;
    return $this;
  }

  public function getResults() {
    return $this->results;
  }

  public function getQuery() {
    return $this->query;
  }
}

