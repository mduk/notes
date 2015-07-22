<?php

namespace Mduk\Application;

use Mduk\Application\Stage\InitRouter as InitRouterStage;
use Mduk\Application\Stage\MatchRoute as MatchRouteStage;
use Mduk\Application\Stage\SelectResponseType as SelectResponseTypeStage;
use Mduk\Application\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Application\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Application\Stage\DecodeRequestBody as DecodeRequestBodyStage;

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
