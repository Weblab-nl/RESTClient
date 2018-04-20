<?php

namespace Weblab\RESTClient\Adapters;

use Weblab\RESTClient\Exceptions\OAuthException;
use Weblab\RESTClient\Tests\TestCase;
use Weblab\CURL\CURL;
use Weblab\CURL\Request;
use Weblab\CURL\Result;

/**
 * Class OAuthTest
 * @author Weblab.nl - Eelco Verbeek
 */
class OAuthTest extends TestCase {

    /**
     * @runInSeparateProcess
     */
    public function testDoRequestSuccess() {
        // set the expected result
        $expectedResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        //  set some parameters
        $params = ['test'];
        $url = 'api.com/users';
        $type = 'get';

        // create a mock request object
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        // perform the requestmock and assert it returns the expected result
        $requestMock
            ->expects($this->once())
            ->method('get')
            ->with('api.com/users', $params)
            ->willReturn($expectedResult);

        // set the post result mock object
        $postResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        // perform the postmock request and assert it returns an access token
        $postResult
            ->expects($this->once())
            ->method('getResult')
            ->willReturn(['access_token' => 'token']);

        // perform the postmock result and assert it return the expected 200 status
        $postResult
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        // create a mock curl-call object
        $curlMock = \Mockery::mock('overload:' . CURL::class);

        // do the mock curl call and assert it returns the post result
        $curlMock
            ->shouldReceive('post')
            ->andReturns($postResult);

        // do the curl mock call and assert it returns the request mock
        $curlMock
            ->shouldReceive('setBearer')
            ->withArgs(['token'])
            ->andReturns($requestMock);

        // create the OAUTH class
        $adapter = new OAuth();

        // Do the OAUTH call to test the call
        $result = $adapter->doRequest($type, $url, $params);

        // test if the result equals the expected result
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDoRequestNoAccessToken() {
        // set the mock Result object
        $postResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        // set the mock curl object, overriding the curl class object
        $curlMock = \Mockery::mock('overload:' . CURL::class);

        // the mock should return the postresult if given post method
        $curlMock
            ->shouldReceive('post')
            ->andReturns($postResult);

        // set the exception expected
        $this->expectException(OAuthException::class);

        // create the OAUTH class
        $adapter = new OAuth();

        // do the request
        $adapter->doRequest('get', 'api.com/users', []);
    }

}
