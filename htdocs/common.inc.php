<?php
/*
 * This file is meant to be included.
 *  - ensure Apache doesn’t display any errors/warnings unless server is 
 *    localhost
 *  - define the URL constant (to get the URL of the containing directory)
 *  - start session
 *  - define a regexp matching function for registration with sqlite (not 
 *    compulsory)
 *  - define a readSmallFile ($file,$max) function to return the contents of a 
 *    file
 *
 *  The function handle_requests () should be called by the main php file.
 */

mb_http_output('UTF-8');
$serverList = array('localhost', '127.0.0.1');

if (in_array($_SERVER['HTTP_HOST'], $serverList)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('error_log', '/tmp/php-error.log');
    define('IS_LOCALHOST', true);
}
else {
    error_reporting(0);
    define('IS_LOCALHOST', false);
}
define ('URL', 
    (
        (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') .
        $_SERVER['SERVER_NAME'] .
        dirname($_SERVER['REQUEST_URI'])
    )
);

define('KB', 1024);
define('MB', 1024*KB);
define('GB', 1024*MB);
define('TB', 1024*GB);


function postVal($name, $default='') {
    if (isset ($_POST[$name])) return $_POST[$name];
    else return $default;
}

function getVal($name, $default='') {
    if (isset ($_GET[$name])) return $_GET[$name];
    else return $default;
}

function sessionVal($name, $default) {
    if (isset ($_SESSION[$name])) return $_SESSION[$name];
    else return $default;
}

function _sqliteRE ($re, $txt) {
    return !!(preg_match($re, $txt));
}

function handle_requests () {
    $http_method = $_SERVER['REQUEST_METHOD'];
    // $http_method is one of:
    //    - 'GET'
    //    - 'POST'
    //    - 'PUT'
    //    - 'DELETE'
    //    - 'HEAD'
    //
    // handle_GET and handle_HEAD are ONLY for information retrieval (according 
    // to rfc2616, they should not be used to take any other action).

    $action = postVal('action', null) ?? getVal('action', null);
    $callbackName = 'action_' . $action;
    if ($action && is_string($action) && function_exists($callbackName)) {
        exit(call_user_func($callbackName));
    }

    elseif (function_exists ('handle_' . $http_method)) {
        exit(call_user_func ('handle_' . $http_method));
    }
}
?>