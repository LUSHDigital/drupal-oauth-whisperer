<?php

require "../vendor/autoload.php";

use gdragffy\Drupal\OAuthWhisperer\Request;
use gdragffy\Drupal\OAuthWhisperer\RequestException;
use Guzzle\Http\Client;

$client = new Request('http://example.com', new Client());
try {
    $stream = $client->getAsStream('exampleresource/id/1');
} catch (RequestException $ex) {
    echo $ex->getMessage();
    echo "HTTP status: " . $ex->getCode();
    exit;
}

if ($stream->getSize()) {
    while (!$stream->feof()) {
        $data = $stream->read(1024);
        # Do something with the data
    }
} else {
    exit('No data downloaded.');
}