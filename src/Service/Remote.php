<?php

namespace Mduk\Service;

use Mduk\Gowi\Collection\Paged as PagedCollection;
use Mduk\Gowi\Service;
use Mduk\Gowi\Service\Request;
use Mduk\Gowi\Service\Response;

class Remote implements Service {
  protected $endpoint;

  public function __construct( $endpoint ) {
    $this->endpoint = $endpoint;
  }

  public function request( $call ) {
    return new Request( $this, $call );
  }

  public function execute( Request $sReq, Response $sRes ) {
    $remoteRequestBody = json_encode( [
      'call' => $sReq->getCall(),
      'parameters' => $sReq->getParameters(),
      'payload' => $sReq->getPayload()
    ] );

    $client = new \Guzzle\Http\Client;
    $response = $client->post(
      $this->endpoint,
      [],
      $remoteRequestBody
    )->send();
    
    $responseJson = $response->json();
    $sRes->setResults( new PagedCollection( $responseJson ) );

    return $sRes;
  }
}
