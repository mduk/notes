<?php

namespace Mduk\Application;

use Mduk\Stage\InitRouter as InitRouterStage;
use Mduk\Stage\MatchRoute as MatchRouteStage;
use Mduk\Stage\SelectResponseType as SelectResponseTypeStage;
use Mduk\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Stage\DecodeRequestBody as DecodeRequestBodyStage;

use Mduk\Gowi\Factory;

class Builder {

  protected $application;
  protected $builders = [];
  protected $routes = [];

  public function __construct( $app ) {
    $this->application = $app;
  }

  public function setBuilder( $type, $builder ) {
    $this->builders[ $type ] = $builder;
  }

  public function addRoute( $path, $config ) {
    $this->routes[ $path ] = $config;
  }

  public function buildRoute( $type, $path, $config ) {
    $this->routes = array_merge(
      $this->routes,
      $routes = $this->builder( $type )->buildRoutes( $path, $config )
    );
  }

  public function build() {
    $this->application->addStage( new InitRouterStage ); // Initialise the Router Service
    $this->application->addStage( new MatchRouteStage ); // Match a route

    $this->application->setConfig( 'routes', $this->routes );

    return $this->application;
  }

  protected function builder( $name ) {
    if ( !isset( $this->builders[ $name ] ) ) {
      throw new \Exception( "Unknown builder: {$name}" );
    }

    return $this->builders[ $name ];
  }
}
