<?php

namespace Mduk\Application\Builder;

use Mduk\Gowi\Factory as GowiFactory;
use Psr\Log\LoggerInterface as Logger;

class Factory extends GowiFactory {

  protected $debug;
  protected $transcoderFactory;
  protected $serviceFactory;
  protected $logger;

  public function get( $builder ) {
    switch ( $builder ) {

      case 'router':
        $builder = new \Mduk\Application\Builder\Router;
        break;

      case 'service-invocation':
        $builder = new \Mduk\Application\Builder\ServiceInvocation;
        break;

      case 'webtable':
        $builder = new \Mduk\Application\Builder\WebTable;
        break;

      case 'static-page':
        $builder = new \Mduk\Application\Builder\StaticPage;
        break;

      case 'card':
        $builder = new \Mduk\Application\Builder\Card;
        break;

      default:
        throw new \Exception("Unknown application type: {$builder}" );
    }

    $builder->setDebug( $this->debug );
    $builder->setApplicationBuilderFactory( $this );
    $builder->setTranscoderFactory( $this->transcoderFactory );
    $builder->setServiceFactory( $this->serviceFactory );
    $builder->setLogger( $this->logger );
    return $builder;
  }

  public function setDebug( $debug ) {
    $this->debug = $debug;
  }

  public function setServiceFactory( GowiFactory $factory ) {
    $this->serviceFactory = $factory;
  }

  public function setTranscoderFactory( GowiFactory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function setLogger( Logger $logger ) {
    $this->logger = $logger;
  }
}
