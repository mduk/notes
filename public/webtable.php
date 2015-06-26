<?php

namespace Mduk;

error_reporting( E_ALL );

require_once 'vendor/autoload.php';

$builder = new WebTableApplicationBuilder;
$builder->setPdoConnection( 'sqlite:/Users/daniel/dev/notes/db.sq3' );
$builder->addTable( 'user', 'user_id', [ 'name', 'email', 'role' ] );
$builder->addTable( 'note', 'note_id', [ 'user_id', 'body' ] );

$builder->build()
  ->run()
  ->send();
