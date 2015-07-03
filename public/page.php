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
  'html:profile_card' => function() use ( $templatesDir ) {
    return new MustacheTranscoder( "{$templatesDir}/cards/profile.mustache" );
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

$app->setConfig( 'routes./user/{user_id}.GET', [
  'page' => [
    'layout' => 'right-sidebar',
    'title' => 'My page title!',
    'regions' => [
      'content' => [
        'cards' => [
          'profile', 'profile-about', 'profile-publications'
        ]
      ],
      'sidebar' => [
        'cards' => [ 'stats', 'follow' ]
      ]
    ]
  ]
] );

// --------------------------------------------------------------------------------
// Set up some Card Factories
// --------------------------------------------------------------------------------
$app->addStage( new StubStage( function( $app, $rq, $rs ) {
  $app->setConfig( 'card', new Factory( [
    'profile' => function() use ( $app ) {
      $card = new Page\Card\ServiceRequest;
      $card->setTranscoder( $app->getConfig( 'transcoder.html:profile_card' ) );
      $card->setServiceRequest( 
        $app->getService( 'user' )
          ->request( 'getByUserId' )
          ->setParameter( 'user_id', $app->getConfig( 'route.parameters.user_id' ) )
      );
      return $card;
    },
    'profile-about' => function() {
      return new Page\Card\Shim( 'All about me!' );
    },
    'profile-publications' => function() {
      return new Page\Card\Ssi( '/documents/-/cards/profile-publications' );
    },
    'stats' => function() {
      return new Page\Card\Shim( 'SPAM! STATS! SPAM!' );
    },
    'follow' => function() {
      return new Page\Card\Shim( 'Follow me follow you' );
    },
  ] ) );
} ) );

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

$app->addStage( new StubStage( function( $a, $rq, $rs ) {
  return $rs->ok()->text( print_r( $a->getConfigArray(), true ) );
} ) );

$response = $app->run();
if ( $response->getStatusCode() == 404 ) {
  return false;
}
$response->send();

} // namespace
