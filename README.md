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

##### Create example

```
mkdir -p /home/cesar/myproject-cesar-migrator-config/queue/executed
```