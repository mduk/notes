<?php

namespace Mduk\Stage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class Builder implements Stage {

  public function __construct( $builderFactory, $builder, $config, $appConfig = [] ) {
    $this->builderFactory = $builderFactory;
    $this->builder = $builder;
    $this->config = $config;
    $this->appConfig = $appConfig;
  }

  public function execute( Application $app, Request $req, Response $res ) {
    $app->debugLog( function() {
      return __CLASS__ . ": Using builder: {$this->builder}";
    } );

    $app->debugLog( function() {
      return __CLASS__ . ": Using builder config: " . print_r( $this->config, true );
    } );

    $newApp = $this->builderFactory->get( $this->builder )
      ->build( $this->config );

    if ( $this->appConfig != [] ) {
      $app->debugLog( function() {
        return __CLASS__ . ": Applying config to new Application: " . print_r( $this->appConfig, true );
      } );

      $newApp->applyConfigArray( $this->appConfig );
    }

    return $newApp;
  }

}
