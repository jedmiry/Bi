<?php

/**
 * Bi
 *
 * @author Dmitry Fomin
 */
class Bi
{
    /**
     * Routes
     *
     * @var array
     */
    private static $routes =
    [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * Named routes
     *
     * @var array
     */
    private static $namedRoutes = [];

    /**
     * Current prefix
     *
     * @var string
     */
    private static $prefix;

    /**
     * Error handler
     *
     * @var Closure
     */
    private static $error;

    /**
     * Add a route
     *
     * @param string $method
     * @param string $pattern
     * @param mixed  $callable
     * @param string $name
     */
    public static function bind($method, $pattern, $callable, $name = null)
    {
        self::$routes[$method][self::$prefix . $pattern] = $callable;

        if ($name)
        {
            self::$namedRoutes[$name] = $pattern;
        }
    }

    /**
     * Add a route for GET method
     *
     * @param string $pattern
     * @param mixed  $callable
     * @param string $name
     */
    public static function get($pattern, $callable, $name = null)
    {
        self::bind('GET', $pattern, $callable, $name);
    }

    /**
     * Add a route for POST method
     *
     * @param string $pattern
     * @param mixed  $callable
     * @param string $name
     */
    public static function post($pattern, $callable, $name = null)
    {
        self::bind('POST', $pattern, $callable, $name);
    }

    /**
     * Add routes with a prefix to the pattern
     *
     * @param string  $prefix
     * @param Closure $callable
     */
    public static function prefix($prefix, Closure $callable)
    {
        // Save a previous prefix
        $previous = self::$prefix;

        // Add a new prefix
        self::$prefix .= $prefix;

        // Add routes
        $callable();

        // Return to the previous prefix
        self::$prefix = $previous;
    }

    /**
     * Define error handler
     *
     * @param mixed $callable
     */
    public static function error($callable)
    {
        self::$error = $callable;
    }

    /**
     * Call error handler
     */
    public static function alert()
    {
        if (!self::$error)
        {
            self::$error = function ()
            {
                http_response_code(404);

                echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>That page doesn\'t exist!</p></body></html>';
            };
        }

        self::call(self::$error);
    }

    /**
     * Get a generated link from the route with parameters
     *
     * @param string $name
     * @param array  $params
     *
     * @return string
     */
    public static function generate($name, array $params = [])
    {
        if (!array_key_exists($name, self::$namedRoutes))
        {
            throw new RuntimeException(sprintf('Route, %s, not found.', $name));
        }

        $search = [];

        // Prepare regexp
        foreach ($params as $key => $value)
        {
            $search[] = '~@' . preg_quote($key, '~') . '(:([^/\(\)]*))\+?(?!\w)~';
        }

        // Replace parameters
        $pattern = preg_replace($search, $params, self::$namedRoutes[$name]);

        // Remove remnants of unpopulated, trailing optional pattern segments, escaped special characters
        return preg_replace('#\(/?@.+\)|\(|\)|\\\\#', '', $pattern);
    }

    /**
     * Dispatching
     *
     * @return mixed
     */
    public static function dispatch(array $routes = [])
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Search suitable routes
        $routes = array_merge(self::$routes[$method], $routes);

        // Search static route
        if (array_key_exists($uri, $routes))
        {
            return self::call($routes[$uri]);
        }

        // Search dinamic route
        foreach ($routes as $pattern => $callable)
        {
            $keys = [];

            $pattern = str_replace(array(')','*'), array(')?','.*?'), $pattern);

            // Replace parameters for regexp
            $pattern = preg_replace_callback('~@([\w]+)(:([^/\(\)]*))?~', function ($matches) use (&$keys)
            {
                $keys[] = $matches[1];

                if (isset($matches[3]))
                {
                    return "(?P<{$matches[1]}>{$matches[3]})";
                }

                return "(?P<{$matches[1]}>[^/\?]+)";
            }, $pattern);

            // Match
            if (preg_match("~^{$pattern}$~", $uri, $matches))
            {
                $params = [];

                foreach ($keys as $key)
                {
                    if (array_key_exists($key, $matches))
                    {
                        $params[$key] = urldecode($matches[$key]);
                    }
                }

                // Call action
                return self::call($callable, $params);
            }
        }

        // Error!
        self::alert();
    }

    /**
     * Call action
     *
     * @param mixed $callable
     * @param array $arguments
     */
    private static function call($callable, array $arguments = [])
    {
        call_user_func_array($callable, $arguments);
    }
}
