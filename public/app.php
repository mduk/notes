<?php

namespace Mduk;

error_reporting( E_ALL );

require_once '../vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Transcoder\Mustache as MustacheTranscoder;

use Mduk\Gowi\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
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

$builder = new RoutedServiceApplicationBuilder;

$builder->useTranscoderFactory( $transcoderFactory );

$builder->addStaticPage( '/', 'index' );
$builder->addStaticPage( '/about', 'about' );

$builder->addRemoteService( 'remote_calculator', 'http://localhost:5556/' );

$builder->addPdoConnection( 'main', 'sqlite:/Users/daniel/dev/notes/db.sq3' );

$builder->addPdoService( 'user', 'main', [
  'getAll' => [
    'sql' => 'SELECT * FROM user'
  ],
  'getById' => [
    'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
    'required' => [ 'user_id' ]
  ]
] );

$builder->addPdoService( 'note', 'main', [
  'getByUserId' => [
    'sql' => 'SELECT * FROM note WHERE user_id = :user_id',
    'required' => [ 'user_id' ]
  ]
] );

$builder->addBootstrapStage( new StubStage( function( $app, $req, $res ) {

  $renderer = new \Mustache_Engine( [
    'loader' => new \Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../templates' )
  ] );

  $shim = new ServiceShim( 'Mustache template renderer' );
  $shim->setCall( 'render', [ $renderer, 'render' ], [ 'template', '__payload' ],
    "Render a mustache template" );
  $app->setService( 'mustache', $shim );

} ) );

$builder->addRoute( '/users', [
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

$builder->addRoute( '/users/{user_id}', [
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

$builder->addRoute( '/users/{user_id}/notes', [
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

$builder->addRoute( '/srv/mustache/{template}', [
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

$builder->addRoute( '/srv/router', [
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

$builder->addRoute( '/srv/calculator/add/{x}/{y}', [
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

$builder->build()
  ->run()
  ->send();
