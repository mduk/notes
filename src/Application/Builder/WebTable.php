<?php

namespace Mduk\Application\Builder;

use Mduk\Application\Builder as AppBuilder;

use Mduk\Gowi\Http\Application;
use Mduk\Gowi\Factory as GowiFactory;

class WebTable extends AppBuilder {

  protected $table;
  protected $pk;
  protected $fields;
  protected $connectionConfig;
  protected $transcoderFactory;

  public function __construct( array $config = [] ) {
    $this->transcoderFactory = new GowiFactory( [
      'generic:json' => function() {
        return new \Mduk\Gowi\Transcoder\Generic\Json;
      }
    ] );
  }

  public function buildRoutes( $path, $config ) {
    $this->connectionConfig = $config['connection'];
    $this->table = $config['table'];
    $this->pk = $config['pk'];
    $this->fields = $config['fields'];

    $appConfig = [
      'builders' => [ 'webtable' ]
    ];

    $routes = [];

    // Retrieve whole collection
    $routes[ $path ]['GET'] = $this->routeConfig( 'response', [
      'service' => $this->service( $this->table, 'retrieveAll', 'many' ),
    ] );

    // Create new object
    $routes[ $path ]['POST'] = $this->routeConfig( 'request', [
      'service' => $this->service( $this->table, 'create', 'none' ),
      'bind' => [
        'required' => [
          'payload' => $this->fields
        ]
      ],
    ] );

    // Retrieve object
    $routes[ "{$path}/{{$this->pk}}" ]['GET'] = $this->routeConfig( 'response', [
      'service' => $this->service( $this->table, 'retrieve', 'one' ),
      'bind' => [
        'required' => [
          'route' => [ $this->pk ]
        ]
      ],
    ] );

    // Update whole object
    $routes[ "{$path}/{{$this->pk}}" ]['PUT'] = $this->routeConfig( 'request', [
      'service' => $this->service( $this->table, 'update', 'none' ),
      'bind' => [
        'required' => [
          'route' => [ $this->pk ],
          'payload' => $this->fields
        ]
      ],
    ] );

    // Update partial object
    $routes[ "{$path}/{{$this->pk}}" ]['PATCH'] = $this->routeConfig( 'request', [
      'service' => $this->service( $this->table, 'updatePartial', 'none' ),
      'bind' => [
        'required' => [
          'route' => [ $this->pk ]
        ],
        'optional' => [
          'payload' => $this->fields
        ]
      ],
    ] );

    // Delete object
    $routes[ "{$path}/{{$this->pk}}" ]['DELETE'] = $this->routeConfig( null, [
      'service' => $this->service( $this->table, 'delete', 'none' ),
      'bind' => [
        'required' => [
          'route' => [ $this->pk ]
        ]
      ],
    ] );

    return $routes;
  }

  public function build( Application $app = null, array $config = [] ) {
    $pdo = new \PDO(
      $config['connection']['dsn'],
      isset( $config['connection']['username'] ) ? $config['connection']['username'] : '',
      isset( $config['connection']['password'] ) ? $config['connection']['password'] : '',
      isset( $config['connection']['options'] ) ? $config['connection']['options'] : []
    );
    $pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

    $app = new \Mduk\Gowi\Http\Application( '.' );
    $app->setConfig( 'debug', true );
    $app->addStage( new \Mduk\Application\Stage\SelectResponseType );
    $app->addStage( new \Mduk\Application\Stage\SelectRequestTranscoder);
    $app->addStage( new \Mduk\Application\Stage\InitResponseTranscoder);
    $app->addStage( new \Mduk\Application\Stage\DecodeRequestBody);

    $app->addStage( new \Mduk\Application\Stage\InitPdoServices );
    $app->addStage( new \Mduk\Application\Stage\BindServiceRequestParameters );
    $app->addStage( new \Mduk\Application\Stage\ResolveServiceRequest );
    $app->addStage( new \Mduk\Application\Stage\ExecuteServiceRequest );
    $app->addStage( new \Mduk\Application\Stage\EncodeServiceResponse );
    $app->addStage( new \Mduk\Application\Stage\Respond );

    $app->setConfigArray( $config );
    return $app;
  }

  protected function routeConfig( $http, $config ) {
    $route = [
      'builder' => 'webtable',
      'config' => array_replace_recursive( [
        'transcoder' => $this->transcoderFactory,
        'connection' => $this->connectionConfig,
        'pdo' => [
          'connections' => [
            'main' => $this->connectionConfig
          ],
          'services' => [
            $this->table => [
              'connection' => 'main',
              'queries' => $this->queryConfig()
            ]
          ]
        ]
      ], $config )
    ];

    if ( $http ) {
      $route['config']['http'][ $http ] = [
        'transcoders' => [
          'application/json' => 'generic:json'
        ]
      ];
    }
    
    return $route;
  }

  protected function queryConfig() {
    $allFields = $this->fields;
    $allFields[] = $this->pk;

    $allFieldsStr = implode( ', ', $allFields );

    $allPlaceholdersStr = implode( ', ', array_map( function( $e ) {
      return ":{$e}";
    }, $allFields ) );

    $updatePlaceholders = implode( ', ', array_map( function( $e ) {
      return "{$e} = :{$e}";
    }, $this->fields ) );

    $wherePk = "WHERE {$this->pk} = :{$this->pk}";

    return [
      'create' => [
        'sql' => "INSERT INTO {$this->table} ( {$allFieldsStr} ) VALUES ( {$allPlaceholdersStr} )",
        'parameters' => $this->fields
      ],
      'retrieveAll' => [
        'sql' => "SELECT {$allFieldsStr} FROM {$this->table}"
      ],
      'retrieve' => [
        'sql' => "SELECT {$allFieldsStr} FROM {$this->table} {$wherePk}",
        'parameters' => [ $this->pk ]
      ],
      'update' => [
        'sql' => "UPDATE {$this->table} SET {$updatePlaceholders} {$wherePk}",
        'parameters' => $this->fields
      ],
      'updatePartial' => [
        'sql' => function( $parameters ) use ( $wherePk ) {
          $this->fields = [];
          foreach ( $parameters as $parameter ) {
            if ( $parameter == $this->pk ) { // Mustn't change the primary key
              continue;
            }
            $this->fields[] = "{$parameter} = :{$parameter}";
          }
          $updateFields = implode( ', ', $this->fields );
          $sql = "UPDATE {$this->table} SET {$updateFields} {$wherePk}";
          return $sql;
        }
      ],
      'delete' => [
        'sql' => "DELETE FROM {$this->table} {$wherePk}",
        'parameters' => [ $this->pk ]
      ]
    ];
  }

  protected function service( $table, $call, $multiplicity ) {
    return [
      'name' => $this->table,
      'call' => $call,
      'multiplicity' => $multiplicity
    ];
  }

}

