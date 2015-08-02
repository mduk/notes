<?php

return [
  [
    'builder' => 'static-page',
    'location' => '/',
    'config' => [
      'template' => 'index'
    ]
  ],
  [
    'builder' => 'static-page',
    'location' => '/about',
    'config' => [
      'template' => 'about'
    ]
  ],
  [
    'builder' => 'card',
    'location' => '/tkt/card/foo', 
    'config' => [
      'service' => [
        'name' => 'foo',
        'call' => 'bar',
        'multiplicity' => 'many'
      ],
      'template' => 'foobar'
    ]
  ],
  [
    'builder' => 'webtable',
    'location' => '/api/tables/user',
    'config' => [
      'connection' => [
        'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
      ],
      'table' => 'user',
      'pk' => 'user_id',
      'fields' => [ 'name', 'email', 'role' ]
    ]
  ],
  [
    'builder' => 'service-invocation',
    'location' => [ 'GET', '/users' ],
    'config' => [
      'pdo' => [
        'connections' => [
          'maindb' => [
            'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
          ],
        ],
        'services' => [
          'user' => [
            'connection' => 'maindb',
            'queries' => [
              'getAll' => [
                'sql' => 'SELECT * FROM user'
              ]
            ]
          ]
        ]
      ],
      'service' => [
        'name' => 'user',
        'call' => 'getAll',
        'multiplicity' => 'many',
      ],
      'http' => [
        'response' => [
          'transcoders' => [
            'text/html' => 'template:user_list',
            'application/json' => 'generic:json'
          ]
        ]
      ]
    ]
  ],
  [
    'builder' => 'service-invocation',
    'location' => [ 'GET', '/users/{user_id}' ],
    'config' => [
      'pdo' => [
        'connections' => [
          'maindb' => [
            'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
          ],
        ],
        'services' => [
          'user' => [
            'connection' => 'maindb',
            'queries' => [
              'getById' => [
                'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
                'required' => [ 'user_id' ]
              ]
            ]
          ]
        ]
      ],
      'service' => [
        'name' => 'user',
        'call' => 'getById',
        'multiplicity' => 'one',
      ],
      'bind' => [
        'required' => [
          'route' => [ 'user_id' ]
        ]
      ],
      'http' => [
        'response' => [
          'transcoders' => [
            'text/html' => 'template:user_page',
            'application/json' => 'generic:json'
          ]
        ]
      ]
    ]
  ],
  [
    'builder' => 'service-invocation',
    'location' => [ 'GET', '/users/{user_id}/notes' ],
    'config' => [
      'pdo' => [
        'connections' => [
          'maindb' => [
            'dsn' => 'sqlite:/Users/daniel/dev/notes/db.sq3'
          ],
        ],
        'services' => [
          'user' => [
            'connection' => 'maindb',
            'queries' => [
              'getById' => [
                'sql' => 'SELECT * FROM user WHERE user_id = :user_id',
                'required' => [ 'user_id' ]
              ]
            ]
          ],
          'note' => [
            'connection' => 'maindb',
            'queries' => [
              'getByUserId' => [
                'sql' => 'SELECT * FROM note WHERE user_id = :user_id',
                'required' => [ 'user_id' ]
              ]
            ]
          ]
        ]
      ],
      'service' => [
        'name' => 'note',
        'call' => 'getByUserId',
        'multiplicity' => 'many',
      ],
      'bind' => [
        'required' => [
          'route' => [ 'user_id' ]
        ]
      ],
      'context' => [
        'user' => [
          'service' => [
            'name' =>'user',
            'call' => 'getById',
          ],
        ]
      ],
      'http' => [
        'response' => [
          'transcoders' => [
            'text/html' => 'template:note_list',
            'application/json' => 'generic:json'
          ]
        ]
      ]
    ]
  ]
];
