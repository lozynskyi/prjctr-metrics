<?php

// Enabling Composer Packages
require __DIR__ . '/vendor/autoload.php';

$statsd = new League\StatsD\Client();
$statsd->configure([
    'host'      => '127.0.0.1',
    'port'      => 8125,
    'namespace' => 'performance'
]);

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

$db = $db_client->selectDatabase('metrics');
// Create an index
$db->pages->createIndex(['document_id' => 1]);

$types = ['apple', 'book', 'glass', 'watch'];

// Test insert data
for ($document = 1; $document <= 1000; $document++) {
    $type = $types[random_int(0 , 3)];
    $time = time();
    $count = random_int(0 , 100);
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
    $statsd->increment('request.successful.count,type=' . $type, $count);
    $statsd->timing('request.successful.time,type=' . $type, $time);

    //echo $document . " " ;
}
echo '<br/>DONE';
exit;