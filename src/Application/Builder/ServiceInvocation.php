<?php

namespace Mduk\Application\Builder;

class ServiceInvocation {
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
      $app = new \Mduk\Gowi\Http\Application( '.' );
    }

    $app->addStage( new \Mduk\Stage\SelectResponseType );
    $app->addStage( new \Mduk\Stage\InitResponseTranscoder );
    $app->addStage( new \Mduk\Stage\InitPdoServices );
    $app->addStage( new \Mduk\Stage\InitRemoteServices );
    $app->addStage( new \Mduk\Stage\BindServiceRequestParameters );
    $app->addStage( new \Mduk\Stage\ResolveServiceRequest );
    $app->addStage( new \Mduk\Stage\ExecuteServiceRequest );
    $app->addStage( new \Mduk\Stage\Context );
    $app->addStage( new \Mduk\Stage\EncodeServiceResponse );
    $app->addStage( new \Mduk\Stage\Respond );

    $app->applyConfigArray( $config );
    return $app;
  }
}
