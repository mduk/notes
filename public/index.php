<?php

namespace Mduk;

error_reporting( E_ALL );

require_once 'vendor/autoload.php';

use Mduk\Transcoder\Mustache as MustacheTranscoder;

use Mduk\Service\Remote as RemoteService;
use Mduk\Service\Router as RouterService;

use Mduk\Stage\BindServiceRequestParameters as BindServiceRequestParametersStage;
use Mduk\Stage\ResolveServiceRequest as ResolveServiceRequestStage;
use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Context as ContextStage;
use Mduk\Stage\DecodeRequestBody as DecodeRequestBodyStage;
use Mduk\Stage\EncodeServiceResponse as EncodeServiceResponseStage;
use Mduk\Stage\InitDb as InitDbStage;
use Mduk\Stage\InitLog as InitLogStage;
use Mduk\Stage\InitResponseTranscoder as InitResponseTranscoderStage;
use Mduk\Stage\MatchRoute as MatchRouteStage;
use Mduk\Stage\Respond as RespondStage;
use Mduk\Stage\Response\NotAcceptable as NotAcceptableResponseStage;
use Mduk\Stage\SelectRequestTranscoder as SelectRequestTranscoderStage;
use Mduk\Stage\SelectResponseType as SelectResponseTypeStage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Http\Request as HttpRequest;
use Mduk\Gowi\Http\Response as HttpResponse;
use Mduk\Gowi\Service\Shim as ServiceShim;

$templatesDir = dirname( __FILE__ ) . '/../templates';
$transcoderFactory = new Factory( [
  'generic:text' => function() {
    return new \Mduk\Gowi\Transcoder\Generic\Text;
  },
  'generic:json' => function() {
    return new \Mduk\Gowi\Transcoder\Generic\Json;
  },
  'generic:form' => function() {
    return new \Mduk\Gowi\Transcoder\Generic\Form;
  },
  'html:user_page' => function() use ( $templatesDir ) {
    return new MustacheTranscoder( "{$templatesDir}/user_page.mustache" );
  },
  'html:note_list' => function() use ( $templatesDir ) {
    return new MustacheTranscoder( "{$templatesDir}/note_list.mustache" );
  },
  'html:user_list' => function() use ( $templatesDir ) {
    return new MustacheTranscoder( "{$templatesDir}/user_list.mustache" );
  }
] );

class RoutedApplicationConfig {
  protected $routes = [];
  protected $transcoderFactory;

