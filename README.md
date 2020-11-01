# Cesar migrator SQL processor

## Installing

### Global install in Linux

#### Requirements
* Composer
* PHP


You can get Cesar Migrator in here [Packagist](https://packagist.org/packages/cesar/cesar-migrator)


#### Install with Composer

```
sudo rm -rf ~/cesar_migrator_tmp
sudo rm -rf /opt/cesar_migrator
sudo rm -rf /usr/bin/cesar-migrator 

mkdir ~/cesar_migrator_tmp
composer create-project cesar/cesar-migrator ~/cesar_migrator_tmp -s dev
sudo mkdir -p /opt/cesar_migrator
sudo mv ~/cesar_migrator_tmp/* /opt/cesar_migrator/
sudo ln -s /opt/cesar_migrator/cesar-migrator /usr/bin/

```

## Using

### Config file

```
return [
    [
        "databases" => [
            [
                "type" => "mysql",
                "host" => "localhost",
                "port" => 3306,
                "name" => "",
                "user" => "",
                "password" => ""
            ]
        ],
        "mainfolder" => "", // Pool of queries, in here are inserted new query files
        "mainfile" => "", // Constant queries, this query is executed every time
        "queuefoldername" => "" // Queries of environment, directory used by Cesar migrator, do not require user interaction. 
    ]
];

```
#### Skeleton required by queuefoldername

queue/executed

##### Example:

```
/home/cesar/myproject-cesar-migrator-config/queue

/home/cesar/myproject-cesar-migrator-config/queue/executed
```
##### Create example
```
mkdir -p /home/cesar/myproject-cesar-migrator-config/queue/executed

```

### Update

```
cesar@t-rex:~$cd /opt/cesar_migrator
cesar@t-rex:/opt/cesar_migrator$ composer update
Loading composer repositories with package information
Updating dependencies (including require-dev)
Nothing to install or update
Generating autoload files

```

#### Command

##### Help param -h

```
cesar@t-rex:~$ ./cesar-migrator -h

 SQL Processor v3.0 - by Edily Cesar Medule (edilycesar@gmail.com)

-n Create file for new query
 - a Define the query author
 - d Define a query description
 - r Run the queries
 - f Config file
 - c Validate runs on all databases
 - h Help
 - m Manual / Documentation (Future)
 - k Clear all, database control table and directory contents

Example:
 ./cesar-migrator -a Cesar -d Query marotinha -r

```

























```