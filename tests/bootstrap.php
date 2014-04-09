<?php

spl_autoload_register( function( $class ) {
	$class = str_replace( '_', '/', $class );
	$file = 'src/' . $class . '.php';
	
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

$pdo = new PDO( 'sqlite::memory:' );
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$schema = <<<SQL
CREATE TABLE user ( 
	user_id INT PRIMARY KEY NOT NULL,
	name TEXT NOT NULL,
	email TEXT NOT NULL,
	role TEXT NOT NULL
);

INSERT INTO user VALUES ( 1, 'Daniel', 'daniel.kendell@gmail.com', 'admin' );
INSERT INTO user VALUES ( 2, 'Slartibartfast', 'slartibartfast@magrathea.hg', 'user' );
INSERT INTO user VALUES ( 3, 'Arthur Dent', 'arthur_dent@earth.hg', 'user' );
INSERT INTO user VALUES ( 4, 'Ford Prefect', 'fprefect@megadodo-publications.hg', 'user' );

CREATE TABLE note (
	note_id INT PRIMARY KEY NOT NULL,
	user_id INT NOT NULL,
	body TEXT NOT NULL
);

INSERT INTO note VALUES ( 1, 1, 'note one' );
INSERT INTO note VALUES ( 2, 1, 'note two' );
INSERT INTO note VALUES ( 3, 1, 'note three' );
INSERT INTO note VALUES ( 4, 1, 'note four' );
INSERT INTO note VALUES ( 5, 1, 'note five' );
INSERT INTO note VALUES ( 6, 1, 'note six' );
INSERT INTO note VALUES ( 7, 1, 'note seven' );
INSERT INTO note VALUES ( 8, 1, 'note eight' );
INSERT INTO note VALUES ( 9, 1, 'note nine' );
INSERT INTO note VALUES ( 10, 1, 'note ten' );
INSERT INTO note VALUES ( 11, 1, 'note eleven' );
INSERT INTO note VALUES ( 12, 1, 'note twelve' );
SQL;

$pdo->exec( $schema );