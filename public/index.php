<?php

namespace Mduk;

require_once 'vendor/autoload.php';

use Mduk\Dot;
use Mduk\Dot\Exception\InvalidKey as DotInvalidKeyException;
use Mduk\Gowi\Application as BaseApp;
use Mduk\Gowi\Application\Stage;
use Mduk\Gowi\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Http\Request as HttpRequest;
use Mduk\Gowi\Http\Response as HttpResponse;
use Mduk\Gowi\Service\Shim as ServiceShim;
use Mduk\Gowi\Transcoder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Application extends BaseApp {
  public function __construct( $baseDir, $config ) {
    parent::__construct( $baseDir );
    $this->config = new Dot( $config );
  }

  public function setConfig( array $array ) {
    foreach ( $array as $k => $v ) {
      $this->config->set( $k, $v );
    }
  }

  public function getConfig( $rootKey = null ) {
    if ( !$rootKey ) throw new \Exception('Just a spike. not implemented');
    try {
      return $this->config->get( $rootKey );
    }
    catch ( DotInvalidKeyException $e ) {
      return false;
    }
  }
}

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

$config = [
  'debug' => true,
  'routes' => [

    '/' => [
      'service' => 'user',
      'GET' => [
        'call' => 'listAll',
        'transcoders' => [
          'response' => [
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/users' => [
      'service' => 'user',
      'GET' => [
        'call' => 'getAll',
        'transcoders' => [
          'response' => [
            'text/html' => 'html/user_list',
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/users/{user_id}' => [
      'service' => 'user',
      'GET' => [
        'call' => 'getById',
        'bind' => [ 'user_id' ],
        'multiplicity' => 'one',
        'transcoders' => [
          'response' => [
            'text/html' => 'html/user_page',
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/users/{user_id}/notes' => [
      'service' => 'note',
      'GET' => [
        'call' => 'getByUserId',
        'bind' => [ 'user_id' ],
        'transcoders' => [
          'response' => [
            'text/html' => 'html/note_list',
            'application/json' => 'generic/json'
          ]
        ]
      ]
    ],

    '/srv/mustache/{template}' => [
      'service' => 'mustache',
      'POST' => [
        'call' => 'render',
        'bind' => [ 'template' ],
        'multiplicity' => 'one',
        'transcoders' => [
          'request' => [
            'application/json' => 'generic/json',
            'application/x-www-form-urlencoded' => 'generic/form'
          ],
          'response' => [
            'text/html' => 'generic/text',
            'text/plain' => 'generic/text'
          ]
        ]
      ]
    ],

  ]
];

$app = new Application( dirname( __FILE__ ), $config );

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

  $pdo = $app->getService( 'pdo' );

  $mapperFactory = new Mapper\Factory( $pdo );

  $app->setService( 'user', new User\Service( new User\Mapper( $mapperFactory, $pdo ) ) );
  $app->setService( 'note', new Note\Service( new Note\Mapper( $mapperFactory, $pdo ) ) );

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
  $routeConfig = $app->getConfig( 'routes' );

  $routes = new RouteCollection();
  foreach ( $routeConfig as $routePattern => $routeParams ) {
    $route = new Route( $routePattern, $routeParams );
    $routes->add( $routePattern, $route );
  }

  try {
    $context = new RequestContext();
    $matcher = new UrlMatcher( $routes, $context );
    $route = $matcher->matchRequest( $req );
  }
  catch ( ResourceNotFoundException $e ) {
    return $res->notFound()
      ->text( $req->getUri() . "\nNot Found" );
  }

  $app->setConfig( [ 'active_route' => $route ] );
} ) );

// ----------------------------------------------------------------------------------------------------
// Block invalid method calls
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  if ( !$app->getConfig('active_route.' . $req->getMethod() ) ) {
    $res->setStatusCode( 405 );
    $res->headers->set( 'Content-Type', 'text/plain' );
    $res->setContent( $req->getMethod() . ' is not allowed on ' . $req->getUri() );
    return $res;
  }

  $app->setConfig( [
    'active_route_method' => $app->getConfig( 'active_route.' . $req->getMethod() )
  ] );
} ) );

// ----------------------------------------------------------------------------------------------------
// Select Response MIME type
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $supportedTypes = array_keys( $app->getConfig('active_route_method.transcoders.response') );
  $acceptedTypes = $req->getAcceptableContentTypes();
  $selectedType = false;

  foreach ( $acceptedTypes as $aType ) {
    if ( in_array( $aType, $supportedTypes ) ) {
      $selectedType = $aType;
      break;
    }
  }

  if ( !$selectedType ) {
    $res->setContent("Bad Accept header\n" . $req->headers->get('Accept') );
    return $res;
  }

  $app->setConfig( [
    'response' => [
      'content_type' => $selectedType
    ]
  ] );
} ) );

