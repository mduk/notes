<?php

namespace Mduk;

use Mduk\Stage\DecodeRequestBody as DecodeRequestBodyStage;
use Mduk\Stage\InitErrorHandler as InitErrorHandlerStage;
use Mduk\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Stage\InitRouter as InitRouterStage;
use Mduk\Stage\MatchRoute as MatchRouteStage;
use Mduk\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Stage\SelectResponseType as SelectResponseTypeStage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage\Stub;
use Mduk\Gowi\Factory;

class RoutedApplicationBuilder {

  protected $routes = [];
  protected $transcoderFactory;
  protected $bootstrapStages = [];

  public function useTranscoderFactory( Factory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function addBootstrapStage( $stage ) {
    $this->bootstrapStages[] = $stage;
  }

  public function addStaticPage( $path, $template ) {
    $this->routes[ $path ] = [
      'GET' => [
        'service' => [
          'name' => 'mustache',
          'call' => 'render',
          'multiplicity' => 'one',
          'parameters' => [
            'template' => $template
          ]
        ],
        'http' => [
          'response' => [
            'transcoders' => [
              'text/html' => 'generic:text'
            ]
          ]
        ]
      ]
    ];
  }

  public function addRoute( $path, $routeConfig ) {
    $this->routes[ $path ] = $routeConfig;
  }

  public function configArray() {
    return [
      'debug' => true,
      'transcoder' => $this->transcoderFactory,
      'routes' => $this->routes
    ];
  }

  public function build() {
    $app = new Application( dirname( __FILE__ ) );

    $app->setConfigArray( $this->configArray() );

    $app->addStage( new InitErrorHandlerStage ); // Initialise Error Handler
    $app->addStage( new InitRouterStage ); // Initialise Router Service

    foreach ( $this->bootstrapStages as $stage ) {
      $app->addStage( $stage );
    }

    $app->addStage( new MatchRouteStage ); // Match a route
    $app->addStage( new SelectResponseTypeStage ); // Select Response MIME type
    $app->addStage( new SelectRequestTranscoderStage ); // Select Request Transcoder
    $app->addStage( new InitResponseTranscoderStage ); // Initialise Response Transcoder
    $app->addStage( new DecodeRequestBodyStage ); // Decode HTTP Request body

    return $app;
  }
}
