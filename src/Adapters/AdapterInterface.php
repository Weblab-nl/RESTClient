<?php

namespace Weblab\RESTClient\Adapters;


interface AdapterInterface {

    public function doRequest($type, $url, $params, $options, $headers);

}