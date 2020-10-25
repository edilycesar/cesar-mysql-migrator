<?php

/* QUERY EXAMPLE:
  CREATE USER 'cesar_migrator_sample_user'@'%' IDENTIFIED BY 'p422w0r0';
  GRANT ALL PRIVILEGES ON *.* TO 'cesar_migrator_sample_user'@'%'  WITH GRANT OPTION;
  FLUSH PRIVILEGES;
 */

$configs = [
    [
        "databases" => [
            [
                "type" => "mysql",
                "host" => "localhost",
                "port" => 3306,
                "name" => "cesar_migrator_sample_1",
                "user" => "cesar_migrator_sample_user",
                "password" => "p422w0r0"
            ],
            [
                "type" => "mysql",
                "host" => "localhost",
                "port" => 3306,
                "name" => "cesar_migrator_sample_2",
                "user" => "cesar_migrator_sample_user",
                "password" => "p422w0r0"
            ],
            [
                "type" => "mysql",
                "host" => "localhost",
                "port" => 3306,
                "name" => "cesar_migrator_sample_3",
                "user" => "cesar_migrator_sample_user",
                "password" => "p422w0r0"
            ]
        ],
        "mainfolder" => "/var/www/html/cesar-migrator/cesar-migrator-queries/sql-pool", // Pool of queries
        "mainfile" => "/var/www/html/cesar-migrator/cesar-migrator-queries/main.sql", // Constant queries
        "queuefoldername" => "/var/www/html/cesar-migrator/cesar-migrator-queries/sql-dev" // Queries of environment
    ]
];
