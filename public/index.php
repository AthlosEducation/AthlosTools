<?php

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Mvc\View;
use Phalcon\Exception;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

//-- Including Controller Base File --//
require "../app/controllers/ControllerBase.php";

// Register an autoloader
$loader = new Loader();
$loader->registerDirs(
    [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
    ]
)->register();

// Create a DI
$di = new FactoryDefault();

// Setting up the view component
$di['view'] = function () {
    $view = new View();
    $view->setViewsDir(APP_PATH . '/views/');
    return $view;
};

// Setup a base URI so that all generated URIs include the "tutorial" folder
$di['url'] = function () {
    $url = new UrlProvider();
    $url->setBaseUri('/');
    return $url;
};

// Set the database service
$di['db'] = function () {
    return new DbAdapter([
        "host" => "127.0.0.1:6033",
        "username" => "athlosUser",
        "password" => "HGiT@Fz27Wb!@ahLxTAcg2gHhktp",
        "dbname" => "athlos_tools_2",
    ]);
};

//Setup the security / password hashing
$di['security'] = function(){
    $security = new Phalcon\Security();
    //Set the password hashing factor to 12 rounds
    $security->setWorkFactor(12);
    return $security;
};

//Start the session the first time when some component requests the session service
$di->setShared('session', function() {
    $session = new Phalcon\Session\Adapter\Files();
    $session->start();
    return $session;
});

//-- Register the flash service with custom CSS classes --//
$di['flashSession'] = function(){
    $flash = new \Phalcon\Flash\Session([
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);
    $flash->setAutoescape(false); //-- Allows HTML in the passed message --//
    return $flash;
};

//-- Handle 404 & 500 Errors --//
$di['dispatcher'] = function(){
    $evManager = new Phalcon\Events\Manager();
    $evManager->attach("dispatch:beforeException", function($event, $dispatcher, $exception) {
        switch($exception->getCode()){
            case Phalcon\Mvc\Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
            case Phalcon\Mvc\Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                $dispatcher->forward(array(
                    'controller' => 'error',
                    'action'     => 'show404'
                ));
                return false;
            default:
                $dispatcher->forward(array(
                    'controller' => 'error',
                    'action'     => 'show500'
                ));
                return false;
        }
    });

    $dispatcher = new Phalcon\Mvc\Dispatcher();
    $dispatcher->setEventsManager($evManager);
    return $dispatcher;
};

//-- Assign HTTP Protocol --//
$di['htprotocol'] = function(){
    $val = 'https://';
    //$val = 'http://';
    return $val;
};

// Handle the request
try {
    $application = new Application($di);
    echo $application->handle()->getContent();
} catch (Exception $e) {
    echo "Exception: ", $e->getMessage();
}
