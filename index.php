<?php

/*
ANDY:
Some headers that allow cross domain requests, credentials, and a good set of API verbs.
*/
header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
header("Access-Control-Allow-Headers: X-Requested-With, X-Authorization, Content-Type, X-HTTP-Method-Override");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');

//ANDY: I have my own exception for deep in the API
require_once(dirname(__FILE__) . '/../classes/ControllerException.php');

/**
 * API framework front controller.
 * 
 * @package api-framework
 * @author  Martin Bean <martin@martinbean.co.uk>
 */

/**
 * Generic class autoloader.
 * 
 * @param string $class_name
 */
function autoload_class($class_name) {
    $directories = array(
        'classes/',
        'classes/controllers/',
        'classes/models/'
    );
    foreach ($directories as $directory) {
        $filename = $directory . $class_name . '.php';
        if (is_file($filename)) {
            require($filename);
            break;
        }
    }
}

/**
 * Register autoloader functions.
 */
spl_autoload_register('autoload_class');

/**
 * Parse the incoming request.
 */
$request = new Request();
if (isset($_SERVER['PATH_INFO'])) {
    $request->url_elements = explode('/', trim($_SERVER['PATH_INFO'], '/'));
}

//ANDY:  When doing credentials you get an OPTIONS request first
$request->method = isset($_SERVER['REQUEST_METHOD'])? strtoupper($_SERVER['REQUEST_METHOD']) : 'OPTIONS';
switch ($request->method) {
    case 'GET':
        $request->parameters = $_GET;
    break;
    case 'POST': //ANDY: More elaborate POST so I can handle JSON inputs, form data, and standard POSTs
        $body = file_get_contents("php://input");
        $content_type = false;
        if(isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];
        }
        switch($content_type){
            case "application/json":
                $body_params = json_decode($body);
                if($body_params) {
                    foreach($body_params as $param_name => $param_value) {
                        $request->parameters[$param_name] = $param_value;
                    }
                }
                break;
            case "application/x-www-form-urlencoded":
                parse_str($body, $postvars);
                foreach($postvars as $field => $value) {
                    $request->parameters[$field] = $value;
                }
                break;
            default:
                $request->parameters = $_POST;
                break;
        }
    case 'PUT':
        parse_str(file_get_contents('php://input'), $request->parameters);
    break;
    case 'OPTIONS':
        //no-op
        return;
    default:
        $request->parameters = json_decode(file_get_contents('php://input'));
}

/**
 * Route the request.
 */
if (!empty($request->url_elements)) {
    $controller_name = ucfirst($request->url_elements[0]) . 'Controller';
    if (class_exists($controller_name)) {
        $controller = new $controller_name;
        $action_name = strtolower($request->method);
        $response_str = call_user_func_array(array($controller, $action_name), array($request));
    }
    else {
        header('HTTP/1.1 404 Not Found');
        $response_str = 'Unknown request: ' . $request->url_elements[0];
    }
}
else {
    $response_str = 'Unknown request';
}

/**
 * Send the response to the client.
 */
$response_obj = Response::create($response_str, $_SERVER['HTTP_ACCEPT']);
echo $response_obj->render();
