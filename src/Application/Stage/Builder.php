<?php

namespace Mduk\Application\Stage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class Builder implements Stage {

  protected $builder;
  protected $config;
  protected $appConfig;

  public function __construct( $builder, $config, $appConfig = [] ) {
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

    $builder = $app->getConfig( 'application.builder' )
      ->get( $this->builder );

    if ( $this->appConfig != [] ) {
      $app->debugLog( function() {
        return __CLASS__ . ": Applying config to new Application: " . print_r( $this->appConfig, true );
      } );

      $builder->applyConfigArray( $this->appConfig );
    }

    return $builder->build( null, $this->config );
  }

}
