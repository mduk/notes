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

    $app->addStage( new \Mduk\Application\Stage\SelectResponseType );
    $app->addStage( new \Mduk\Application\Stage\InitResponseTranscoder );
    $app->addStage( new \Mduk\Application\Stage\InitPdoServices );
    $app->addStage( new \Mduk\Application\Stage\InitRemoteServices );
    $app->addStage( new \Mduk\Application\Stage\BindServiceRequestParameters );
    $app->addStage( new \Mduk\Application\Stage\ResolveServiceRequest );
    $app->addStage( new \Mduk\Application\Stage\ExecuteServiceRequest );
    $app->addStage( new \Mduk\Application\Stage\Context );
    $app->addStage( new \Mduk\Application\Stage\EncodeServiceResponse );
    $app->addStage( new \Mduk\Application\Stage\Respond );

    $app->applyConfigArray( $config );
    return $app;
  }
}
