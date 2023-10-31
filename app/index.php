<?php

// Enabling Composer Packages
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use MongoDB\Database;

require __DIR__ . '/vendor/autoload.php';

$token = 'mytoken';
$org = 'myorg';
$bucket = 'mybucket';

$client = new InfluxDB2\Client([
    "url" => "http://influxdb:8086", // url and port of your instance
    "token" => $token,
    "bucket" => $bucket,
    "org" => $org,
    "precision" => InfluxDB2\Model\WritePrecision::NS,
]);
$writeApi = $client->createWriteApi();

$ops = 0;
$requestsSent = 0;
$startTime = microtime(true);



// Get environment variables
$local_conf = getenv();
define('DB_USERNAME', $local_conf['DB_USERNAME']);
define('DB_PASSWORD', $local_conf['DB_PASSWORD']);
define('DB_HOST', $local_conf['DB_HOST']);

// Connect to MongoDB
$db_client = new \MongoDB\Client('mongodb://'. DB_USERNAME .':' . DB_PASSWORD . '@'. DB_HOST . ':27017/');

//var_dump('......Mongo DB connection....');
//var_dump('mongodb://'. DB_USERNAME .':' . DB_PASSWORD . '@'. DB_HOST . ':27017/');

$db = $db_client->selectDatabase('metrics');

$uri = $_SERVER['REQUEST_URI'];

switch ($uri){
    case ($uri =='/insert'):
        insert($db,$writeApi, $bucket, $org);
        $point = Point::measurement('count')
            ->addTag('type', 'insert')
            ->addField('count', 1)
            ->time(microtime(true));

        $writeApi->write($point, WritePrecision::S, $bucket, $org);
        break;
    case ($uri == '/update'):
        update($db,$writeApi, $bucket, $org);
        $point = Point::measurement('count')
            ->addTag('type', 'update')
            ->addField('count', 1)
            ->time(microtime(true));

        $writeApi->write($point, WritePrecision::S, $bucket, $org);
        break;
    default:
        break;
}

function insert($db, $writeApi, $bucket, $org)
{
    $types = ['apple', 'book', 'glass', 'watch'];
    $count = random_int(1, 100);
    for ($document = 1; $document <= $count; $document++) {
        $type = $types[random_int(0, 3)];
        $time = time();
        $data = [
            'document_id' => $document,
            'title' => "Counter Document number: " . $document,
            'date' => date("m.d.y H:i:s"),
            'timestamp' => $time,
            'type' => $type,
            'mongodb_time' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        $insert = $db->pages->insertOne(
            [
                'document_id' => $document // query
            ],
            ['$set' => $data]
        );

        $point = Point::measurement('request')
            ->addTag('type', 'insert')
            ->addField('memory_usage', print_mem())
            ->time(microtime(true));

        $writeApi->write($point, WritePrecision::S, $bucket, $org);

    }
}

function update($db,$writeApi, $bucket, $org)
{
    $db->pages->createIndex(['document_id' => 1]);
    $types = ['apple', 'book', 'glass', 'watch'];
    $count = random_int(0, 100);
    for ($document = 1; $document <= $count; $document++) {
        $type = $types[random_int(0, 3)];
        $time = time();

        $data = [
            'document_id' => $document,
            'title' => "Counter Document number: " . $document,
            'date' => date("m.d.y H:i:s"),
            'timestamp' => $time,
            'type' => $type,
            'mongodb_time' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        $updateResult = $db->pages->updateOne(
            [
                'document_id' => $document // query
            ],
            ['$set' => $data],
            ['upsert' => true]
        );

    }
    $point = Point::measurement('request')
        ->addTag('type', 'update')
        ->addField('memory_usage', print_mem())
        ->time(microtime(true));
    $writeApi->write($point, WritePrecision::S, $bucket, $org);

    $point = Point::measurement('request')
        ->addTag('type', 'update')
        ->addField('count', print_mem())
        ->time(microtime(true));
    $writeApi->write($point, WritePrecision::S, $bucket, $org);
}

function print_mem()
{
    /* Currently used memory */
    $mem_usage = memory_get_usage();

    /* Peak memory usage */
    $mem_peak = memory_get_peak_usage();
   return  round($mem_peak / 1024);
}

echo print_mem();
echo '<br/>DONE';
exit;