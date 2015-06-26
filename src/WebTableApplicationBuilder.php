<?php

namespace Mduk;

use Mduk\Gowi\Factory;

class WebTableApplicationBuilder extends RoutedServiceApplicationBuilder {

  protected $table = '';
  protected $pk = '';
  protected $fields = [];

  public function __construct() {
    $this->transcoderFactory = new Factory( [
      'generic:json' => function() {
        return new \Mduk\Gowi\Transcoder\Generic\Json;
      }
    ] );
  }

  public function setPdoConnection( $dsn, $username = null, $password = null, $options = [] ) {
    parent::addPdoConnection( 'main', $dsn, $username, $password, $options );
  }

  public function setTable( $table ) {
    $this->table = $table;
  }

  public function setPrimaryKey( $pk ) {
    $this->pk = $pk;
  }

  public function setFields( array $fields = [] ) {
    $this->fields = $fields;
  }

  public function routeServiceCallWithRequestBodyConfig( $call, $multiplicity = 'many', $bind = [] ) {
    $config = $this->routeServiceCallConfig( $call, $multiplicity, $bind );
    $config['bind']['payload'] = $this->fields;
    $config['http']['request']['transcoders']['application/json'] = 'generic:json';
    return $config;
  }

  public function routeServiceCallConfig( $call, $multiplicity = 'many', $bind = [] ) {
    $bind = [ 'route' => $bind ];

    return [
      'service' => [
        'name' => 'table',
        'call' => $call,
        'multiplicity' => $multiplicity
      ],
      'bind' => $bind,
      'http' => [
        'response' => [
          'transcoders' => [
            'application/json' => 'generic:json'
          ]
        ]
      ]
    ];
  }

  public function configArray() {
    $c = [
      'debug' => true,
      'transcoder' => $this->transcoderFactory,
      'pdo' => [
        'connections' => $this->pdoConnections,
        'services' => $this->pdoServices
      ],
      'routes' => [
        "/{$this->table}" => [
          'GET' => $this->routeServiceCallConfig( 'retrieveAll' ),
          'POST' => $this->routeServiceCallWithRequestBodyConfig( 'create', 'none' )
        ],
        "/{$this->table}/{$this->pk}" => [
          'GET' => $this->routeServiceCallConfig( 'retrieve', 'one', [ $this->pk ] ),
          'PUT' => $this->routeServiceCallConfig( 'update', 'one', [ $this->pk ] ),
          'PATCH' => $this->routeServiceCallConfig( 'update', 'one', [ $this->pk ] ),
          'DELETE' => $this->routeServiceCallConfig( 'delete', 'none', [ $this->pk ] )
        ]
      ]
    ];
    return $c;
  }

  public function build() {
    $fieldArray = $this->fields;
    $fieldArray[] = $this->pk;
    $fields = implode( ', ', $fieldArray );
    $placeholders = implode( ', ', array_map( function( $e ) {
      return ":{$e}";
    }, $fieldArray ) );
    $this->addPdoService( 'table', 'main', [
      'create' => [
        'sql' => "INSERT INTO {$this->table} ( {$fields} ) VALUES ( {$placeholders} )",
        'parameters' => $fields
      ],
      'retrieveAll' => [
        'sql' => "SELECT {$fields} FROM {$this->table}"
      ],
      'retrieve' => [
        'sql' => "SELECT {$fields} FROM {$this->table} WHERE {$this->pk} = :{$this->pk}",
        'parameters' => [ $this->pk ]
      ],
      'delete' => [
        'sql' => "DELETE FROM {$this->table} WHERE {$this->pk} = :{$this->pk}",
        'parameters' => [ $this->pk ]
      ]
    ] );

    return parent::build();
  }

}

