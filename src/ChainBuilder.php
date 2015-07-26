<?php

namespace Mduk;

use Mduk\Gowi\Factory;
use Psr\Log\LoggerInterface as Logger;

abstract class ChainBuilder {

  private $debug;
  private $transcoderFactory;
  private $logger;
  private $applicationBuilderFactory;

  public function setDebug( $debug ) {
    $this->debug = $debug;
  }

  public function setTranscoderFactory( Factory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function setLogger( Logger $logger ) {
    $this->logger = $logger;
  }

  public function setApplicationBuilderFactory( Factory $factory ) {
    $this->applicationBuilderFactory = $factory;
  }

  protected function configure( $app ) {
    if ( $this->debug && $this->logger ) {
      $this->logger->debug( get_class( $this ) . 
        ': Configuring Application with:' );
      $this->logger->debug( get_class( $this ) .
        ':     debug => ' . print_r( $this->debug, true ) );
      $this->logger->debug( get_class( $this ) .
        ':     transcoder => ' . print_r( $this->transcoderFactory, true ) );
      $this->logger->debug( get_class( $this ) .
        ':     application.builder  => ' . print_r( $this->applicationBuilderFactory, true ) );
    }

    $app->setConfig( 'debug', $this->debug );
    $app->setConfig( 'application.builder', $this->applicationBuilderFactory );
    $app->setConfig( 'transcoder', $this->transcoderFactory );
    $app->setLogger( $this->logger );
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
