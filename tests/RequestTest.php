<?php

use gdragffy\Drupal\OAuthWhisperer\Request;
use org\bovigo\vfs\vfsStream;

class RequestTest extends PHPUnit_Framework_TestCase
{
    private $faker;
    private $req;
    private $baseUrl;
    private $client;
    private $root;
    private $request;
    private $body;
    private $response;

    public function setUp()
    {
        $this->faker = Faker\Factory::create('en_GB');
        $this->baseUrl = $this->faker->url;
        $client = $this->getMockBuilder('\Guzzle\Http\Client')->getMock();
        $this->client = $client;

        $this->req = new gdragffy\Drupal\OAuthWhisperer\Request($this->baseUrl, $client);
        $this->root = vfsStream::setup('test');

        $this->request = $this->getMockBuilder('\Guzzle\Http\Message\Request')
                ->disableOriginalConstructor()
                ->getMock();

        $this->response = $this->getMockBuilder('\Guzzle\Http\Message\Response')
                ->disableOriginalConstructor()
                ->getMock();

        $this->body = $this->getMockBuilder('\Guzzle\Http\EntityBody')
                ->disableOriginalConstructor()
                ->getMock();

        $this->response->method('getBody')->willReturn($this->body);
        $this->request->method('send')->willReturn($this->response);
        $this->client->method('get')->willReturn($this->request);
    }

    public function test_it_can_be_contructed_with_a_url_and_client()
    {
        $baseUrl = $this->faker->url;
        $this->client
                ->expects($this->once())
                ->method('setBaseUrl')
                ->with($this->equalTo($baseUrl));
        $req = new gdragffy\Drupal\OAuthWhisperer\Request($baseUrl, $this->client);
        $this->assertInstanceOf('\gdragffy\Drupal\OAuthWhisperer\Request', $req);
        $this->assertEquals($baseUrl, $req->getBaseUrl());
        $this->assertSame($this->client, $req->getClient());
    }

    public function test_it_can_store_key_and_secret()
    {
        $key = $this->faker->md5;
        $secret = $this->faker->md5;
        $this->client
                ->expects($this->once())
                ->method('addSubscriber')
                ->with($this->isInstanceOf('\Guzzle\Plugin\Oauth\OauthPlugin'));
        $this->req->setKeyAndSecret($key, $secret);
    }

    public function test_it_can_make_a_get_request()
    {
        $uri = $this->faker->domainWord;
        $this->client
                ->expects($this->once())
                ->method('get')
                ->with($this->equalTo($this->baseUrl . '/' . $uri . '?CACHECANGOFUCKITSELF=' . time()), $this->equalTo(array('Accept' => '*/*')), $this->anything());
        $this->assertSame($this->response, $this->req->getRaw($uri));
    }

    public function test_it_can_write_a_get_request_to_file()
    {
        $uri = $this->faker->domainWord;
        $data = $this->faker->realText(300);
        $targetFile = $this->root->url() . '/target_file.txt';

        $this->response->method('isSuccessful')->willReturn(true);
        $this->body->method('feof')->will($this->onConsecutiveCalls(false, true)); # First call returns false, followed by true.
        $this->body->method('read')->willReturn($data);
        $this->body->method('getSize')->willReturn(strlen($data));

        $this->client
                ->expects($this->once())
                ->method('get')
                ->with($this->equalTo($this->baseUrl . '/' . $uri . '?CACHECANGOFUCKITSELF=' . time()), $this->equalTo(array('Accept' => '*/*')), $this->anything());

        $ret = $this->req->getToFile($uri, $targetFile);

        $this->assertFileExists($targetFile);
        $this->assertGreaterThan(0, $ret);
        $this->assertStringEqualsFile($targetFile, $data);
    }

    /**
     * @expectedException gdragffy\Drupal\OAuthWhisperer\RequestException
     */
    public function test_it_throws_request_exception_for_file_writing_on_request_failure()
    {
        $uri = $this->faker->domainWord;
        $targetFile = $this->root->url() . '/target_file.txt';
        $this->response->method('isSuccessful')->willReturn(false);
        $this->req->getToFile($uri, $targetFile);
        $this->assertFileNotExists($targetFile);
    }

    /**
     * @expectedException gdragffy\Drupal\OAuthWhisperer\FileException
     */
    public function test_it_throws_exception_on_file_perms()
    {
        $uri = $this->faker->domainWord;
        $targetFile = $this->root->chmod(0555); # Make it unwriteable
        $targetFile = $this->root->url() . '/' . $this->faker->word;
        $this->response->method('isSuccessful')->willReturn(true);
        $this->req->getToFile($uri, $targetFile);
    }

    public function test_it_can_return_a_stream_resource()
    {
        $uri = $this->faker->domainWord;
        $this->response->method('isSuccessful')->willReturn(true);
        $stream = $this->req->getAsStream($uri);
        $this->assertSame($this->body, $stream);
    }

    /**
     * @expectedException gdragffy\Drupal\OAuthWhisperer\RequestException
     */
    public function test_it_throws_request_exception_on_stream_reqeust_for_failed_request()
    {
        $uri = $this->faker->domainWord;
        $this->response->method('isSuccessful')->willReturn(false);
        $this->req->getAsStream($uri);
    }

}
