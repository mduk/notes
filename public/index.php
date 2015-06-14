<?php

namespace Mduk;

require_once 'vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Service\Router\Exception as RouterServiceException;

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
use Mduk\Gowi\Transcoder;

use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class MustacheTranscoder implements Transcoder {
  protected $template;
  protected $masseur;

  public function __construct( $template, \Closure $masseur = null ) {
    $this->template = $template;
    $this->masseur = $masseur ?: function( $in ) { return $in; };
  }

  public function encode( $in ) {
    $renderer = new \Mustache_Engine( [
      'loader' => new \Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../templates' )
    ] );

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
        'multiplicity' => 'one',
        'response' => [
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
        'multiplicity' => 'one',
        'response' => [
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
      ]
    ],

    '/users/{user_id}' => [
      'GET' => [
        'service' => 'user',
        'call' => 'getById',
        'bind' => [
          'route' => [ 'user_id' ]
        ],
        'multiplicity' => 'one',
        'response' => [
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
        'multiplicity' => 'one',
        'request' => [
          'transcoders' => [
            'application/json' => 'generic/json',
            'application/x-www-form-urlencoded' => 'generic/form'
          ]
        ],
        'response' => [
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
        'multiplicity' => 'one',
        'request' => [
          'transcoders' => [
            'application/json' => 'generic/json'
          ],
        ],
        'response' => [
          'transcoders' => [
            'application/json' => 'generic/json',
          ]
        ]
      ]
    ]

  ]
] );


$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  error_reporting( E_ALL );
/*
  set_error_handler( function( $errno, $errstr, $errfile, $errline, array $errcontext ) {
    //
  } );
*/
} ) );

// Initialise DB
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $pdo = new \PDO( 'sqlite::memory:' );
  $pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

  $schema = <<<SQL
CREATE TABLE user (
  user_id INT PRIMARY KEY NOT NULL,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  role TEXT NOT NULL
);

INSERT INTO user VALUES ( 1, 'Daniel', 'daniel.kendell@gmail.com', 'admin' );
INSERT INTO user VALUES ( 2, 'Slartibartfast', 'slartibartfast@magrathea.hg', 'user' );
INSERT INTO user VALUES ( 3, 'Arthur Dent', 'arthur_dent@earth.hg', 'user' );
INSERT INTO user VALUES ( 4, 'Ford Prefect', 'fprefect@megadodo-publications.hg', 'user' );

CREATE TABLE note (
  note_id INT PRIMARY KEY NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL
);

INSERT INTO note VALUES ( 1, 1, 'note one' );
INSERT INTO note VALUES ( 2, 1, 'note two' );
INSERT INTO note VALUES ( 3, 1, 'note three' );
INSERT INTO note VALUES ( 4, 1, 'note four' );
INSERT INTO note VALUES ( 5, 1, 'note five' );
INSERT INTO note VALUES ( 6, 1, 'note six' );
INSERT INTO note VALUES ( 7, 1, 'note seven' );
INSERT INTO note VALUES ( 8, 1, 'note eight' );
INSERT INTO note VALUES ( 9, 1, 'note nine' );
INSERT INTO note VALUES ( 10, 1, 'note ten' );
INSERT INTO note VALUES ( 11, 1, 'note eleven' );
INSERT INTO note VALUES ( 12, 1, 'note twelve' );
SQL;

  $pdo->exec( $schema );
  $app->setService( 'pdo', $pdo );
} ) );

// ----------------------------------------------------------------------------------------------------
// Initialise Log
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $log = new \Monolog\Logger( 'name' );
  $log->pushHandler( new \Monolog\Handler\StreamHandler( '/tmp/log' ) );
  $app->setService( 'log', $log );
} ) );

