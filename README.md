# Wyue

Framework for developing Vite.js application on top of PHP

On my recent projects, I've developed different applications using PHP as API and Vue 3 with Vite on the frontend, I successfully combined these technologies into a single project, even worked with Hot Module Replacement (HMR) for development, and added meta data to my Vue application for better Search Engine Optimization (SEO). When I'm starting new projects, I copied the codes from the previous project and use it as my boiler plate, then added new features and functionalities, then I also update the other old project for bug fixes and add the new features from other projects, that's kind of a mess on my part and exhausting, so I decided to create this framework to make my life easier and more organized. As this project helps me build my projects, I hope someone can use it too and give it a try, if you like it you can also leave a star or fund this project, I would also appreciate any feedback.

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

For the response, you can set the [http response status code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status) using `code` method

```php
$handler = function (Route $context, $result = null) {
    $context->code(202);
    return 'OK'; // response with html/text
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

If you are used with Laravel or Codeigniter, they have some magic to call route handler functions, I somehow implemented their way of calling its route handler functions.

```php
class AuthController {
    public function login(Route $context, $result = null) {
        // process login here
    }

    // ...
}

function loginFunction(Route $context, $result = null) {
    // process login here
}

// Different ways to call route handler functions
$auth = new AuthController();
$route->Post('/login', [$auth, 'login']); // I believe this is the most recommended way for performance reasons
$route->Post('/login', [AuthController::class, 'login']);
$route->Post('/login', 'AuthController@login');
$route->Post('/login', 'AuthController::login');
$route->Post('/login', 'loginFunction');
```

With Wyue, you can also define a url parameters in different ways and get it's value from the context with `Params` method

```php
// actual url path: /api/user/1

class UserController {
    public function user(Route $context, $result = null) {
        // process user here
        $id = $context->Params('id', 'default value if id not exist in url');
        // or 
        $id = $context->Params()['id'];
    }
}

$route->Get('/api/user/{id}', 'UserController::user');
$route->Get('/api/user/$id', 'UserController::user');
$route->Get('/api/user/:id', 'UserController::user');
```

If you want something more complex ways of defining your Routes, try nested routes by creating a new Route inside the handler function
```php

$route->Route('/api', function (Route $route) {
    $route->Route('/user/$id', function (Route $route) {
        $route->Delete('/delete', function (Route $route) {
            // delete user
        })

        $route->Put('/update', function (Route $route) {
            // update user
        })

        return [
            // ... user details
        ];
    });

    $route->Get('/users', function (Route $route) {
        // list users
    });

    return [
        // ... fallback
    ];
})

```