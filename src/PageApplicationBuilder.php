<?php

namespace Mduk;

use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;

class PageApplicationBuilder {
  public function buildRoutes( $path, $config ) {
    return [
      $path => [
        'GET' => [
          'builder' => 'page',
          'config' => $config
        ]
      ]
    ];
  }

  public function build( $config ) {
    $app = new Gowi\Http\Application( '.' );
    $app->applyConfigArray( $config );

    $app->addStage( new Stage\InitPdoServices );

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

    // --------------------------------------------------------------------------------
    // Find Page template
    // Render cards for Page Regions
    // Render Page
    // Return HTTP Response
    // --------------------------------------------------------------------------------
    $app->addStage( new StubStage( function( $a, $rq, $rs ) {
      $layoutTemplatePath = dirname( __FILE__ ) .
        '/../templates/layouts/' .
        $a->getConfig( 'layout' ) .
        '.mustache';

      $transcoder = new Transcoder\Mustache( $layoutTemplatePath );
      $regions = [];
      foreach ( $a->getConfig( 'regions' ) as $region => $regionSpec ) {
        $cards = [];
        foreach ( $regionSpec['cards'] as $card ) {
          $cards[] = $a->getConfig( "card.{$card}" )->render();
        }
        $regions[ $region ] = implode( '', $cards );
      }

      return $rs->ok()->html( $transcoder->encode(
        array_replace_recursive( [ 'title' => $a->getConfig( 'title' ) ], $regions )
      ) );
    } ) );


    return $app;
  }
}
