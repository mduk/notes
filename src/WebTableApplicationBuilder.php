<?php

namespace Mduk;

use Mduk\Gowi\Factory;

class WebTableApplicationBuilder extends RoutedServiceApplicationBuilder {

  protected $tables = [];

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

  public function addTable( $table, $pk, $fields ) {
    $this->tables[ $table ] = [
      'table' => $table,
      'pk' => $pk,
      'fields' => $fields
    ];
  }

  public function configArray() {
    $transcoderConfig = [
      'transcoders' => [
        'application/json' => 'generic:json'
      ]
    ];

    $routes = [];
    foreach ( $this->tables as $table => $spec ) {

      // Retrieve whole collection
      $routes[ "/{$table}" ]['GET'] = [
        'service' => $this->service( $table, 'retrieveAll', 'many' ),
        'http' => [
          'response' => $transcoderConfig
        ]
      ];

      // Create new object
      $routes[ "/{$table}" ]['POST'] = [
        'service' => $this->service( $table, 'create', 'none' ),
        'bind' => [
          'required' => [
            'payload' => $spec['fields']
          ]
        ],
        'http' => [
          'request' => $transcoderConfig
        ]
      ];

      // Retrieve object
      $routes[ "/{$table}/{{$spec['pk']}}" ]['GET'] = [
        'service' => $this->service( $table, 'retrieve', 'one' ),
        'bind' => [
          'required' => [
            'route' => [ $spec['pk'] ]
          ]
        ],
        'http' => [
          'response' => $transcoderConfig
        ]
      ];

      // Update whole object
      $routes[ "/{$table}/{{$spec['pk']}}" ]['PUT'] = [
        'service' => $this->service( $table, 'update', 'none' ),
        'bind' => [
          'required' => [
            'route' => [ $spec['pk'] ],
            'payload' => $spec['fields']
          ]
        ],
        'http' => [
          'request' => $transcoderConfig
        ]
      ];

      // Update partial object
      $routes[ "/{$table}/{{$spec['pk']}}" ]['PATCH'] = [
        'service' => $this->service( $table, 'updatePartial', 'none' ),
        'bind' => [
          'required' => [
            'route' => [ $spec['pk'] ]
          ],
          'optional' => [
            'payload' => $spec['fields']
          ]
        ],
        'http' => [
          'request' => $transcoderConfig
        ]
      ];

      // Delete object
      $routes[ "/{$table}/{{$spec['pk']}}" ]['PATCH'] = [
        'service' => $this->service( $table, 'delete', 'none' ),
        'bind' => [
          'required' => [
            'route' => [ $spec['pk'] ]
          ]
        ],
      ];
    }

    return [
      'debug' => true,
      'transcoder' => $this->transcoderFactory,
      'pdo' => [
        'connections' => $this->pdoConnections,
        'services' => $this->pdoServices
      ],
      'routes' => $routes
    ];
  }

  public function build() {

    foreach ( $this->tables as $table => $spec ) {
      $pk = $spec['pk'];
      $fieldArray = $spec['fields'];
      $fieldArray[] = $spec['pk'];
      $fields = implode( ', ', $fieldArray );
      $placeholders = implode( ', ', array_map( function( $e ) {
        return ":{$e}";
      }, $fieldArray ) );

      $updatePlaceholders = implode( ', ', array_map( function( $e ) {
        return "{$e} = :{$e}";
      }, $this->fields ) );

      $wherePk = "WHERE {$pk} = :{$pk}";

      $this->addPdoService( $table, 'main', [
        'create' => [
          'sql' => "INSERT INTO {$table} ( {$fields} ) VALUES ( {$placeholders} )",
          'parameters' => $fields
        ],
        'retrieveAll' => [
          'sql' => "SELECT {$fields} FROM {$table}"
        ],
        'retrieve' => [
          'sql' => "SELECT {$fields} FROM {$table} {$wherePk}",
          'parameters' => [ $pk ]
        ],
        'update' => [
          'sql' => "UPDATE {$table} SET {$updatePlaceholders} {$wherePk}",
          'parameters' => $fields
        ],
        'updatePartial' => [
          'sql' => function( $parameters ) use ( $table, $pk, $wherePk ) {
            $fields = [];
            foreach ( $parameters as $parameter ) {
              if ( $parameter == $pk ) { // Mustn't change the primary key
                continue;
              }
              $fields[] = "{$parameter} = :{$parameter}";
            }
            $updateFields = implode( ', ', $fields );
            $sql = "UPDATE {$table} SET {$updateFields} {$wherePk}";
            return $sql;
          }
        ],
        'delete' => [
          'sql' => "DELETE FROM {$this->table} {$wherePk}",
          'parameters' => [ $pk ]
        ]
      ] );
    }

    return parent::build();
  }

  protected function service( $table, $call, $multiplicity ) {
    return [
      'name' => $table,
      'call' => $call,
      'multiplicity' => $multiplicity
    ];
  }

}