// ----------------------------------------------------------------------------------------------------
// (Artifact of using stub stages for both transcoder selection stages)
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $app->setService( 'resolve', function( $acceptedContentTypes, $mimeTranscoders ) use ($app) {
    foreach ( $acceptedContentTypes as $mime ) {
      $app->getService('log')->debug( $mime );
      if ( !isset( $mimeTranscoders[ $mime ] ) ) {
        continue;
      }

      try {
        $transcoder = $app->getService( 'transcoder' )
          ->get( $mimeTranscoders[ $mime ] );
        return [ $mime, $transcoder ];
      } catch ( \Exception $e ) {}
    }

    $types = implode( ', ', $acceptedContentTypes );
    throw new \Exception( "No transcoder found for: {$types}" );
  } );
} ) );

// ----------------------------------------------------------------------------------------------------
// Select Request Transcoder
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $log = $app->getService('log');
  $resolve = $app->getService('resolve');
  $routeMethod = $app->getConfig( 'active_route_method' );

  $content = $req->getContent();
  if ( $content ) {
    $requestTranscoders = ( isset( $routeMethod['transcoders']['request'] ) )
      ? $routeMethod['transcoders']['request']
      : [];

    $resolved = $resolve(
      [ $req->headers->get( 'Content-Type' ) ],
      $requestTranscoders
    );
    $mime = $resolved[0];
    $requestTranscoder = $resolved[1];

    $app->setConfig( [ 'request' => [
      'content_type' => $mime,
      'transcoder' => $requestTranscoder
    ] ] );
  }

} ) );

// ----------------------------------------------------------------------------------------------------
// Select Response Transcoder
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $log = $app->getService('log');
  $resolve = $app->getService('resolve');
  $routeMethod = $app->getConfig( 'active_route_method' );

  $log->debug(
    'Offered Types: ' .
    json_encode( array_keys( $routeMethod['transcoders']['response'] ) )
  );

  $responseTranscoders = ( isset( $routeMethod['transcoders']['response'] ) )
    ? $routeMethod['transcoders']['response']
    : [];

  $log->debug(
    'Acceptable Types: ' .
    json_encode( $req->getAcceptableContentTypes() )
  );

  $resolved = $resolve(
    $req->getAcceptableContentTypes(),
    $responseTranscoders
  );
  $mime = $resolved[0];
  $responseTranscoder = $resolved[1];

  $app->setConfig( [ 'response' => [
    'content_type' => $mime,
    'transcoder' => $responseTranscoder
  ] ] );

} ) );

// ----------------------------------------------------------------------------------------------------
// Decode HTTP Request body
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $content = $req->getContent();
  if ( $content ) {
    $transcoder = $app->getConfig('request.transcoder');
    $payload = $transcoder->decode( $content );
    $app->setConfig( [ 'request' => [
      'payload' => $payload
    ] ] );
  }
} ));

// ----------------------------------------------------------------------------------------------------
// Service Request
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  
  $route = $app->getConfig( 'active_route' );
  $routeMethod = $app->getConfig( 'active_route_method' );

  $serviceRequest = $app->getService( $route['service'] )
    ->request( $routeMethod['call'] );

  $bindParams = [];

  if ( isset( $routeMethod['bind'] ) ) {
    if ( !is_array( $routeMethod['bind'] ) ) {
      throw new \Exception( "Parameter 'bind' must be an array.");
    }
    $bindParams = $routeMethod['bind'];
  }

  foreach ( $bindParams as $bind ) {
    $serviceRequest->setParameter( $bind, $route[ $bind ] );
  }

  if ( $app->getConfig( 'request.payload') ) {
    $serviceRequest->setPayload( $app->getConfig( 'request.payload') );
  }

  $collection = $serviceRequest->execute()->getResults();

  $app->setConfig( [ 'service' => [
    'response' => $collection
  ] ] );

} ) );

// ----------------------------------------------------------------------------------------------------
// Encode and send HTTP Response
// ----------------------------------------------------------------------------------------------------
$app->addStage( new StubStage( function( Application $app, HttpRequest $req, HttpResponse $res ) {
  $transcoder = $app->getConfig('response.transcoder');
  $routeConfig = $app->getConfig('active_route_method');
  $encode = $app->getConfig('service.response');

  if ( isset( $routeConfig['multiplicity'] ) && $routeConfig['multiplicity'] == 'one' ) {
    $encode = $encode->shift();
  }
  else {
    $page = (int) $req->query->get( 'page', 0 );
    $encode = [
      'total' => $encode->count(),
      'page' => $page,
      'pages' => $encode->numPages(),
      'objects' => $encode->page( $page )->getAll()
    ];
  }

  $res->headers->set( 'Content-Type', $app->getConfig( 'response.content_type' ) );
  $res->setContent( $transcoder->encode( $encode ) );
  return $res;
} ));

$app->run()->send();