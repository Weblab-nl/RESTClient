WebLab B.V. - RESTClient
==================================

This library will help you implement REST APIs faster. There is a base class which 


Installation
------------

### Install using composer:

    composer require weblabnl/restclient


Using the Library
-----------------

#### Setup api

```php
$api = new \Weblab\RESTClient\RESTClient();

$adapter = (new \Weblab\RESTClient\Adapters\OAuth)
    ->setURL($authURL)
    ->setClientID($clientID)
    ->setSecret($clientSecret)
    ->setAccessToken($accessToken)
    ->setRefreshToken($refreshToken)
    ->setRedirectURI($redirectURI);

$api->setAdapter($adapter);
$api->setBaseURL('https://api.weblab.nl');
```


#### Make a POST request to a REST API

```php
$result = $api->post('/users', ['first_name' => 'Ankie', 'last_name' => 'Visser']);
```