// ----------------------------------------------------------------------------------------------------
// Initialise Some Services
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {

  $app->setService( 'router', new RouterService( $app->getConfig( 'routes' ) ) );

  $pdo = $app->getService( 'pdo' );

  $mapperFactory = new Mapper\Factory( $pdo );

  $app->setService( 'user', new User\Service( $mapperFactory->get( '\\Mduk\\User\\Mapper' ) ) );
  $app->setService( 'note', new Note\Service( $mapperFactory->get( '\\Mduk\\Note\\Mapper' ) ) );

  $renderer = new \Mustache_Engine( [
    'loader' => new \Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../templates' )
  ] );

  $shim = new ServiceShim;
  $shim->setCall( 'render', [ $renderer, 'render' ], [ 'template', '__payload' ] );
  $app->setService( 'mustache', $shim );
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
      return new MustacheTranscoder( 'note_list', function( $in ) {
        $in['user'] = $in['objects'][0]->user[0];
        return $in;;
      } );
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
    $app->setConfig( 'active_route', $app->getService( 'router' )
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
// Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $service = $app->getConfig( 'active_route.config.service' );
  $call = $app->getConfig( 'active_route.config.call' );

  $serviceRequest = $app->getService( $service )
    ->request( $call );

  $parameters = $app->getConfig( 'active_route.config.parameters', [] );
  $parameterBindings = $app->getConfig( 'active_route.config.bind', [] );

  foreach ( $parameterBindings as $bind => $params ) {
    switch ( $bind ) {
      case 'payload':
        $payload = $app->getConfig( 'request.payload' );
        foreach ( $params as $param ) {
          if ( is_array( $payload ) ) {
            if ( !isset( $payload[ $param ] ) ) {
              throw new \Exception( "SERVICE REQUEST: {$bind}.{$param} not found." );
            }

            $value = $payload[ $param ];
          }
          else if ( is_object( $payload ) ) {
            if ( !isset( $payload->$param ) ) {
              throw new \Exception( "SERVICE REQUEST: {$bind}.{$param} not found." );
            }

            $value = $payload->$param;
          }
          $parameters[ $param ] = $value;
        }
        break;

      case 'query':
        foreach ( $params as $param ) {
          if ( !$req->query->get( $param ) ) {
            throw new \Exception( "SERVICE REQUEST: {$bind}.{$param} not found." );
          }
          $parameters[ $param ] = $req->query->get( $param );
        }
        break;

      case 'route':
        $routeParameters = $app->getConfig( 'active_route.params' );
        foreach ( $params as $param ) {
          if ( !isset( $routeParameters[ $param ] ) ) {
            throw new \Exception( "SERVICE REQUEST: {$bind}.{$param} not found." );
          }
          $parameters[ $param ] = $routeParameters[ $param ];
        }
        break;

      default:
        throw new \Exception("SERVICE REQUEST: Unknown bind '{$bind}'");
    }
  }

  $requiredParameters = $serviceRequest->getRequiredParameters();
  foreach ( $requiredParameters as $required ) {
    if ( !isset( $parameters[ $required ] ) ) {
      throw new \Exception( "SERVICE REQUEST: Parameter {$required} is required" );
    }
  }

  foreach ( $parameters as $pk => $pv ) {
    $serviceRequest->setParameter( $pk, $pv );
  }

  if ( $app->getConfig( 'request.payload', false ) ) {
    $payload = $app->getConfig( 'request.payload' );
    $serviceRequest->setPayload( $payload );
  }

  $collection = $serviceRequest->execute()->getResults();

  $app->setConfig( 'service.response', $collection );
} ) );

// ----------------------------------------------------------------------------------------------------
// Encode and send HTTP Response
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $transcoder = $app->getConfig('response.transcoder');
  $multiplicity = $app->getConfig('active_route.config.multiplicity', 'many' );
  $encode = $app->getConfig('service.response');

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

  $app->setConfig( 'response.body', $transcoder->encode( $encode ) );
} ) );

$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $res->headers->set( 'Content-Type', $app->getConfig( 'response.content_type' ) );
  $res->setContent( $app->getConfig( 'response.body' ) );
  return $res;
} ));

$app->run()->send();
