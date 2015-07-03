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
use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Service\Shim as ServiceShim;

$builder = new RoutedApplicationBuilder;

$templatesDir = dirname( __FILE__ ) . '/../templates';
$builder->useTranscoderFactory( new Factory( [
  'html:user_card' => function() use ( $templatesDir ) {
    return new MustacheTranscoder( "{$templatesDir}/cards/user.mustache" );
  }
] ) );

$builder->addPdoConnection( 'main', 'sqlite:/Users/daniel/dev/notes/db.sq3' );

$builder->addPdoService( 'user', 'main', [
  'getByUserId' => [
    'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
    'required' => [ 'user_id' ]
  ]
] );

$app = $builder->build();

$app->setConfig( 'cards', [
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

] );

$app->setConfig( 'routes./user/{user_id}.GET', [
  'page' => [
    'layout' => 'right-sidebar',
    'title' => 'My page title!',
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
  ]
] );

// --------------------------------------------------------------------------------
// Initialise Card factory from card config
// --------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $rq, $rs ) {
  $factories = [];
  foreach ( $app->getConfig( 'cards' ) as $card => $spec ) {
    switch ( $spec['type'] ) {

      case 'service':
        $factory = function() use ( $spec, $app ) {
          $transcoder = $app->getConfig( "transcoder.{$spec['transcoder']}" );
          $serviceRequest = $app->getService( $spec['service']['name'] )
            ->request( $spec['service']['call'] );

          foreach ( $app->getConfig( 'route.parameters' ) as $k => $v ) {
            $serviceRequest->setParameter( $k, $v );
          }

          $card = new Page\Card\ServiceRequest;
          $card->setTranscoder( $transcoder );
          $card->setServiceRequest( $serviceRequest );
          
          return $card;
        };
        break;

      case 'ssi':
        $factory = function() use ( $spec ) {
          return new Page\Card\Ssi( $spec['ssi'] );
        };
        break;

      case 'shim':
        $factory = function() use ( $spec ) {
          return new Page\Card\Shim( $spec['shim'] );
        };
        break;

      default:
        throw new \Exception( "Unknown card type: {$spec['type']}" );

    }

    $factories[ $card ] = $factory;
  }

  $app->setConfig( 'card', new Factory( $factories ) );
} ) );

/*
$app->addStage( new StubStage( function( $a, $rq, $rs ) {
  return $rs->ok()->text( print_r( $a->getConfigArray(), true ) );
} ) );
*/

// --------------------------------------------------------------------------------
// Find Page template
// Render cards for Page Regions
// Render Page
// Return HTTP Response
// --------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $a, $rq, $rs ) {
  $layoutTemplatePath = dirname( __FILE__ ) .
    '/../templates/layouts/' .
    $a->getConfig( 'page.layout' ) .
    '.mustache';

  $transcoder = new Transcoder\Mustache( $layoutTemplatePath );
  $regions = [];
  foreach ( $a->getConfig( 'page.regions' ) as $region => $regionSpec ) {
    $cards = [];
    foreach ( $regionSpec['cards'] as $card ) {
      $cards[] = $a->getConfig( "card.{$card}" )->render();
    }
    $regions[ $region ] = implode( '', $cards );
  }

  return $rs->ok()->html( $transcoder->encode(
    array_replace_recursive( $a->getConfig( 'page' ), $regions )
  ) );
} ) );


$response = $app->run();
if ( $response->getStatusCode() == 404 ) {
  return false;
}
$response->send();

} // namespace
