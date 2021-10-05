<?php

/*
 * This file is part of Gabriel Dragffy's DrupalOAuthWhisperer package.
 *
 * (c) Gabriel Dragffy <gabe@dragffy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace gdragffy\Drupal\OAuthWhisperer;

/**
 * Description of Request
 *
 * @example get_as_stream.php Example usage of this class to retrieve a stream resource.
 * @example get_to_file.php Example usage of using this class to download to a file.
 * @author Gabriel Dragffy <gabe@dragffy.com>
 */
class Request
{
    private $baseUrl;
    private $client;
    private $debug;

    /**
     * Constructor requires final url endpoint to send request to as
     * first parameter. Optional second argument is a client object of type
     * Guzzle\Http\Client.
     *
     *
     * @param string $baseUrl Host to make requests to e.g. https://www.lush.co.uk
     * @param \Guzzle\Http\Client $client Instance of Guzzle client object to use.
     * @param bool $debug If set to true then debug messages will be emitted by the underlying Guzzle client
     */
    public function __construct($baseUrl, \Guzzle\Http\Client $client, $debug = false)
    {
        $this->baseUrl = $baseUrl;
        $client->setBaseUrl($this->baseUrl);
        $this->client = $client;
        $this->debug = (bool) $debug;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns the underlying Guzzle Http Client instance being used.
     *
     * @return \Guzzle\Http\Client $client Instance of Guzzle client object
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * This method *must* be called prior to any requests being made. Pass
     * in your OAuth key as the first argument and your OAuth secret as the second.
     *
     * @param string $key OAuth key (consumer key)
     * @param string $secret OAuth secret (consumer secret)
     */
    public function setKeyAndSecret($key, $secret)
    {
        $this->client->addSubscriber(new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                'consumer_key' => $key,
                'consumer_secret' => $secret
        )));
    }

    /**
     * Method will perform a basic get request using it's Guzzle client and return
     * the Guzzle response as-is. Please see other methods such as getFile()
     * or getStream() to have the response written to a file or the stream returned.
     *
     * @param string $uri Endpoint to hit on the base url e.g. 'jargen/2014-09-01'
     * @return \Guzzle\Http\Message\Response $response Instance of Guzzle reponse
     */
    public function getRaw($uri)
    {
        $uri = $this->baseUrl . '/' . $uri;

        $prefix = '?';
        if (strpos($uri, '?')) {
            $prefix = '&';
        }
        $uri .= $prefix . 'CACHECANGOFUCKITSELF=' . time();

        $request = $this->client->get(
                $uri, array('Accept' => '*/*'), array('debug' => $this->debug, 'exceptions' => false)
        );
        $response = $request->send();
        return $response;
    }

    /**
     * Convenience method that will perform a request and save the response body
     * in to a given file path all at once. This can handle even large responses
     * since it only reads 1MB at a time to conserve memory.
     *
     * Throws FileException if the destination file cannot be written.
     *
     * Throws RequestException if the request fails. The exception object code
     * will be the HTTP response code, the exception object message will be the
     * response body, with the requested URL prepended.
     *
     * @param string $uri Endpoint to request on the base url e.g. 'roger/headers'
     * @param string $targetFile Full path to the output file you want e.g. /home/gdragffy/downloads/outputfile.txt
     * @return int Number of bytes written to file.
     * @throws FileException
     * @throws RequestException
     */
    public function getToFile($uri, $targetFile)
    {
        $ret = 0;
        $response = $this->getRaw($uri);

        if (!is_writeable(dirname($targetFile))) {
            throw new FileException(dirname($targetFile) . " unwriteable");
        }

        if ($response->isSuccessful()) {
            $body = $response->getBody();
            $body->rewind();
            $fh = fopen($targetFile, 'wb');

            while (!$body->feof()) {
                $data = $body->read(1024);
                if (!$data) {
                    return 0;
                }
                fwrite($fh, $data, strlen($data));
            }
            $ret = $body->getSize();
            fclose($fh);
        } else {
            throw new RequestException($response->getReasonPhrase() . " : " . $response->getEffectiveUrl(), $response->getStatusCode());
        }

        return $ret;
    }

    /**
     * Will make a request and return the response body as a Guzzle\Stream\StreamInterface object.
     * This allows you to further manipulate the response, e.g. $ret->ftell(), $ret->seek(), $ret->read(1024), $ret->feof()
     *
     * Throws RequestException if the request fails. The exception object code
     * will be the HTTP response code, the exception object message will be the
     * response body, with the requested URL prepended.
     *
     * @link http://api.guzzlephp.org/class-Guzzle.Http.EntityBody.html Guzzle HTTP EntityBody API Documentation
     * @param string $uri Endpoint to request on the base url e.g. 'roger/headers'
     * @return bool|\Guzzle\Http\EntityBody False on request failure, something that impliments Guzzle\Stream\StreamInterface on success.
     * @throws RequestException
     */
    public function getAsStream($uri)
    {
        $ret = FALSE;
        $response = $this->getRaw($uri);
        if ($response->isSuccessful()) {
            $ret = $response->getBody();
        } else {
            throw new RequestException($response->getReasonPhrase() . " : " . $response->getEffectiveUrl(), $response->getStatusCode());
        }

        return $ret;
    }

}
