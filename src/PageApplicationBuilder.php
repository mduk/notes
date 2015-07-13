<?php

namespace Mduk;

class PageApplicationBuilder {
  public function buildRoutes( $path, $config ) {
    return [
      $path => [
        'GET' => [
          'builder' => 'page',
          'config' => [
            'layout' => $config['layout'],
            'regions' => $config['regions']
          ]
        ]
      ]
    ];
  }

  public function build( $config ) {
    $app = new Gowi\Http\Application( '.' );
    $app->applyConfigArray( $config );
    return $app;
  }
}
