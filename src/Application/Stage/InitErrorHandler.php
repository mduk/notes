<?php

namespace Mduk\Application\Stage;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Http\Application\Stage;
use Mduk\Gowi\Http\Request;
use Mduk\Gowi\Http\Response;

class InitErrorHandler implements Stage {

  public function execute( Application $app, Request $req, Response $res ) {

    set_exception_handler( function( $e ) {
      $eClass = get_class( $e );
      error_log( "{$eClass}: {$e->getMessage()} in {$e->getFile()} line {$e->getLine()}\nStacktrace: {$e->getTraceAsString()}" );

      http_response_code( 500 );
      header( 'Content-Type: application/api-problem+json' );
      echo json_encode( [
        'problemType' => get_class( $e ),
        'title' => $e->getMessage()
      ] );
      exit;
    } );

    set_error_handler( function( $errno, $errstr, $errfile, $errline, $errcontext ) {
      $errorLevels = [
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        8 => 'E_NOTICE'
      ];
      $errorLabel = isset( $errorLevels[ $errno ] )
        ? $errorLevels[ $errno ]
        : $errno;

      error_log( "{$errorLabel}: {$errstr} in {$errfile} line {$errline}" );

      http_response_code( 500 );
      header( 'Content-Type: application/api-problem+json' );
      echo json_encode( [
        'problemType' => $errorLabel,
        'title' => $errstr,
        'file' => $errfile,
        'line' => $errline
      ] );
      exit;
    } );
  }

}
