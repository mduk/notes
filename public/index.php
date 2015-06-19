<?php

namespace Mduk;

require_once 'vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Service\Router\Exception as RouterServiceException;

use Mduk\Stage\ServiceRequest as ServiceRequestStage;
use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Context as ContextStage;
use Mduk\Stage\InitDb as InitDbStage;
use Mduk\Stage\InitLog as InitLogStage;
use Mduk\Stage\Response\NotFound as NotFoundResponseStage;
use Mduk\Stage\Response\NotAcceptable as NotAcceptableResponseStage;
use Mduk\Stage\Response\MethodNotAllowed as MethodNotAllowedResponseStage;

use Mduk\Gowi\Application;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Http\Request as HttpRequest;
use Mduk\Gowi\Http\Response as HttpResponse;
use Mduk\Gowi\Service\Shim as ServiceShim;
use Mduk\Service\Remote as RemoteService;
use Mduk\Gowi\Transcoder;

class MustacheTranscoder implements Transcoder {
  protected $template;
  protected $masseur;

  public function __construct( $template, \Closure $masseur = null ) {
    $this->template = $template;
    $this->masseur = $masseur ?: function( $in ) { return $in; };
  }

  public function encode( $in, array $context = null ) {
    $renderer = new \Mustache_Engine( [
      'loader' => new \Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../templates' )
    ] );

    if ( $context ) {
      foreach ( $context as $key => $request ) {
        $in['context'][ $key ] = $request->execute()->getResults();
      }
    }

    $masseur = $this->masseur;
    $massaged = $masseur( $in );
    return $renderer->render( $this->template, $massaged );
  }

  public function decode( $in ) {
    throw new \Exception( "Can't decode from html" );
  }
}

$app = new Application( dirname( __FILE__ ) );

$app->setConfigArray( [
  'debug' => true,
  'routes' => [

    '/' => [
      'GET' => [
        'service' => 'mustache',
        'call' => 'render',
        'parameters' => [
          'template' => 'index'
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'text/html' => 'generic/text'
          ]
        ]
      ]
    ],

    '/about' => [
      'GET' => [
        'service' => 'mustache',
        'call' => 'render',
        'parameters' => [
          'template' => 'about'
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'text/html' => 'generic/text'
          ]
        ]
      ]
    ],

    '/users' => [
      'GET' => [
        'service' => 'user',
        'call' => 'getAll',
        'response' => [
          'transcoders' => [
            'text/html' => 'html/user_list',
            'application/json' => 'generic/json'
          ]
        ]
      ],
      'POST' => [
        'service' => 'user',
        'call' => 'create',
        'request' => [
          'transcoders' => [
            'application/json' => 'generic/json'
          ]
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/users/{user_id}' => [
      'GET' => [
        'service' => 'user',
        'call' => 'getById',
        'bind' => [
          'route' => [ 'user_id' ]
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'text/html' => 'html/user_page',
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/users/{user_id}/notes' => [
      'GET' => [
        'service' => 'note',
        'call' => 'getByUserId',
        'bind' => [
          'route' => [ 'user_id' ]
        ],
        'context' => [
          'user' => [
            'service' => 'user',
            'call' => 'getById',
            'bind' => [
              'route' => [ 'user_id' ]
            ],
            'multiplicity' => 'one'
          ]
        ],
        'response' => [
          'transcoders' => [
            'text/html' => 'html/note_list',
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/srv/mustache/{template}' => [
      'POST' => [
        'service' => 'mustache',
        'call' => 'render',
        'bind' => [
          'route' => [ 'template' ],
        ],
        'request' => [
          'transcoders' => [
            'application/json' => 'generic/json',
            'application/x-www-form-urlencoded' => 'generic/form'
          ]
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'text/html' => 'generic/text',
            'text/plain' => 'generic/text'
          ]
        ]
      ]
    ],

    '/srv/router' => [
      'GET' => [
        'service' => 'router',
        'call' => 'route',
        'bind' => [
          'query' => [ 'path', 'method' ]
        ],
        'request' => [
          'transcoders' => [
            'application/json' => 'generic/json'
          ],
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'application/json' => 'generic/json',
          ]
        ]
      ]
    ],

    '/calculator/add/{x}/{y}' => [
      'GET' => [
        'service' => 'remote_calculator',
        'call' => 'add',
        'bind' => [
          'route' => [ 'x', 'y' ]
        ],
        'response' => [
          'multiplicity' => 'one',
          'transcoders' => [
            'text/plain' => 'generic/text'
          ]
        ]
      ]
    ]

  ]
] );


$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  error_reporting( E_ALL );
} ) );

// Initialise DB
$app->addStage( new InitDbStage );

// ----------------------------------------------------------------------------------------------------
// Initialise Log
// ----------------------------------------------------------------------------------------------------
$app->addStage( new InitLogStage );

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

  $shim = new ServiceShim;
  $shim->setCall( 'render', [ $renderer, 'render' ], [ 'template', '__payload' ] );
  $app->setService( 'mustache', $shim );

  $app->setService( 'remote_calculator', new RemoteService( 'http://localhost:5556/' ) );

} ) );

