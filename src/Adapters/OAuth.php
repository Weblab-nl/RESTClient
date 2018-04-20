<?php

namespace Weblab\RESTClient\Adapters;

use Weblab\CURL\CURL;
use Weblab\CURL\Result;
use Weblab\RESTClient\Exceptions\OAuthException;

/**
 * Class OAuth - Adapter for the RESTClient. Does cURL requests with OAuth accesstokens
 * @author Weblab.nl - Eelco Verbeek
 */
class OAuth implements AdapterInterface {

    /**
     * @var string  The url to where the auth requests are done
     */
    private $url;

    /**
     * @var string  The client ID
     */
    private $clientID;

    /**
     * @var string  The client secret
     */
    private $secret;

    /**
     * @var string  The request token. This is the token you get when the user gives the permissions for the app to access the requested scopes
     */
    private $requestToken;

    /**
     * @var string  The access token
     */
    private $accessToken;

    /**
     * @var string  The refresh token
     */
    private $refreshToken;

    /**
     * @var int     The timestamp of the access token expires
     */
    private $expireTime;

    /**
     * @var string  The uri to which the fetching of the access token request is redirected
     */
    private $redirectURI;

    /**
     * OAuth constructor.
     */
    public function __construct() {}

    /**
     * Set the access token
     *
     * @param   string  $token
     * @return  $this
     */
    public function setAccessToken($token) {
        $this->accessToken = $token;

        return $this;
    }

    /**
     * Set the refresh token
     *
     * @param   string    $token
     * @return  $this
     */
    public function setRefreshToken($token) {
        $this->refreshToken = $token;

        return $this;
    }

    /**
     * Set the request token
     *
     * @param   string  $token
     * @return  $this
     */
    public function setRequestToken($token) {
        $this->requestToken = $token;

        return $this;
    }

    /**
     * Set the url
     *
     * @param   string  $url
     * @return  $this
     */
    public function setURL($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the clientID
     *
     * @param   string  $id
     * @return  $this
     */
    public function setClientID($id) {
        $this->clientID = $id;

        return $this;
    }

    /**
     * Set the client secret
     *
     * @param   string   $secret
     * @return  $this
     */
    public function setSecret($secret) {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Set the redirect uri
     *
     * @param   string  $uri
     * @return  $this
     */
    public function setRedirectURI($uri) {
        $this->redirectURI = $uri;

        return $this;
    }

    /**
     * Set the expire time
     *
     * @param   int     $time
     * @return  $this
     */
    protected function setExpireTime($time) {
        $this->expireTime = $time;

        return $this;
    }

    /**
     * Make a request to the API
     *
     * @param   string      $type
     * @param   string      $url
     * @param   mixed       $params
     * @param   array       $options
     * @param   array       $headers
     * @return  mixed
     * @throws  \Exception
     */
    public function doRequest($type, $url, $params, $options = [], $headers = []) {
        // get access to the curl wrapper and set the bearer
        $curl = CURL::setBearer($this->getAccessToken());

        // set the request options
        foreach ($options as $var => $value) {
            $curl->setOption($var, $value);
        }

        // set the header values
        foreach ($headers as $var => $value) {
            $curl->setHeader($var, $value);
        }

        // execute the request
        $result = $curl->$type($url, $params);

        // done, return the result
        return $result;
    }

    /**
     * Get the access token
     *
     * @param   bool    $expired    Pass true if you want to force refresh the access token
     * @return  string
     * @throws  \Exception
     */
    private function getAccessToken($expired = false) {
        // Refresh the access token if force refresh or the expiretime has passed
        if ($expired || isset($this->expireTime) && $this->expireTime <= time()) {
            // If there is no refresh token throw exception
            if (!isset($this->refreshToken)) {
                throw new OAuthException('Token expired but no refresh token found');
            }

            $this->refreshAccessToken();
        }
        // If there no access token fetch one
        else if (!$expired && !isset($this->accessToken)) {
            $this->fetchAccessToken();
        }

        // If there still is no access token at this point. Throw exception
        if (!isset($this->accessToken)) {
            throw new OAuthException('No access token found');
        }

        return $this->accessToken;
    }

    /**
     * Does a cURL to request a new access token
     *
     * @throws  \Exception
     */
    private function fetchAccessToken() {
        // Setup the params
        $params = [
            'client_id'     => $this->clientID,
            'client_secret' => $this->secret,
            'grant_type'    => 'authorization_code',
            'code'          => $this->requestToken
        ];

        if (isset($this->redirectURI)) {
            $params['redirect_uri'] = $this->redirectURI;
        }

        // Parse the cURL result
        $this->parseAccessToken(CURL::post($this->url, $params));
    }

    /**
     * Refreshes the access token
     *
     * @throws  \Exception
     */
    private function refreshAccessToken() {
        // Setup the params
        $params = [
            'client_id'     => $this->clientID,
            'client_secret' => $this->secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refreshToken
        ];

        // Parse the cURL result
        $this->parseAccessToken(CURL::post($this->url, $params));
    }

    /**
     * Parse the cURL result of the fetching / refreshing access token requests
     *
     * @param   Result      $result
     * @throws  \Exception
     */
    private function parseAccessToken(Result $result) {
        // If unexpected status code throw exception
        if ($result->getStatus() !== 200) {
            throw new OAuthException('Something went wrong requesting access token');
        }

        // Get the data form the result object
        $data = $result->getResult();

        // Store the data if available
        if (isset($data['access_token'])) {
            $this->setAccessToken($data['access_token']);
        }
        if (isset($data['refresh_token'])) {
            $this->setRefreshToken($data['refresh_token']);
        }
        if (isset($data['expire_time'])) {
            $this->setExpireTime(time() + $data['expire_time']);
        }
    }

    /**
     * Convert a request token to an access token
     *
     * @param   string  $token
     * @return  mixed
     */
    public function processRequestToken($token) {
        // Setup the params
        $params = [
            'client_id'     => $this->clientID,
            'client_secret' => $this->secret,
            'code'          => $token,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectURI
        ];

        // Parse the cURL result
        return CURL::post($this->url, $params);
    }

}
