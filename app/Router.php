<?php

declare(strict_types=1);

require 'vendor/autoload.php';

include 'UserApi.php';
include 'PostApi.php';

class Router
{
    public function run() {

        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            $r->addGroup('/api', function(\FastRoute\RouteCollector $r) {
                $r->post('/login', ['UserApi','login']);

                $r->addGroup('/user', function ($group) {
                    $group->post('/register', ['UserApi', 'register']);
                    $group->get('/get/{id:\d+}',  ['UserApi', 'getUser']);
                    $group->get('/search', ['UserApi', 'searchUser']);
                });

                $r->addGroup('/friend', function ($group) {
                    $group->post('/set/{id:\d+}', ['UserApi', 'addFriend']);
                    $group->post('/delete/{id:\d+}', ['UserApi', 'removeFriend']);
                });

                $r->addGroup('/post', function ($group) {
                    $group->post('/create', ['PostApi','create']);
                });
            });

        });

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                return json_encode('API Not Found');
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return json_encode('Method Not Allowed',);
                break;
            case \FastRoute\Dispatcher::FOUND:
                $controllerName = $routeInfo[1][0];
                $action = $routeInfo[1][1];
                $vars = $routeInfo[2];
                $class = new $controllerName;

                return $class->{$action}($vars);
        }
    }
}