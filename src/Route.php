<?php

namespace Wyue;

use Throwable;
use Wyue\Exceptions\ApiException;

/**
 * Wyue Route class
 */
class Route
{
    private array $middlewares = [];
    private array $urlParameters = [];
    private string $parentRoute = "/";
    private $errorHandler = null;
    private $responseHandler = null;
    private int $httpCode = 200;
    private bool $debug = false;

    /**
     * Create a new route context
     * @param Route|null $route A parent route context
     */
    public function __construct(?Route $route = null)
    {
        if ($route) {
            $this->Middlewares(...$route->getMiddlewares());
            $this->setParams($route->Params());
            $this->setParentRoute($route->ParentRoute());
            $this->setErrorHandler($route->ErrorHandler());
            $this->setResponseHandler($route->ResponseHandler());
            $this->code($route->getHttpCode());
            $this->debug($route->getDebug());
        }
    }

    /**
     * Get the middlewares for the current route context
     * @return array Array of middlewares
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Sets the middlewares for the current route context
     * @param mixed ...$cb
     * @return Route
     */
    public function Middlewares(...$cb)
    {
        $this->middlewares = $cb;
        return $this;
    }

    /**
     * Get the request parameters
     * @param array|null $key Magic array key
     * @return array|mixed returns an array if $key is null, else the value of the key
     */
    public function Params(?string $key = null)
    {
        return $this->urlParameters;
    }

    /**
     * Get the request payload
     * @param array|null $key Magic array key
     * @return array|mixed returns an array if $key is null, else the value of the key
     */
    public function Payload(?array $key = null)
    {
        $json = json_decode(file_get_contents('php://input'), true) ?? [];
        $payload = $json + $_REQUEST;
        return $payload;
    }

    public function setParams(array $params)
    {
        $this->urlParameters = $params;
        return $this;
    }

    public function ErrorHandler()
    {
        return $this->errorHandler;
    }

    public function setErrorHandler($errorHandler): Route
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    public function setParentRoute(string $parentRoute)
    {
        $this->parentRoute = $parentRoute;
        return $this;
    }

    public function ParentRoute()
    {
        return $this->parentRoute;
    }

    public function ResponseHandler()
    {
        return $this->responseHandler;
    }

