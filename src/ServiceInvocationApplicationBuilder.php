<?php

namespace Mduk;

class ServiceInvocationApplicationBuilder {
  public function buildRoutes( $methodPath, $config ) {
    return [
      $methodPath[1] => [
        $methodPath[0] => [
          'builder' => 'service-invocation',
          'config' => $config
        ]
      ]
    ];
  }

  public function build( $config, $app = null ) {
    if ( !$app ) {
      $app = new Gowi\Http\Application( '.' );
    }

    $app->addStage( new Stage\SelectResponseType );
    $app->addStage( new Stage\InitResponseTranscoder );
    $app->addStage( new Stage\InitPdoServices );
    $app->addStage( new Stage\InitRemoteServices );
    $app->addStage( new Stage\BindServiceRequestParameters );
    $app->addStage( new Stage\ResolveServiceRequest );
    $app->addStage( new Stage\ExecuteServiceRequest );
    $app->addStage( new Stage\Context );
    $app->addStage( new Stage\EncodeServiceResponse );
    $app->addStage( new Stage\Respond );

    $app->applyConfigArray( $config );
    return $app;
  }
}
