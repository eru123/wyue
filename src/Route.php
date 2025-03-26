<?php

namespace Wyue;

use Wyue\Controllers\FileStream;
use Wyue\Exceptions\ApiException;

/**
 * Wyue Route class.
 */
class Route
{
    use FileStream;

    private array $middlewares = [];
    private array $urlParameters = [];
    private string $parentRoute = '/';
    private $errorHandler;
    private $responseHandler;
    private int $httpCode = 200;
    private bool $debug = false;

    /**
     * Create a new route context.
     *
     * @param null|Route $route A parent route context
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
     * Get the middlewares for the current route context.
     *
     * @return array Array of middlewares
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Sets the middlewares for the current route context.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Middlewares(...$cb)
    {
        $this->middlewares = $cb;

        return $this;
    }

    /**
     * Get the request parameters.
     *
     * @param null|array $key     Magic array key
     * @param null|mixed $default
     *
     * @return array|mixed returns an array if $key is null, else the value of the key
     */
    public function Params(?string $key = null, $default = null)
    {
        return Venv::_get($this->urlParameters, $key, $default);
    }

    /**
     * Get the request payload.
     *
     * @param null|array $key Magic array key
     *
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
     * Get the debug mode.
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Set the debug mode.
     */
    public function Debug(bool $debug): Route
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get the current request URI.
     */
    public function uri(): string
    {
        $uri = preg_replace('/\?.*$/', '', (string) $_SERVER['REQUEST_URI']);
        $uri = '/'.trim($uri, '/');

        return $uri;
    }

    /**
     * Creates a new route handler.
     */
    public function Route(string $path, ...$cb): Route
    {
        $rgxSuffix = '\\/?(?P<resource>.*)?$/';
        $cpath = Helper::CombineUrlPaths($this->parentRoute, $path);
        if ('$' == substr($cpath, -1)) {
            $cpath = substr($cpath, 0, -1);
            $rgxSuffix = '\\/?$/';
        }

        $uri = preg_replace('/\//', '\\\/', $cpath);
        $rgx = '/\{([a-zA-Z_]([a-zA-Z0-9_]+)?)\}|\$([a-zA-Z_]([a-zA-Z0-9_]+)?)|\:([a-zA-Z_]([a-zA-Z0-9_]+)?)/';
        $rgx = preg_replace_callback($rgx, fn ($m) => '(?P<'.($m[1] ?: $m[3] ?: $m[5]).'>[^\\/\\?]+)', $uri);
        $rgx = '/^'.$rgx.$rgxSuffix;

        $match = (bool) preg_match($rgx, $this->uri(), $params);

        if ($match) {
            $params = array_filter($params, fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

            $routeContext = new Route($this);
            $routeContext->setParams($params + $this->Params());
            $routeContext->setParentRoute($cpath);
            $routeContext->Debug($this->debug);

            $res = null;
            $cbs = array_merge($this->middlewares, $cb);

            try {
                foreach ($cbs as $cba) {
                    $res = Helper::Callback($cba, [$routeContext, $res]);
                    if (false === $res) {
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
            } catch (\Throwable $e) {
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
     * Set the http response code.
     *
     * @param int $httpCode HTTP Response Code
     *
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
     * Default error handler.
     *
     * @param Route      $routeContext Although it's not used, it's required to match the format of the other handlers
     * @param \Throwable $e
     *
     * @return array An array with the following keys: code, error, message
     */
    public function defaultErrorHandler($routeContext, $e)
    {
        $this->code(is_string($e->getCode()) ? 500 : $e->getCode());
        $res = [
            'code' => $this->httpCode,
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
        ];

        if ($this->debug || $routeContext->getDebug()) {
            $res['trace'] = $e->getTrace();
            $res['trace_string'] = $e->getTraceAsString();
        }

        return $res;
    }

    /**
     * Default response handler.
     *
     * @param Route $routeContext Although it's not used, it's required to match the format of the other handlers
     * @param array $res
     *
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

                $debug['mysql_history'] = MySql::history();
                if (isset($_FILES) && !empty($_FILES)) {
                    $debug['files'] = @$_FILES;
                }

                if (is_array($res)) {
                    $res['debug'] = $debug;
                } elseif (is_object($res)) {
                    $res->debug = $debug;
                }
            }

            echo json_encode($res);
        } elseif (is_string($res)) {
            echo $res;
        }

        exit;
    }

    /**
     * Create a GET Request Route.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Get(string $path, ...$cb)
    {
        if ('GET' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
            return $this;
        }

        return $this->Route($path, $cb, 'GET');
    }

    /**
     * Create a POST Request Route.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Post(string $path, ...$cb)
    {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
            return $this;
        }

        return $this->Route($path, $cb, 'POST');
    }

    /**
     * Create a PUT Request Route.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Put(string $path, ...$cb)
    {
        if ('PUT' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
            return $this;
        }

        return $this->Route($path, $cb, 'PUT');
    }

    /**
     * Create a PATCH Request Route.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Patch(string $path, ...$cb)
    {
        if ('PATCH' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
            return $this;
        }

        return $this->Route($path, $cb, 'PATCH');
    }

    /**
     * Create a DELETE Request Route.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Delete(string $path, ...$cb)
    {
        if ('DELETE' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
            return $this;
        }

        return $this->Route($path, $cb, 'DELETE');
    }

    /**
     * Create a Fallback Route.
     *
     * @param mixed ...$cb
     *
     * @return Route
     */
    public function Fallback(...$cb)
    {
        return $this->Route('/', ...$cb);
    }

    /**
     * Expose Static Files.
     *
     * @return Route
     */
    public function Static(string $path, string $dir, ...$cb)
    {
        $get_resource = function (self $ctx) use ($dir) {
            $rsrc = strval($ctx->Params('resource'));
            $fpath = realpath(rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($rsrc, DIRECTORY_SEPARATOR));
            if (empty($rsrc) || !$fpath || !is_file($fpath)) {
                return false;
            }

            return $fpath;
        };

        $cb[] = function (self $ctx, $fpath) {
            if (realpath($fpath) && is_file($fpath)) {
                $ctx->streamFile($fpath);

                exit(0);
            }

            http_response_code(404);

            exit(1);
        };

        return $this->Route($path, $get_resource, ...$cb);
    }
}
