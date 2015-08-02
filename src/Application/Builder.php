<?php

namespace Mduk\Application;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Factory;
use Psr\Log\LoggerInterface as Logger;

abstract class Builder {

  private $debug;
  private $transcoderFactory;
  private $serviceFactory;
  private $logger;
  private $applicationBuilderFactory;
  private $appConfig = [];

  public function setDebug( $debug ) {
    $this->debug = $debug;
  }

  public function setTranscoderFactory( Factory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function setServiceFactory( Factory $factory ) {
    $this->serviceFactory = $factory;
  }

  public function setLogger( Logger $logger ) {
    $this->logger = $logger;
  }

  public function setApplicationBuilderFactory( Factory $factory ) {
    $this->applicationBuilderFactory = $factory;
  }

  /*public function applyConfigArray( array $array ) {
    $this->appConfig = array_replace_recursive( $this->appConfig, $array );
  }*/

  public function build( Application $app = null, array $config = [] ) {
    if ( !$app ) {
      $app = new Application;
    }

    $app->applyConfigArray( $this->appConfig );

    $app->setConfig( 'debug', $this->debug );
    $app->setConfig( 'application.builder', $this->applicationBuilderFactory );
    $app->setConfig( 'transcoder', $this->transcoderFactory );
    $app->setLogger( $this->logger );
    $app->setServiceFactory( $this->serviceFactory );

    return $app;
  }

  protected function getDebug() {
    return $this->debug;
  }

  protected function getTranscoderFactory() {
    $this->transcoderFactory;
  }

  protected function getLogger() {
    return $this->logger;
  }

  protected function getApplicationBuilderFactory() {
    return $this->applicationBuilderFactory;
  }

}
