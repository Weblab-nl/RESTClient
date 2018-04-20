<?php

namespace Weblab\RESTClient;

use Weblab\CURL\Result;
use Weblab\RESTClient\Adapters\AdapterInterface;
use Weblab\RESTClient\Exceptions\NoAdapterException;
use Weblab\RESTClient\Exceptions\ResponseHandlerNotFoundException;

/**
 * Class RESTClient - Class to wrap basic REST client functionality
 * @author Weblab.nl - Eelco Verbeek
 */
class RESTClient {

    /**
     * @var string              The base URL where the API calls are made to
     */
    protected $baseURL;

    /**
     * @var AdapterInterface    The adapter that will actually make the requests
     */
    protected $adapter;

    /**
     * @var array               You can assign handlers for specific http status codes
     */
    protected $responseHandlers = [];

    /**
     * RESTClient constructor.
     */
    public function __construct() {
        // Register the default response handler
        $this->registerResponseHandler('default', 'defaultResponseHandler');
    }

    /**
     * Set the adapter class must use to do the API requests
     *
     * @param   AdapterInterface    $adapter
     * @return  $this
     */
    public function setAdapter(AdapterInterface $adapter) {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Returns the baseURL
     *
     * @return  string
     */
    public function getBaseURL() {
        return $this->baseURL;
    }

    /**
     * Set the baseURL
     *
     * @param   string  $url
     * @return  $this
     */
    public function setBaseURL($url) {
        // Make sure last character is a slash
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        $this->baseURL = $url;

        return $this;
    }

    /**
     * Do a GET request
     *
     * @param   string      $url
     * @param   array       $params
     * @return  mixed
     * @throws  \Exception
     */
    public function get($url, $params = [], $options = [], $headers = []) {
        return $this->makeCall('get', $url, $params, $options, $headers);
    }

    /**
     * Do a POST request
     *
     * @param   string      $url
     * @param   mixed       $params
     * @return  mixed
     * @throws  \Exception
     */
    public function post($url, $params, $options = [], $headers = []) {
        return $this->makeCall('post', $url, $params, $options, $headers);
    }

    /**
     * Do a PUT request
     *
     * @param   string      $url
     * @param   mixed       $params
     * @return  mixed
     * @throws  \Exception
     */
    public function put($url, $params, $options = [], $headers = []) {
        return $this->update('put', $url, $params, $options, $headers);
    }

    /**
     * Do a PATCH request
     *
     * @param   string      $url
     * @param   mixed       $params
     * @return  mixed
     * @throws  \Exception
     */
    public function patch($url, $params, $options = [], $headers = []) {
        return $this->update('patch', $url, $params, $options, $headers);
    }

    /**
     * Proxy function for both edit requests types
     *
     * @param   string      $type
     * @param   string      $url
     * @param   mixed       $params
     * @return  mixed
     * @throws  \Exception
     */
    protected function update($type, $url, $params, $options = [], $headers = []) {
        return $this->makeCall($type, $url, $params, $options, $headers);
    }

    /**
     * Do a DELETE request
     *
     * @param   string      $url
     * @param   mixed       $params
     * @return  mixed
     * @throws  \Exception
     */
    public function delete($url, $params = [], $options = [], $headers = []) {
        return $this->makeCall('delete', $url, $params, $options, $headers);
    }

    /**
     * Calls the adapter which will perform the request
     *
     * @param   string      $type
     * @param   string      $url
     * @param   array       $params
     * @return  mixed
     * @throws  \Exception
     */
    protected function makeCall($type, $url, $params, $options, $headers) {
        // Throw exception when adapter is not set
        if (!isset($this->adapter)) {
            throw new NoAdapterException('No adapter set');
        }

        // Remove potential leading slashes
        if (substr($url, 0, 1) === '/') {
            $url = substr($url, 1);
        }

        // Call adapter to make request
        $result = $this->adapter->doRequest($type, $this->baseURL . $url, $params, $options, $headers);

        // Run the the response handler
        return $this->runResponseHandler($result, $type, $url, $params);
    }

    /**
     * Register a response handler. You can add a key "default" or else send a HTTP status code for status specific handlers
     *
     * @param   string              The http code to register the handler for
     * @param   string|callable     The handler
     * @return  $this
     */
    public function registerResponseHandler($key, $value) {
        $this->responseHandlers[$key] = $value;

        return $this;
    }

    /**
     * Run the correct response handler function
     *
     * @param   Result      $result
     * @param   string      $type
     * @param   string      $url
     * @param   array       $params
     * @return  Result
     * @throws  \Exception
     */
    protected function runResponseHandler(Result $result, $type, $url, $params) {
        // Check if a status specific handler isset or else fallback to the default
        if (isset($this->responseHandlers[$result->getStatus()])) {
            $handler = $this->responseHandlers[$result->getStatus()];
        } else {
            $handler = $this->responseHandlers['default'];
        }

        // If the handler is a callable run the handler and return the result
        if (is_callable($handler)) {
            return $handler($result, $type, $url, $params);
        }
        // If the handler does not exists, throw exception
        else if (!method_exists($this, $handler)) {
            throw new ResponseHandlerNotFoundException('Response handler not found: ' . $handler);
        }

        // Run the handler and return the result
        return $this->$handler($result, $type, $url, $params);
    }

    /**
     * The default response handler
     *
     * @param   Result  $result
     * @param   string  $type
     * @param   string  $url
     * @param   array   $params
     * @return  Result
     */
    protected function defaultResponseHandler(Result $result, $type, $url, $params) {
        return $result;
    }

}
