<?php

namespace Mduk;

ini_set('xdebug.collect_params', '4');
error_reporting( E_ALL );

require_once '../vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Transcoder\Mustache as MustacheTranscoder;

use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Service\Shim as ServiceShim;
use Mduk\Transcoder\Factory as TranscoderFactory;

/**
 * Start here, with an ApplicationBuilderFactory
 */
$applicationBuilderFactory = new Application\Builder\Factory;

/**
 * Set debug setting
 */
$applicationBuilderFactory->setDebug( true );

/**
 * Construct a Logger, 'cause logging is useful.
 */
$logger = new Gowi\Logger\PhpErrorLog;
//$logger = new HtmlReportLogger;
$applicationBuilderFactory->setLogger( $logger );

/**
 * Construct a Transcoder Factory. Applications need Transcoders
 */
$templatesDir = dirname( __FILE__ ) . '/../templates';
$transcoderFactory = new TranscoderFactory( $templatesDir );
$applicationBuilderFactory->setTranscoderFactory( $transcoderFactory );

/**
 * Construct a Service factory. Applications need Services
 */
$serviceFactory = new Factory( [
  'foo' => function() {
    $s = new ServiceShim('My stub service');
    $s->setCall( 'bar', function() {
      return 'baz';
    }, [], 'bar call returns baz' );
    return $s;
  }
] );
$applicationBuilderFactory->setServiceFactory( $serviceFactory );

/**
 * Get a Router Application Builder since we want an Application that can route HTTP requests
 */
$applicationBuilder = $applicationBuilderFactory->get( 'router' );

/**
 * Start building routes
 */
$applicationBuilder->buildRoute( 'card', '/tkt/card/foo', [
  'service' => [
    'name' => 'foo',
    'call' => 'bar',
    'multiplicity' => 'one'
  ],
  'template' => 'foobar'
] );

$applicationBuilder->buildRoute( 'static-page', '/', [ 'template' => 'index' ] );
$applicationBuilder->buildRoute( 'static-page', '/about', [ 'template' => 'about' ] );

$applicationBuilder->buildRoute( 'webtable', '/api/tables/user', [
  'connection' => [
    'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
  ],
  'table' => 'user',
  'pk' => 'user_id',
  'fields' => [ 'name', 'email', 'role' ]
] );


$applicationBuilder->buildRoute( 'service-invocation', [ 'GET', '/users' ], [
  'transcoder' => $transcoderFactory,
  'pdo' => [
    'connections' => [
      'maindb' => [
        'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
      ],
    ],
    'services' => [
      'user' => [
        'connection' => 'maindb',
        'queries' => [
          'getAll' => [
            'sql' => 'SELECT * FROM user'
          ]
        ]
      ]
    ]
  ],
  'service' => [
    'name' => 'user',
    'call' => 'getAll',
    'multiplicity' => 'many',
  ],
  'http' => [
    'response' => [
      'transcoders' => [
        'text/html' => 'template:user_list',
        'application/json' => 'generic:json'
      ]
    ]
  ]
] );

$applicationBuilder->buildRoute( 'service-invocation', [ 'GET', '/users/{user_id}' ], [
  'transcoder' => $transcoderFactory,
  'pdo' => [
    'connections' => [
      'maindb' => [
        'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
      ],
    ],
    'services' => [
      'user' => [
        'connection' => 'maindb',
        'queries' => [
          'getById' => [
            'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
            'required' => [ 'user_id' ]
          ]
        ]
      ]
    ]
  ],
  'service' => [
    'name' => 'user',
    'call' => 'getById',
    'multiplicity' => 'one',
  ],
  'bind' => [
    'required' => [
      'route' => [ 'user_id' ]
    ]
  ],
  'http' => [
    'response' => [
      'transcoders' => [
        'text/html' => 'template:user_page',
        'application/json' => 'generic:json'
      ]
    ]
  ]
] );

$applicationBuilder->buildRoute( 'service-invocation', [ 'GET', '/users/{user_id}/notes' ], [
  'transcoder' => $transcoderFactory,
  'pdo' => [
    'connections' => [
      'maindb' => [
        'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
      ],
    ],
    'services' => [
      'user' => [
        'connection' => 'maindb',
        'queries' => [
          'getById' => [
            'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
            'required' => [ 'user_id' ]
          ]
        ]
      ],
      'note' => [
        'connection' => 'maindb',
        'queries' => [
          'getByUserId' => [
            'sql' => 'SELECT * FROM note WHERE user_id = :user_id',
            'required' => [ 'user_id' ]
          ]
        ]
      ]
    ]
  ],
  'service' => [
    'name' => 'note',
    'call' => 'getByUserId',
    'multiplicity' => 'many',
  ],
  'bind' => [
    'required' => [
      'route' => [ 'user_id' ]
    ]
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
        'text/html' => 'template:note_list',
        'application/json' => 'generic:json'
      ]
    ]
  ]
] );

$response = $applicationBuilder->build()
  ->run();

if ( $response->getStatusCode() == 404 ) {
  return false;
}

$response->send();
