<?php

namespace Mduk;

use Mduk\Gowi\Http\Application as App;

class ApplicationBuilderTest extends \PHPUnit_Framework_TestCase {
  public function testBuildInvalid() {
    try {
      $builder = new ApplicationBuilder( new App( '.' ) );
      $app = $builder->build( 'nonsense', [] );
      $this->fail( "Should have thrown an exception" );
    }
    catch ( \Exception $e ) {
    }
  }

  public function testBuildWebtable() {
    $builder = new ApplicationBuilder( new App( '.' ) );
    $builder->setBuilder( 'webtable', new WebtableApplicationBuilder );
    $app = $builder->build( 'webtable', [
      'connection' => [
        'dsn' => ''
      ],
      'table' => 'user',
      'pk' => 'user_id',
      'fields' => [ 'name', 'email', 'role' ]
    ] );

  }
}

