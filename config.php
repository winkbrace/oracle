<?php

return array(

    // put your database credentials here. DO NOT put this in any public version control system.
    // note that the database and schema names MUST BE capitalized. Password are case-dependent.
    'credentials' => array(
        // database
        'XE' => array(
            // username => password
            'TEST' => 'test',
        ),
    ),

    // database connection descriptions
    'connections' => array(
        'XE' => '127.0.0.1:1521/XE',
    ),

    'default_database' => 'XE',
    'default_schema' => 'TEST',

    // true enables logging, anything else disables logging
    'logging' => false,

    // Always validate sql statements before executing them
    // Enabling this will slow down all queries a little bit (about 100-200 ms.), so only use when you have a good reason
    'validate_sql_syntax' => false,
);