    public function setResponseHandler($responseHandler): Route
    {
        $this->responseHandler = $responseHandler;
        return $this;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Get the debug mode
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Set the debug mode
     * @param bool $debug
     * @return Route
     */
    public function Debug(bool $debug): Route
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Get the current request URI
     * @return string
     */
    public function uri(): string
    {
        $uri = preg_replace('/\?.*$/', '', (string) $_SERVER['REQUEST_URI']);
        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    /**
     * Creates a new route handler
     * @param string $path
     */
    public function Route(string $path, ...$cb): Route
    {
        $rgxSuffix = "\/?(.*)?$/";
        $cpath = Helper::CombineUrlPaths($this->parentRoute, $path);
        if (substr($cpath, -1) == "$") {
            $cpath = substr($cpath, 0, -1);
            $rgxSuffix = "\/?$/";
        }

        $uri = preg_replace('/\//', '\\\/', $cpath);
        $rgx = '/\{([a-zA-Z_]([a-zA-Z0-9_]+)?)\}|\$([a-zA-Z_]([a-zA-Z0-9_]+)?)|\:([a-zA-Z_]([a-zA-Z0-9_]+)?)/';
        $rgx = preg_replace_callback($rgx, fn($m) => "(?P<" . ($m[1] ?: $m[3] ?: $m[5]) . ">[^\/\?]+)", $uri);
        $rgx = '/^' . $rgx . $rgxSuffix;

        $match = !!preg_match($rgx, $this->uri(), $params);

        if ($match) {
            $params = array_filter($params, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

            $routeContext = new Route($this);
            $routeContext->setParams($params + $this->Params());
            $routeContext->setParentRoute($cpath);

            $res = null;
            $cbs = array_merge($this->middlewares, $cb);

            try {
                foreach ($cbs as $cba) {
                    $res = Helper::Callback($cba, [$routeContext, $res]);
                    if ($res === false) {
                        break;
                    }
                }
            } catch (ApiException $e) {
                $this->code($e->getCode());
                if ($routeContext->errorHandler) {
                    $res = $routeContext->ErrorHandler()($routeContext, $e);
                } else {
                    $res = $routeContext->defaultErrorHandler($routeContext, $e);
                }
            } catch (Throwable $e) {
                if ($routeContext->errorHandler) {
                    $res = $routeContext->ErrorHandler()($routeContext, $e);
                } else {
                    $res = $routeContext->defaultErrorHandler($routeContext, $e);
                }
            }

            if ($res) {
                if ($routeContext->responseHandler) {
                    $res = $routeContext->ResponseHandler()($routeContext, $res);
                }

                return $routeContext->defaultResponseHandler($routeContext, $res);
            }
        }

        return $this;
    }

    /**
     * Set the http response code
     * @param int $httpCode HTTP Response Code
     * @return Route
     */
    public function code(int $httpCode = 200)
    {
        $http_code = preg_match('/^[1-5][0-9][0-9]$/', (string) $httpCode) ? (int) $httpCode : 500;
        if (intval($http_code) <= 0) {
            $http_code = 500;
        }
        $this->httpCode = $http_code;
        return $this;
    }

    /**
     * Default error handler
     * @param Route $routeContext Although it's not used, it's required to match the format of the other handlers
     * @param Throwable $e
     * @return array An array with the following keys: code, error, message
     */
    public function defaultErrorHandler($routeContext, $e)
    {
        $this->httpCode = 500;
        return [
            "code" => $this->httpCode,
            "error" => "Internal Server Error",
            "message" => $e->getMessage(),
            "trace" => $e->getTrace()
        ];
    }

    /**
     * Default response handler
     * @param Route $routeContext Although it's not used, it's required to match the format of the other handlers
     * @param array $res
     * @return array 
     */
    public function defaultResponseHandler($routeContext, $res)
    {
        if (empty($res)) {
            return;
        }

        $hs = headers_sent();
        $hs || http_response_code($this->httpCode);
        if (is_array($res) xor is_object($res)) {
            $hs || header('Content-Type: application/json');

            if ($this->debug) {
                $debug['payload'] = $this->Payload();
                $debug['params'] = $this->Params();

                if (isset($_FILES) && !empty($_FILES)) {
                    $debug['files'] = @$_FILES;
                }

                if (is_array($res)) {
                    $res['debug'] = $debug;
                } else if (is_object($res)) {
                    $res->debug = $debug;
                }
            }

            echo json_encode($res);
        } else if (is_string($res)) {
            echo $res;
        }

        exit;
    }

    /**
     * Create a GET Request Route
     * @param string $path
     * @param mixed ...$cb
     * @return Route
     */
    public function Get(string $path, ...$cb)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
            return $this;
        }
        return $this->route($path, $cb, 'GET');
    }

    /**
     * Create a POST Request Route
     * @param string $path
     * @param mixed ...$cb
     * @return Route
     */
    public function Post(string $path, ...$cb)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            return $this;
        }
        return $this->route($path, $cb, 'POST');
    }

    /**
     * Create a PUT Request Route
     * @param string $path
     * @param mixed ...$cb
     * @return Route
     */
    public function Put(string $path, ...$cb)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'PUT') {
            return $this;
        }
        return $this->route($path, $cb, 'PUT');
    }

    /**
     * Create a PATCH Request Route
     * @param string $path
     * @param mixed ...$cb
     * @return Route
     */
    public function Patch(string $path, ...$cb)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'PATCH') {
            return $this;
        }
        return $this->route($path, $cb, 'PATCH');
    }

    /**
     * Create a DELETE Request Route
     * @param string $path
     * @param mixed ...$cb
     * @return Route
     */
    public function Delete(string $path, ...$cb)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'DELETE') {
            return $this;
        }
        return $this->route($path, $cb, 'DELETE');
    }
}
