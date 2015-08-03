<?php

namespace Mduk\Application\Builder;

use Mduk\Application\Builder\ServiceInvocation as ServiceInvocationBuilder;

class Card extends ServiceInvocationBuilder {

  public function buildRoutes( $route, $config ) {
    $routes = [
      $route => [
        'GET' => [
          'builder' => 'card',
          'config' => [
            'service' => $config['service'],
            'http' => [
              'response' => [
                'transcoders' => [
                  'text/html' => "template:{$config['template']}"
                ]
              ]
            ]
          ]
        ]
      ]
    ];
    
    if ( $this->getDebug() ) {
      $this->getLogger()->debug( __CLASS__ . ": Built routes: " . print_r( $routes, true ) );
    }

    return $routes;
  }

}
