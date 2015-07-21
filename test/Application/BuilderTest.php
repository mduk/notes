<?php

namespace Mduk\Application;

use Mduk\Gowi\Http\Application as App;

class BuilderTest extends \PHPUnit_Framework_TestCase {
  public function testBuildInvalid() {
    try {
      $builder = new Builder( new App( '.' ) );
      $app = $builder->build( 'nonsense', [] );
      $this->fail( "Should have thrown an exception" );
    }
    catch ( \Exception $e ) {
    }
  }

  public function testBuildWebtable() {
    $builder = new Builder( new App( '.' ) );
    $builder->setBuilder( 'webtable', new Builder\WebTable );
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

