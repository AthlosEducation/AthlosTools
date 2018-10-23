<?php


//-- Including Controller Base File --//
require "../app/controllers/ControllerBase.php";

try {

	/* NOTE: to use debug, you must first comment out the try: catch brackets.
	$debug = new \Phalcon\Debug();
	$debug->listen();*/

    //Register an autoloader
    $loader = new \Phalcon\Loader();
    $loader->registerDirs(array(
        '../app/controllers/',
        '../app/models/'
    ))->register();

    //Create a DI
    $di = new Phalcon\DI\FactoryDefault();
	
	//Setup the database service
	$di->set('db', function(){
        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => "192.168.128.94",
            "username" => "root",
            "password" => "D1zzkn33l@nd",
            "dbname" => "athlos_tools"
        ));
    });
	
	//Setup the security / password hashing
	$di->set('security', function(){
	    $security = new Phalcon\Security();
	    //Set the password hashing factor to 12 rounds
	    $security->setWorkFactor(12);
	    return $security;
	}, true);
	
	//Start the session the first time when some component requests the session service
	$di->setShared('session', function() {
	    $session = new Phalcon\Session\Adapter\Files();
	    $session->start();
	    return $session;
	});
	
	//Register the flash service with custom CSS classes
	$di->set('flashSession', function(){
	    $flash = new \Phalcon\Flash\Session(array(
	        'error' => 'alert alert-danger',
	        'success' => 'alert alert-success',
	        'notice' => 'alert alert-info',
			'warning' => 'alert alert-warning'
	    ));
	    return $flash;
	});
	
    //Setup the view component
    $di->set('view', function(){
        $view = new \Phalcon\Mvc\View();
        $view->setViewsDir('../app/views/');
        return $view;
    });

    //Setup a base URI so that all generated URIs start with the same base
    $di->set('url', function(){
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri('/');
        return $url;
    });
	
	//-- Handle 404 & 500 Errors --//
	$di->set('dispatcher', function(){
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
	});
	
	//-- Assign HTTP Protocol --//
	$di->set('htprotocol', function(){
		$val = 'https://';
		//$val = 'http://';
		return $val;
	});
	
    //Handle the request
    $application = new \Phalcon\Mvc\Application($di);

    echo $application->handle()->getContent();

} catch(\Phalcon\Exception $e) {
	//echo "PhalconException: ", $e->getMessage();
	header('Location: /session');
	exit;
}
