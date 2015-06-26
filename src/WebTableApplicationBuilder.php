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

  public function configArray() {
    $transcoderConfig = [
      'transcoders' => [
        'application/json' => 'generic:json'
      ]
    ];

    return [
      'debug' => true,
      'transcoder' => $this->transcoderFactory,
      'pdo' => [
        'connections' => $this->pdoConnections,
        'services' => $this->pdoServices
      ],
      'routes' => [

        "/{$this->table}" => [

          // Retrieve whole collection
          'GET' => [
            'service' => $this->service( 'retrieveAll', 'many' ),
            'http' => [
              'response' => $transcoderConfig
            ]
          ],

          // Create new object
          'POST' => [
            'service' => $this->service( 'create', 'none' ),
            'bind' => [
              'required' => [
                'payload' => $this->fields
              ]
            ],
            'http' => [
              'request' => $transcoderConfig
            ]
          ]

        ],

        "/{$this->table}/{{$this->pk}}" => [

          // Retrieve object
          'GET' => [
            'service' => $this->service( 'retrieve', 'one' ),
            'bind' => [
              'required' => [
                'route' => [ $this->pk ]
              ]
            ],
            'http' => [
              'response' => $transcoderConfig
            ]
          ],

          // Update whole object
          'PUT' => [
            'service' => $this->service( 'update', 'none' ),
            'bind' => [
              'required' => [
                'route' => [ $this->pk ],
                'payload' => $this->fields
              ]
            ],
            'http' => [
              'request' => $transcoderConfig
            ]
          ],

          // Update partial object
          'PATCH' => [
            'service' => $this->service( 'updatePartial', 'none' ),
            'bind' => [
              'required' => [
                'route' => [ $this->pk ]
              ],
              'optional' => [
                'payload' => $this->fields
              ]
            ],
            'http' => [
              'request' => $transcoderConfig
            ]
          ],

          // Delete object
          'DELETE' => [
            'service' => $this->service( 'delete', 'none' ),
            'bind' => [
              'required' => [
                'route' => [ $this->pk ]
              ]
            ],
          ]

        ]
      ]
    ];
  }

  public function build() {
    $table = $this->table;
    $pk = $this->pk;
    $fieldArray = $this->fields;
    $fieldArray[] = $this->pk;
    $fields = implode( ', ', $fieldArray );
    $placeholders = implode( ', ', array_map( function( $e ) {
      return ":{$e}";
    }, $fieldArray ) );

    $updatePlaceholders = implode( ', ', array_map( function( $e ) {
      return "{$e} = :{$e}";
    }, $this->fields ) );

    $wherePk = "WHERE {$pk} = :{$pk}";

    $this->addPdoService( 'table', 'main', [
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

    return parent::build();
  }

  protected function service( $call, $multiplicity ) {
    return [
      'name' => 'table',
      'call' => $call,
      'multiplicity' => $multiplicity
    ];
  }

}

