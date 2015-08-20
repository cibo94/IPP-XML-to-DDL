#!/bin/php
<?php
#XTD:xcibul10
    function __autoload($class_name) { include dirname(__FILE__). '/' . $class_name . '.php'; }
    function exceptionHandler($ex) { fprintf(STDERR, $ex->getMessage()); exit($ex->getCode()); }
    include dirname(__FILE__) . '/interfaces.php';
    set_exception_handler('exceptionHandler');
    EVENT::$DEBUG = false;
    $app = new APPLICATION($argv);
    exit($app->main());
?>
