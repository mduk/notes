<?php

namespace Mduk;

ini_set('xdebug.collect_params', '4');
error_reporting( E_ALL );

require_once '../vendor/autoload.php';

use Mduk\Service\Router as RouterService;
use Mduk\Transcoder\Mustache as MustacheTranscoder;

use Mduk\Gowi\Http\Application\Stage\Stub as StubStage;
use Mduk\Gowi\Factory;
use Mduk\Gowi\Service\Shim as ServiceShim;
use Mduk\Transcoder\Factory as TranscoderFactory;

/**
 * Start here, with an ApplicationBuilderFactory
 */
$applicationBuilderFactory = new Application\Builder\Factory;

/**
 * Set debug setting
 */
$applicationBuilderFactory->setDebug( true );

/**
 * Construct a Logger, 'cause logging is useful.
 */
$logger = new Gowi\Logger\PhpErrorLog;
//$logger = new HtmlReportLogger;
$applicationBuilderFactory->setLogger( $logger );

/**
 * Construct a Transcoder Factory. Applications need Transcoders
 */
$templatesDir = dirname( __FILE__ ) . '/../templates';
$transcoderFactory = new TranscoderFactory( $templatesDir );
$applicationBuilderFactory->setTranscoderFactory( $transcoderFactory );

/**
 * Construct a Service factory. Applications need Services
 */
$serviceFactory = new Factory( [
  'foo' => function() {
    $s = new ServiceShim('My stub service');
    $s->setCall( 'bar', function() {
      return 'baz';
    }, [], 'bar call returns baz' );
    return $s;
  }
] );
$applicationBuilderFactory->setServiceFactory( $serviceFactory );

/**
 * Get a Router Application Builder since we want an Application that can route HTTP requests
 */
$applicationBuilder = $applicationBuilderFactory->get( 'router' );

$routes = require 'app_routes.php';
foreach ( $routes as $routeSpec ) {
  $applicationBuilder->defineRoute( $routeSpec['builder'], $routeSpec['location'], $routeSpec['config'] );
}

$response = $applicationBuilder->build()
  ->run();

if ( $response->getStatusCode() == 404 ) {
  return false;
}

$response->send();
