<?php

namespace Mduk;

use Mduk\Stage\BindServiceRequestParameters as BindServiceRequestParametersStage;
use Mduk\Stage\ResolveServiceRequest as ResolveServiceRequestStage;
use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Context as ContextStage;
use Mduk\Stage\EncodeServiceResponse as EncodeServiceResponseStage;
use Mduk\Stage\InitRemoteServices as InitRemoteServicesStage;
use Mduk\Stage\Respond as RespondStage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage\Stub;
use Mduk\Gowi\Factory;

class RoutedServiceApplicationBuilder extends RoutedApplicationBuilder {
  protected $remoteServices = [];

  public function addRemoteService( $service, $url ) {
    $this->remoteServices[ $service ] = $url;
  }

  public function configArray() {
    return array_replace_recursive( parent::configArray(), [
      'remote' => [
        'services' => $this->remoteServices
      ],
    ] );
  }

  public function build() {
    $app = parent::build();

    $app->addStage( new InitRemoteServicesStage ); // Initialise Remote Services
    $app->addStage( new BindServiceRequestParametersStage ); // Bind values from the environment to the Service Request
    $app->addStage( new ResolveServiceRequestStage ); // Resolve Service Request
    $app->addStage( new ExecuteServiceRequestStage ); // Execute Service Request
    $app->addStage( new ContextStage ); // Resolve Context
    $app->addStage( new EncodeServiceResponseStage ); // Encode Service Response
    $app->addStage( new RespondStage ); // Send HTTP Response

    return $app;
  }

}
