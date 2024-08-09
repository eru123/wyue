# wyue

PHP Framework for developing Vite.js application with built-in REST API

# Features

## Routing

To start with routing you must first use the Route namespace

```php
use Wyue\Route;
```

To understand the route handlers and middlewares, here is an example of how to define a handler function first

```php
$handler = function (Route $context, $result = null) {
    // process handler
}
```

Think of handlers as data pipelines, where the result of the previous one is passed to the next one

```php
$analyticsHandler = function (Route $context, $result = null) {
    // does not return anything, just processing some background tasks
}

$authHandler = function (Route $context, $result = null) {
    // for the example, we assume that the request failed to send valid credentials so we want to invalidate it
    // if you return false in a handler it will stop processing the next handlers and proceed to process the next routes
    return false;

    // Alternatively, you can just throw an exception with http error codes
    throw new Exception("Forbidden", 403);

    // You could also return a json response by returning an array
    $context->code(403);
    return [
        "code" => 403,
        "error" => "Forbidden",
        "message" => "Please login to access!",
    ];
}

$apiHandler = function (Route $context, $result = null) {
    // since you return false from previous handler, you will not reach this code
}

$route = new Route();
$route->Route('/api/users', $analyticsHandler, $authHandler, $apiHandler);
$route->Route('/api/user/2', $analyticsHandler, $authHandler, $apiHandler);
$route->Route('/api/user/3', $analyticsHandler, $authHandler, $apiHandler);
$route->Route('/api/user/4', $analyticsHandler, $authHandler, $apiHandler);
```

Using `Route` method allows you to process routes without restiction on request method, to add a restriction on request method you can use following functions: `Get`, `Post`, `Put`, `Patch`, `Delete`

```php
$route = new Route();
$route->Get('/api/user', $analyticsHandler, $apiHandler);
$route->Post('/api/user', $analyticsHandler, $authHandler, $apiHandler);
```