// ----------------------------------------------------------------------------------------------------
// Initialise some Transcoder Factories
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {

  $app->setService( 'transcoder', new Factory( [
    'generic/text' => function() {
      return new \Mduk\Gowi\Transcoder\Generic\Text;
    },
    'generic/json' => function() {
      return new \Mduk\Gowi\Transcoder\Generic\Json;
    },
    'generic/form' => function() {
      return new \Mduk\Gowi\Transcoder\Generic\Form;
    },
    'html/user_page' => function() {
      return new MustacheTranscoder( 'user_page' );
    },
    'html/note_list' => function() {
      return new MustacheTranscoder( 'note_list' );
    },
    'html/user_list' => function() {
      return new MustacheTranscoder( 'user_list' );
    }
  ] ) );

} ) );

// ====================================================================================================
//          REQUEST HANDLING
// ====================================================================================================

// ----------------------------------------------------------------------------------------------------
// Match a route
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  try {
    $app->setConfig(
      'active_route',
      $app->getService( 'router' )
        ->request( 'route' )
        ->setParameter( 'path', $req->getPathInfo() )
        ->setParameter( 'method', $req->getMethod() )
        ->execute()
        ->getResults()
        ->shift()
    );
  }
  catch ( RouterServiceException\NotFound $e ) {
    return new NotFoundResponseStage;
  }
  catch ( RouterServiceException\MethodNotAllowed $e ) {
    return new MethodNotAllowedResponseStage;
  }
} ) );

// ----------------------------------------------------------------------------------------------------
// Select Response MIME type
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $supportedTypes = array_keys( $app->getConfig('active_route.config.response.transcoders') );
  $supportedTypes[] = '*/*';
  $acceptedTypes = $req->getAcceptableContentTypes();
  $selectedType = false;

  foreach ( $acceptedTypes as $aType ) {
    if ( in_array( $aType, $supportedTypes ) ) {
      $selectedType = $aType;
      break;
    }
  }

  if ( $selectedType == '*/*' ) {
    $selectedType = array_shift( $supportedTypes );
  }

  if ( !$selectedType ) {
    return new NotAcceptableResponseStage;
  }

  $app->setConfig( 'response.content_type', $selectedType );
} ) );

// ----------------------------------------------------------------------------------------------------
// Select Request Transcoder
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $log = $app->getService('log');
  $routeMethod = $app->getConfig( 'active_route' );

  $content = $req->getContent();
  if ( $content ) {
    $requestContentType = $req->headers->get( 'Content-Type' );
    $requestTranscoders = $app->getConfig( 'active_route.config.request.transcoders' );
    $requestTranscoder = $app->getService( 'transcoder' )
      ->get( $requestTranscoders[ $requestContentType ] );

    $app->setConfig( 'request.content_type', $requestContentType );
    $app->setConfig( 'request.transcoder', $requestTranscoder );
  }

} ) );

// ----------------------------------------------------------------------------------------------------
// Initialise Response Transcoder
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $contentType = $app->getConfig( 'response.content_type' );
  $transcoders = $app->getConfig( 'active_route.config.response.transcoders' );

  $transcoder = $app->getService( 'transcoder' )
    ->get( $transcoders[ $contentType ] );

  $app->setConfig( 'response.transcoder', $transcoder );
} ) );

// ----------------------------------------------------------------------------------------------------
// Decode HTTP Request body
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $content = $req->getContent();
  if ( $content ) {
    $transcoder = $app->getConfig('request.transcoder');
    $payload = $transcoder->decode( $content );
    $app->setConfig( 'request.payload', $payload );
  }
} ));

// ----------------------------------------------------------------------------------------------------
// Execute Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new ServiceRequestStage );
$app->addStage( new ExecuteServiceRequestStage );

// ----------------------------------------------------------------------------------------------------
// Resolve Context
// ----------------------------------------------------------------------------------------------------
$app->addStage( new ContextStage );

#$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
#  echo '<pre>';
#  print_r( $app->getConfigArray() );
#  exit;
#} ) );

// ----------------------------------------------------------------------------------------------------
// Encode Service Response
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $transcoder = $app->getConfig('response.transcoder');
  $multiplicity = $app->getConfig('active_route.config.response.multiplicity', 'many' );
  $encode = $app->getConfig('service.results');
  $context = $app->getConfig('context', []);

  if ( $multiplicity == 'one' ) {
    $encode = $encode->shift();
  }
  else {
    $page = (int) $req->query->get( 'page', 1 );
    $encode = [
      'total' => $encode->count(),
      'page' => $page,
      'pages' => $encode->numPages(),
      'objects' => $encode->page( $page )->getAll()
    ];
  }

  $app->setConfig( 'response.body', $transcoder->encode( $encode, $context ) );
} ) );

// ----------------------------------------------------------------------------------------------------
// Send HTTP Response
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $res->headers->set( 'Content-Type', $app->getConfig( 'response.content_type' ) );
  $res->setContent( $app->getConfig( 'response.body' ) );
  return $res;
} ));

$app->run()->send();