  public function useTranscoderFactory( $factory ) {
    $this->transcoderFactory = $factory;
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

  public function toArray() {
    return [
      'debug' => true,
      'transcoder' => $this->transcoderFactory,
      'routes' => $this->routes
    ];
  }
}

$config = new RoutedApplicationConfig;
$config->useTranscoderFactory( $transcoderFactory );
$config->addStaticPage( '/', 'index' );
$config->addStaticPage( '/about', 'about' );
$config->addRoute( '/users', [
  'GET' => [
    'service' => [
      'name' => 'user',
      'call' => 'getAll'
    ],
    'http' => [
      'response' => [
        'transcoders' => [
          'text/html' => 'html:user_list',
          'application/json' => 'generic:json'
        ]
      ]
    ]
  ],
  'POST' => [
    'service' => [
      'name' => 'user',
      'call' => 'create',
      'multiplicity' => 'one',
    ],
    'http' => [
      'request' => [
        'transcoders' => [
          'application/json' => 'generic:json'
        ]
      ],
      'response' => [
        'transcoders' => [
          'application/json' => 'generic:json'
        ]
      ]
    ]
  ]
] );
$config->addRoute( '/users/{user_id}', [
  'GET' => [
    'service' => [
      'name' => 'user',
      'call' => 'getById',
      'multiplicity' => 'one',
    ],
    'bind' => [
      'route' => [ 'user_id' ]
    ],
    'http' => [
      'response' => [
        'transcoders' => [
          'text/html' => 'html:user_page',
          'application/json' => 'generic:json'
        ]
      ]
    ]
  ]
] );
$config->addRoute( '/users/{user_id}/notes', [
  'GET' => [
    'service' => [
      'name' => 'note',
      'call' => 'getByUserId'
    ],
    'bind' => [
      'route' => [ 'user_id' ]
    ],
    'context' => [
      'user' => [
        'service' => [
          'name' =>'user',
          'call' => 'getById',
        ],
      ]
    ],
    'http' => [
      'response' => [
        'transcoders' => [
          'text/html' => 'html:note_list',
          'application/json' => 'generic:json'
        ]
      ]
    ]
  ]
] );
$config->addRoute( '/srv/mustache/{template}', [
  'POST' => [
    'service' => [
      'name' => 'mustache',
      'call' => 'render',
      'multiplicity' => 'one',
    ],
    'bind' => [
      'route' => [ 'template' ],
    ],
    'http' => [
      'request' => [
        'transcoders' => [
          'application/json' => 'generic:json',
          'application/x-www-form-urlencoded' => 'generic:form'
        ]
      ],
      'response' => [
        'transcoders' => [
          'text/html' => 'generic:text',
          'text/plain' => 'generic:text'
        ]
      ]
    ]
  ]
] );
$config->addRoute( '/srv/router', [
  'GET' => [
    'service' => [
      'name' => 'router',
      'call' => 'route',
      'multiplicity' => 'one',
    ],
    'bind' => [
      'query' => [ 'path', 'method' ]
    ],
    'http' => [
      'response' => [
        'transcoders' => [
          'application/json' => 'generic:json',
        ]
      ]
    ]
  ]
] );
$config->addRoute( '/srv/calculator', [
  'GET' => [
    'service' => [
      'name' => 'remote_calculator',
      'call' => 'add',
      'multiplicity' => 'one',
    ],
    'bind' => [
      'route' => [ 'x', 'y' ]
    ],
    'http' => [
      'response' => [
        'transcoders' => [
          'text/plain' => 'generic:text'
        ]
      ]
    ]
  ]
] );

$app = new Application( dirname( __FILE__ ) );
$app->setConfigArray( $config->toArray() );

$app->addStage( new InitLogStage ); // Initialise Log
$app->addStage( new InitDbStage ); // Initialise DB

// ----------------------------------------------------------------------------------------------------
// Initialise Some Services
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {

  $app->setService( 'router', new RouterService( $app->getConfig( 'routes' ) ) );

  $pdo = $app->getService( 'pdo' );
  $mapperFactory = new Mapper\Factory( $pdo );
  $app->setService( 'user', new User\Service( $mapperFactory->get( '\\Mduk\\User\\Mapper' ), $pdo ) );
  $app->setService( 'note', new Note\Service( $mapperFactory->get( '\\Mduk\\Note\\Mapper' ) ) );

  $renderer = new \Mustache_Engine( [
    'loader' => new \Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../templates' )
  ] );

  $shim = new ServiceShim( 'Mustache template renderer' );
  $shim->setCall( 'render', [ $renderer, 'render' ], [ 'template', '__payload' ],
    "Render a mustache template" );
  $app->setService( 'mustache', $shim );

  $app->setService( 'remote_calculator', new RemoteService( 'http://localhost:5556/' ) );

} ) );

// ====================================================================================================
//          REQUEST HANDLING
// ====================================================================================================

$app->addStage( new MatchRouteStage ); // Match a route
$app->addStage( new SelectResponseTypeStage ); // Select Response MIME type
$app->addStage( new SelectRequestTranscoderStage ); // Select Request Transcoder
$app->addStage( new InitResponseTranscoderStage ); // Initialise Response Transcoder
$app->addStage( new DecodeRequestBodyStage ); // Decode HTTP Request body
$app->addStage( new BindServiceRequestParametersStage ); // Bind values from the environment to the Service Request
$app->addStage( new ResolveServiceRequestStage ); // Resolve Service Request
$app->addStage( new ExecuteServiceRequestStage ); // Execute Service Request
$app->addStage( new ContextStage ); // Resolve Context
$app->addStage( new EncodeServiceResponseStage ); // Encode Service Response
$app->addStage( new RespondStage ); // Send HTTP Response

$app->run()->send();
