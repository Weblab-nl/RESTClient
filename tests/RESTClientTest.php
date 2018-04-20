<?php

namespace Weblab\RESTClient;

use Weblab\RESTClient\Tests\TestCase;
use Weblab\CURL\Result;
use Weblab\RESTClient\Adapters\AdapterInterface;
use Weblab\RESTClient\Exceptions\NoAdapterException;
use Weblab\RESTClient\Exceptions\ResponseHandlerNotFoundException;

/**
 * Class RESTClientTest
 * @author Weblab.nl - Eelco Verbeek
 */
class RESTClientTest extends TestCase {

    public function testGetNoAdapterException() {
        $this->expectException(NoAdapterException::class);
        $client = new RESTClient();
        $client->get('/users/1');
    }

    public function testGetResponseHandlerNotFound() {
        $adapter = $this->prophesize(AdapterInterface::class);

        $expectedResult = new Result('', 400, '');

        $adapter->doRequest('get', 'api.com/users', [], [], [])
            ->shouldBeCalled()
            ->willReturn($expectedResult);

        $this->expectException(ResponseHandlerNotFoundException::class);

        $client = (new RESTClient)
            ->setAdapter($adapter->reveal())
            ->setBaseURL('api.com')
            ->registerResponseHandler(400, 'nonExistingFunction');

        $client->get('/users');
    }

    /**
     * @dataProvider getSuccessProvider
     */
    public function testGetSuccess($baseURL, $url, $expectedURL, $params, $status) {
        $adapter = $this->prophesize(AdapterInterface::class);

        $expectedResult = new Result('', $status, '');

        $adapter->doRequest('get', $expectedURL, $params, [], [])
            ->shouldBeCalled()
            ->willReturn($expectedResult);

        $client = new RESTClient;
        $client->setBaseURL($baseURL);
        $client->setAdapter($adapter->reveal());
        $result = $client->get($url, $params);

        $this->assertEquals($expectedResult, $result);
    }

    public function getSuccessProvider() {
        return [
            ['api.com/', '/users/1', 'api.com/users/1', ['test' => 'test'], 200],
            ['api.com', '/users/1', 'api.com/users/1', ['test' => 'test'], 403],
            ['api.com', 'users/1', 'api.com/users/1', ['test' => 'test'], 500],
            ['api.com/', 'users/1', 'api.com/users/1', ['test' => 'test'], 201],
        ];
    }

    public function testGetSuccessWithCustomResponseHandler() {
        $adapter = $this->prophesize(AdapterInterface::class);

        $expectedResult = new Result('', 400, '');

        $adapter->doRequest('get', 'api.com/users', [], [], [])
            ->shouldBeCalled()
            ->willReturn($expectedResult);

        $client = (new RESTClient)
            ->setAdapter($adapter->reveal())
            ->setBaseURL('api.com')
            ->registerResponseHandler(400, function($result, ...$args) {
                return $result;
            });

        $result = $client->get('/users');

        $this->assertEquals($expectedResult, $result);
    }

}
