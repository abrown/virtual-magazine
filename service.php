<?php

require 'server/pocket-knife/start.php';
add_include_path('server/classes');

// disable time limit; if not, under load, image processing may fail
set_time_limit(0);
 
// create configuration
$configuration = new Settings(get_base_dir().'/../configuration.json');

// create service
$service = new Service($configuration);

// convert application/xml to text/html; some browsers--especially mobile browsers--default to XML
if (WebHttp::getAccept() == 'application/xml') {
    $service->accept = 'text/html';
}

// execute
$service->execute();