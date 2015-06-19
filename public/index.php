<?php

namespace Mduk;

require_once 'vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Service\Router\Exception as RouterServiceException;

use Mduk\Stage\ServiceRequest as ServiceRequestStage;
use Mduk\Stage\ExecuteServiceRequest as ExecuteServiceRequestStage;
use Mduk\Stage\Context as ContextStage;
use Mduk\Stage\DecodeRequestBody as DecodeRequestBodyStage;
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


// ----------------------------------------------------------------------------------------------------
// Error Reporting
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  error_reporting( E_ALL );
} ) );

// ----------------------------------------------------------------------------------------------------
// Initialise DB
// ----------------------------------------------------------------------------------------------------
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
$app->addStage( new MatchRouteStage );

// ----------------------------------------------------------------------------------------------------
// Select Response MIME type
// ----------------------------------------------------------------------------------------------------
$app->addStage( new SelectResponseTypeStage );

// ----------------------------------------------------------------------------------------------------
// Select Request Transcoder
// ----------------------------------------------------------------------------------------------------
$app->addStage( new SelectRequestTranscoderStage );

// ----------------------------------------------------------------------------------------------------
// Initialise Response Transcoder
// ----------------------------------------------------------------------------------------------------
$app->addStage( new InitResponseTranscoderStage );

// ----------------------------------------------------------------------------------------------------
// Decode HTTP Request body
// ----------------------------------------------------------------------------------------------------
$app->addStage( new DecodeRequestBodyStage );

// ----------------------------------------------------------------------------------------------------
// Resolve Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new ServiceRequestStage );

// ----------------------------------------------------------------------------------------------------
// Execute Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new ExecuteServiceRequestStage );

// ----------------------------------------------------------------------------------------------------
// Resolve Context
// ----------------------------------------------------------------------------------------------------
$app->addStage( new ContextStage );

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
$app->addStage( new RespondStage );

$app->run()->send();
