<?php

require 'server/pocket-knife/start.php';
require 'server/classes/Magazine.php';
require 'server/classes/Library.php';
require 'server/classes/Link.php';
require 'server/classes/Page.php';
require 'server/classes/Social.php';

// allow higher time limit; if not, under load, image processing may fail
set_time_limit(60);
 
// create configuration
$configuration = new Settings();
$configuration->representations = array('text/html', 'application/json', 'application/x-www-form-urlencoded', 'multipart/form-data');
$configuration->set('authentication.enforce_https', false);
$configuration->set('authentication.authentication_type', 'digest');
$configuration->set('authentication.password_security', 'plaintext');
$user = array('username' => 'admin', 'roles' => 'administrator', 'password' => 'admin');
$configuration->set('authentication.users', array($user));
$configuration->acl = array(
    'admin can * */*',
    '* can GET magazine/*',
    '* can GET page/*',
    '* can OPTIONS page/*',
    '* can GET social/*'
);

// create service
$service = new Service($configuration);

// execute
pr($service->getAcl());
$service->resource = 'magazine';
$service->id = 'crossfit';
$service->action = 'GET';
$service->executeSecurity();