<?php
namespace Bot;
require __DIR__ . '/../../vendor/autoload.php';

use \Bot\Coincecko\Client;

//$clientHttp = new GuzzleHttp\Client();

//$clientHttp->get('/', ['verify' => true]);
//$client = new CoinGeckoClient($clientHttp);
//$data = $client->ping();


//require __DIR__ . '/../CoinGecko/Base.php';
$client = new Client();
$client->checkRateAll();
//print_r($data);