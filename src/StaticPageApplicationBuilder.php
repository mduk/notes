<?php

namespace Mduk;

use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Service\Shim as ServiceShim;

class StaticPageApplicationBuilder extends ServiceInvocationApplicationBuilder {
  public function buildRoutes( $route, $config ) {
    return [
      $route => [
        'GET' => [
          'builder' => 'static-page',
          'config' => $config
        ]
      ]
    ];
  }

  public function build( $config, $app = null ) {

    $app = new Gowi\Http\Application( '.' );

    $app->addStage( new StubStage( function( $app, $req, $res ) {

      $renderer = new \Mustache_Engine( [
        'loader' => new \Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../templates' )
      ] );

      $shim = new ServiceShim( 'Mustache template renderer' );
      $shim->setCall( 'render', [ $renderer, 'render' ], [ 'template', '__payload' ],
        "Render a mustache template" );

      $app->setService( 'mustache', $shim );

    } ) );

    $app = parent::build( $config, $app );
    $app->applyConfigArray( [
      'transcoder' => [
        'generic:text' => new Gowi\Transcoder\Generic\Text
      ],
      'service' => [
        'name' => 'mustache',
        'call' => 'render',
        'parameters' => $config,
        'multiplicity' => 'one'
      ],
      'http' => [
        'response' => [
          'transcoders' => [
            'text/html' => 'generic:text'
          ]
        ]
      ]
    ] );

    return $app;
  }
}
