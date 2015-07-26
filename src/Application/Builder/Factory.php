<?php

namespace Mduk\Application\Builder;

use Mduk\Gowi\Factory as GowiFactory;
use Psr\Log\LoggerInterface as Logger;

class Factory extends GowiFactory {

  protected $transcoderFactory;
  protected $logger;

  public function get( $builder ) {
    switch ( $builder ) {
      case 'service-invocation':
        $builder = new \Mduk\Application\Builder\ServiceInvocation;
        break;

      case 'webtable':
        $builder = new \Mduk\Application\Builder\WebTable;
        break;

      case 'static-page':
        $builder = new \Mduk\Application\Builder\StaticPage;
        break;

      default:
        throw new \Exception("Unknown application type: {$builder}" );
    }

    $builder->setTranscoderFactory( $this->getTranscoderFactory() );
    $builder->setLogger( $this->getLogger() );
    return $builder;
  }

  public function setTranscoderFactory( GowiFactory $factory ) {
    $this->transcoderFactory = $factory;
  }

  public function setLogger( Logger $logger ) {
    $this->logger = $logger;
  }

  protected function getTranscoderFactory() {
    return $this->transcoderFactory;
  }

  protected function getLogger() {
    return $this->logger;
  }
}
