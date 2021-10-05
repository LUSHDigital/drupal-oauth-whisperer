<?php

require "../vendor/autoload.php";

use gdragffy\Drupal\OAuthWhisperer\Request;
use gdragffy\Drupal\OAuthWhisperer\RequestException;
use gdragffy\Drupal\OAuthWhisperer\FileException;
use Guzzle\Http\Client;

$targetFile = '/home/gabrieldragffy/Downloads/test.txt';
$client = new Request('http://example.com', new Client());

try {
    $result = $client->getToFile('exampleresource/id/1', $targetFile);
} catch (FileException $ex) {
    exit($ex->getMessage());
} catch (RequestException $ex) {
    echo $ex->getMessage();
    echo "HTTP status: " . $ex->getCode();
}


if ($result) {
    echo file_get_contents($targetFile);
} else {
    exit('No data downloaded.');
}