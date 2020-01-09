<?php

/** @var \Phalcon\Mvc\Router $router */
$router = $di->getRouter();

// Define your routes here


$router->add("/rest/phonebook-item/{id}",
    "Rest::index",
    ["GET"]);

$router->add("/rest/phonebook-item",
    "Rest::create",
    ["POST"]);

$router->add("/rest/phonebook-item/{id}",
    "Rest::update",
    ["PUT", "PATCH"]);

$router->add("/rest/phonebook-item",
    "Rest::delete",
    ["DELETE"]);

$router->handle($_SERVER['REQUEST_URI']);
