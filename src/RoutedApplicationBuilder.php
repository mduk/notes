<?php

namespace Mduk;

use Mduk\Stage\DecodeRequestBody as DecodeRequestBodyStage;
use Mduk\Stage\InitErrorHandler as InitErrorHandlerStage;
use Mduk\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Stage\InitPdoServices as InitPdoServicesStage;
use Mduk\Stage\InitRouter as InitRouterStage;
use Mduk\Stage\MatchRoute as MatchRouteStage;
use Mduk\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Stage\SelectResponseType as SelectResponseTypeStage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage\Stub;
use Mduk\Gowi\Factory;

class RoutedApplicationBuilder {

  protected $routes = [];
  protected $useApiProblemErrorHandler = false;
  protected $transcoderFactory;
  protected $bootstrapStages = [];
  protected $pdoConnections = [];
  protected $pdoServices = [];

  public function useApiProblemErrorHandler( $u = true) {
    $this->useApiProblemErrorHandler = $u;
  }
  public function useTranscoderFactory( Factory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function addBootstrapStage( $stage ) {
    $this->bootstrapStages[] = $stage;
  }

  public function addPdoConnection( $name, $dsn, $username = null, $password = null, $options = [] ) {
    $this->pdoConnections[ $name ] = [
      'dsn' => $dsn,
      'username' => $username,
      'password' => $password,
      'options' => $options
    ];
  }

  public function addPdoService( $name, $connectionName, $queries ) {
    $this->pdoServices[ $name ] = [
      'connection' => $connectionName,
      'queries' => $queries
    ];
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
      'pdo' => [
        'connections' => $this->pdoConnections,
        'services' => $this->pdoServices
      ],
      'routes' => $this->routes
    ];
  }

  public function build() {
    $app = new Application( dirname( __FILE__ ) );

    $app->setConfigArray( $this->configArray() );

    // Level 1: Basic setup
    if ( $this->useApiProblemErrorHandler ) {
      $app->addStage( new InitErrorHandlerStage ); // Initialise Error Handler
    }
    $app->addStage( new InitRouterStage ); // Initialise Router Service

    foreach ( $this->bootstrapStages as $stage ) {
      $app->addStage( $stage );
    }

    // Level 2: Request Validation
    $app->addStage( new MatchRouteStage ); // Match a route
    $app->addStage( new SelectResponseTypeStage ); // Select Response MIME type
    $app->addStage( new SelectRequestTranscoderStage ); // Select Request Transcoder
    $app->addStage( new InitResponseTranscoderStage ); // Initialise Response Transcoder
    $app->addStage( new DecodeRequestBodyStage ); // Decode HTTP Request body

    // Level 3: Domain Initialisation
    $app->addStage( new InitPdoServicesStage ); // Initialise PDO Services

    return $app;
  }
}
