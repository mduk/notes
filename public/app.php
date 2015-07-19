<?php

namespace Mduk;

error_reporting( E_ALL );

require_once '../vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Transcoder\Mustache as MustacheTranscoder;

use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
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

$app = new Gowi\Http\Application( __DIR__ );
$app->setConfig( 'debug', true );

$builder = new Application\Builder( $app );

$builder->setBuilder( 'service-invocation', new ServiceInvocationApplicationBuilder );
$builder->setBuilder( 'webtable', new WebTableApplicationBuilder );
$builder->setBuilder( 'static-page', new StaticPageApplicationBuilder );

$builder->buildRoute( 'static-page', '/', [ 'template' => 'index' ] );
$builder->buildRoute( 'static-page', '/about', [ 'template' => 'about' ] );

$builder->buildRoute( 'webtable', '/api/tables/user', [
  'connection' => [
    'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
  ],
  'table' => 'user',
  'pk' => 'user_id',
  'fields' => [ 'name', 'email', 'role' ]
] );


$builder->buildRoute( 'service-invocation', [ 'GET', '/users' ], [
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
        'text/html' => 'html:user_list',
        'application/json' => 'generic:json'
      ]
    ]
  ]
] );

$builder->buildRoute( 'service-invocation', [ 'GET', '/users/{user_id}' ], [
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
        'text/html' => 'html:user_page',
        'application/json' => 'generic:json'
      ]
    ]
  ]
] );

$builder->buildRoute( 'service-invocation', [ 'GET', '/users/{user_id}/notes' ], [
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
        'text/html' => 'html:note_list',
        'application/json' => 'generic:json'
      ]
    ]
  ]
] );

$builder->build()
  ->run()
  ->send();
