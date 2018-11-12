# dynamoReplicator
A simple copy paste for AWS DynamoDB Table Items for Laravel/Lumen
It helps when you need to copy a dump of DynamoDB table content and import to another table, location or account.

## Installation

The Dynamo Replicator can be installed via [Composer](http://getcomposer.org) by requiring the
`leandroperini/dynamo-replicator` package in your project's `composer.json`.

```json
{
    "require": {
        "leandroperini/dynamo-replicator": "^1.0.3"
    }
}
```

Then run a composer update
```sh
php composer.phar update
```
Or just execute the command bellow:
```sh
composer require leandroperini/dynamo-replicator
```


To use the Dynamo Replicator, you must register the provider when bootstrapping your application.


### Lumen
In Lumen find the `Register Service Providers` in your `bootstrap/app.php` and register the Dynamo Replicator Service Provider.

```php
    $app->register(LeandroPerini\DynamoReplicator\DynamoReplicatorServiceProvider::class);
```

### Laravel
In Laravel find the `providers` key in your `config/app.php` and register the Dynamo Replicator Service Provider.

```php
    'providers' => array(
        // ...
        LeandroPerini\DynamoReplicatorServiceProvider::class,
    )
```
## Basic Usage

```sh
php artisan dynamo:import origin-table-name destination-table-name 
--ok=origin_aws_key --os=origin_aws_secret 
--dk=destiation_aws_key --ds=destiation_aws_secret 
--oe=origin_endpoint
--de=destination_endpoint
--or=origin-region
--dr=destination-region
```
### Options

* --ok -> Origin AWS Key | ***Required***. 
* --os -> Origin AWS Secret | ***Required***. 
* --oe -> Origin Endpoint | **Optional**. *Defaults to AWS* 
* --or -> Origin AWS Region | **Optional**. *Default: us-east-1*
* --dk -> Destination AWS Key | ***Required***. 
* --ds -> Destination AWS Secret | ***Required***. 
* --de -> Destination Endpoint | **Optional**. *Defaults to AWS* 
* --dr -> Destination AWS Region | **Optional**. *Default: us-east-1*
