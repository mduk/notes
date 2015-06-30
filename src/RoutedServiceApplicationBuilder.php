<?php

namespace Mduk;

use Mduk\Stage\BindServiceRequestParameters as BindServiceRequestParametersStage;
use Mduk\Stage\ResolveServiceRequest as ResolveServiceRequestStage;
use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Context as ContextStage;
use Mduk\Stage\DecodeRequestBody as DecodeRequestBodyStage;
use Mduk\Stage\EncodeServiceResponse as EncodeServiceResponseStage;
use Mduk\Stage\InitErrorHandler as InitErrorHandlerStage;
use Mduk\Stage\InitPdoServices as InitPdoServicesStage;
use Mduk\Stage\InitRemoteServices as InitRemoteServicesStage;
use Mduk\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Stage\InitRouter as InitRouterStage;
use Mduk\Stage\MatchRoute as MatchRouteStage;
use Mduk\Stage\Respond as RespondStage;
use Mduk\Stage\Response\NotAcceptable as NotAcceptableResponseStage;
use Mduk\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Stage\SelectResponseType as SelectResponseTypeStage;

use Mduk\Gowi\Http\Application\Stage\Stub;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Factory;

class RoutedServiceApplicationBuilder {
  protected $routes = [];
  protected $transcoderFactory;
  protected $bootstrapStages = [];
  protected $remoteServices = [];
  protected $pdoConnections = [];
  protected $pdoServices = [];

  public function useTranscoderFactory( Factory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function addRemoteService( $service, $url ) {
    $this->remoteServices[ $service ] = $url;
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
      'remote' => [
        'services' => $this->remoteServices
      ],
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
    $app->addStage( new InitRemoteServicesStage ); // Initialise Remote Services
    $app->addStage( new InitPdoServicesStage ); // Initialise PDO Services
    $app->addStage( new BindServiceRequestParametersStage ); // Bind values from the environment to the Service Request
    $app->addStage( new ResolveServiceRequestStage ); // Resolve Service Request
    $app->addStage( new ExecuteServiceRequestStage ); // Execute Service Request
    $app->addStage( new ContextStage ); // Resolve Context
    $app->addStage( new EncodeServiceResponseStage ); // Encode Service Response
    $app->addStage( new RespondStage ); // Send HTTP Response

    return $app;
  }
}
