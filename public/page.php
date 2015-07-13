<?php

namespace Mduk\Page {
  abstract class Card {
    abstract public function render();
  }
}

namespace Mduk\Page\Card {
  use Mduk\Page\Card;
  class ServiceRequest extends Card {
    protected $transcoder;
    protected $serviceRequest;

    public function setTranscoder( $t ) {
      $this->transcoder = $t;
    }

    public function setServiceRequest( $sr ) {
      $this->serviceRequest = $sr;
    }

    public function render() {
      return $this->transcoder->encode(
        $this->serviceRequest->execute()->getResults()
      );
    }
  }

  class Ssi extends Card {
    public function __construct( $url ) {
      $this->url = $url;
    }

    public function render() {
      return '<!--#include virtual="' . $this->url  . '" -->';
    }
  }

  class Shim extends Card {
    protected $string;

    public function __construct( $s ) {
      $this->string = $s;
    }

    public function render() {
      return <<<EOF
<div class="ui segment">{$this->string}</div>
EOF;
    }
  }
}

namespace Mduk {

error_reporting( E_ALL );

require_once '../vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Transcoder\Mustache as MustacheTranscoder;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Service\Shim as ServiceShim;

$templatesDir = dirname( __FILE__ ) . '/../templates';
$transcoderFactory = new Factory( [
  'html:user_card' => function() use ( $templatesDir ) {
    return new MustacheTranscoder( "{$templatesDir}/cards/user.mustache" );
  }
] );
$cards = [
  'user' => [
    'type' => 'service',
    'service' => [
      'name' => 'user',
      'call' => 'getByUserId',
      'multiplicity' => 'one'
    ],
    'transcoder' => 'html:user_card'
  ],
  'user-about' => [
    'type' => 'shim',
    'shim' => 'ALL ABOUT ME!'
  ],
  'user-publications' => [
    'type' => 'ssi',
    'ssi' => '/documents/-/cards/profile-publications'
  ],
  'stats' => [
    'type' => 'shim',
    'shim' => 'SPAM! STATS! SPAM!'
  ],
  'follow' => [
    'type' => 'shim',
    'shim' => 'Follow you follow me.'
  ]
];

$app = new Application( '.' );
$app->setConfig( 'debug', true );
$builder = new ApplicationBuilder( $app );

$builder->setBuilder( 'page', new PageApplicationBuilder );

$builder->buildRoute( 'page', '/user/{user_id}', [
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
          'getByUserId' => [
            'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
            'required' => [ 'user_id' ]
          ]
        ]
      ]
    ]
  ],
  'layout' => rand( 0, 1 ) == 0 ? 'right-sidebar' : 'left-sidebar',
  'title' => 'My page title!',
  'cards' => $cards,
  'regions' => [
    'content' => [
      'cards' => [
        'user', 'user-about', 'user-publications'
      ]
    ],
    'sidebar' => [
      'cards' => [ 'stats', 'follow' ]
    ]
  ]
] );

$response = $builder->build()->run();
if ( $response->getStatusCode() == 404 ) {
  return false;
}
$response->send();

} // namespace
