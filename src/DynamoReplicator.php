<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Credentials\Credentials;
use Aws\Result;

class DynamoReplicator extends Command
{
    protected $signature = 'dynamo:import 
    {origin_table} {destination_table} 
    {--ok=} {--os=} 
    {--dk=} {--ds=}
    {--or=us-east-1}
    {--dr=us-east-1}
    {--oe=default}
    {--de=default}
    ';

    protected $description = 'Import DynamoDb table data from one table to another.';

    protected $outputContent = [];

    protected $origTable = true;
    protected $destTable = true;

    protected $origCredentials = true;
    protected $destCredentials = true;

    protected $origClient = true;
    protected $destClient = true;

    protected $origRegion = true;
    protected $destRegion = true;

    public function handle()
    {
        $this->bootstrap();
        try {
            $table = $this->retrieveTable('dest');
            $client = $this->retrieveClient('dest');

            $endpoint = $client->getEndpoint();

            if ($this->confirm("The contents of '$table' at '$endpoint' will be erased, continue?")) {
                $this->clearTable('dest');
            }

            $table = $this->retrieveTable('orig');
            $client = $this->retrieveClient('orig');

            $endpoint = $client->getEndpoint();

            if ($this->confirm("The new contents from '$table' at '$endpoint' will be inserted into the erased table, continue?")) {
                $this->line("Reading contents of '$table' at '$endpoint'");
                $this->line('');
                $result = $this->readTable('orig');
                $this->fillTable($result['Items'], 'dest');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function fillTable($items, $type): void
    {
        $this->line('Insertion initiated:');

        $bar = $this->output->createProgressBar(count($items));

        $bar->start();
        foreach ($items as $item) {
            $this->insertItem($item, $type);
            $bar->advance();
        }
        $bar->finish();

        $this->line('');
        $this->info('Table filled.');
    }

    protected function readTable($type): Result
    {
        $table = $this->retrieveTable($type);
        $client = $this->retrieveClient($type);
        $result = $client->scan([
            'TableName' => $table,
        ]);

        return $result;
    }

    protected function insertItem($item, $type): void
    {
        $table = $this->retrieveTable($type);
        $client = $this->retrieveClient($type);
        $params = [
            'TableName' => $table,
            'Item' => $item,
        ];

        try {
            $result = $client->putItem($params);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit();
        }
    }

    protected function clearTable($type): void
    {
        $this->line('Cleaning initiated:');

        $indexColumns = $this->getIndexColumns($type);
        $indexes = $this->getIndexes($indexColumns, $type);

        $bar = $this->output->createProgressBar(count($indexes));

        $bar->start();
        foreach ($indexes as $index) {
            $this->deleteItem($index, $type);
            $bar->advance();
        }
        $bar->finish();

        $this->line('');
        $this->info('Table data erased.');
    }

    protected function deleteItem($indexes, $type): void
    {
        $marshaler = new Marshaler();

        $key = $marshaler->marshalItem($indexes);
        $table = $this->retrieveTable($type);

        $params = [
            'TableName' => $table,
            'Key' => $key,
        ];

        $client = $this->retrieveClient($type);
        try {
            $result = $client->deleteItem($params);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit();
        }
    }

    protected function bootstrap(): void
    {
        $this->origTable = $this->argument('origin_table');
        $this->destTable = $this->argument('destination_table');

        $origKey = $this->option('ok');
        $destKey = $this->option('dk');

        $origSecret = $this->option('os');
        $destSecret = $this->option('ds');

        $this->origRegion = $this->option('or');
        $this->destRegion = $this->option('dr');

        $this->setCredentials($origKey, $origSecret, 'orig');
        $this->setCredentials($destKey, $destSecret, 'dest');

        $this->buildClient('orig', $this->option('oe'));
        $this->buildClient('dest', $this->option('de'));
    }

    protected function getIndexColumns($type): array
    {
        $client = $this->retrieveClient($type);
        $table = $this->retrieveTable($type);

        $result = $client->describeTable([
            'TableName' => $table,
        ]);

        $keys = [];
        foreach ($result['Table']['KeySchema'] as $key) {
            $keys[] = $key['AttributeName'];
        }

        return $keys;
    }

    protected function getIndexes($indexesColumns, $type): array
    {
        $result = $this->readTable($type);

        $indexes = [];
        $marshaler = new Marshaler();
        foreach ($result['Items'] as $key => $item) {
            foreach ($indexesColumns as $indexColumn) {
                $indexes[$key][$indexColumn] = $marshaler->unmarshalItem($item)[$indexColumn];
            }
        }

        return $indexes;
    }

    protected function setCredentials($key, $secret, $type): void
    {
        throw_if(
            !isset($this->{$type.'Credentials'}),
            'Exception',
            "Credential variable: '{$type}Credentials' not found"
        );
        $this->{$type.'Credentials'} = new Credentials($key, $secret);
    }

    protected function retrieveClient($type): DynamoDbClient
    {
        throw_if(
            !isset($this->{$type.'Client'}),
            'Exception',
            "Client variable: '{$type}Client' not found"
        );

        return $this->{$type.'Client'};
    }

    protected function retrieveTable($type): string
    {
        throw_if(
            !isset($this->{$type.'Table'}),
            'Exception',
            "Table variable: '{$type}Table' not found"
        );

        return $this->{$type.'Table'};
    }

    protected function buildClient($type, $endpoint = 'default'): void
    {
        throw_if(
            !isset($this->{$type.'Client'}),
            'Exception',
            "Client variable: '{$type}Client' not found"
        );
        throw_if(
            is_null($this->{$type.'Credentials'}),
            'Exception',
            "Credential variable: '{$type}Credentials' not set"
        );

        $params = [
            'version' => 'latest',
            'region' => $this->{$type.'Region'},
            'credentials' => $this->{$type.'Credentials'},
        ];
        if ('default' != $endpoint) {
            $params['endpoint'] = $endpoint;
        }

        $this->{$type.'Client'} = new DynamoDbClient($params);
    }
}
