<?php

/**
 * Bi
 *
 * @author Dmitry Fomin
 */
class Bi
{
    /**
     * Маршруты
     *
     * @var array
     */
    private static $routes =
    [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * Текущее пространство маршрутов
     *
     * @var string
     */
    private static $namespace;

    /**
     * Фильтры
     *
     * @var array
     */
    private static $filters =
    [
        // До и после действия
        'before' => [],
        'after'  => [],

        // Ошибка
        'error'  => [],
    ];

    /**
     * Добавление маршрута
     *
     * @param string  $method
     * @param string  $pattern
     * @param Closure $callable
     */
    public static function bind($method, $pattern, Closure $callable)
    {
        static::$routes[$method][static::$namespace . $pattern] = $callable;
    }

    /**
     * Добавление GET-маршрута
     *
     * @param string  $pattern
     * @param Closure $callable
     */
    public static function get($pattern, Closure $callable)
    {
        static::bind('GET', $pattern, $callable);
    }

    /**
     * Добавление POST-маршрута
     *
     * @param string  $pattern
     * @param Closure $callable
     */
    public static function post($pattern, Closure $callable)
    {
        static::bind('POST', $pattern, $callable);
    }

    /**
     * Группирование маршрутов в пространстве имён
     *
     * @param string  $namespace
     * @param Closure $callable
     */
    public static function group($namespace, Closure $callable)
    {
        // Сохранение старого пространства маршрутов
        $previous = static::$namespace;

        // Добавление нового
        static::$namespace .= $namespace;

        // Добавление маршрутов
        $callable();

        // Возврат к предыдушему пространству
        static::$namespace = $previous;
    }

    /**
     * Добавление фильтра
     *
     * @param string  $key
     * @param Closure $callable
     */
    public static function filter($key, Closure $callable)
    {
        static::$filters[$key][] = $callable;
    }

    /**
     * Добавление фильтра "до"
     *
     * @param Closure $callable
     */
    public static function before(Closure $callable)
    {
        static::filter('before', $callable);
    }

    /**
     * Добавление фильтра "после"
     *
     * @param Closure $callable
     */
    public static function after(Closure $callable)
    {
        static::filter('after', $callable);
    }

    /**
     * Добавление фильтра "ошибки"
     *
     * @param Closure $callable
     */
    public static function error(Closure $callable)
    {
        static::filter('error', $callable);
    }

    /**
     * Диспетчеризация
     */
    public static function dispatch(array $routes = [])
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Подбор маршрутов с подходящим методом запроса и объединение маршрутов
        $routes = array_merge(static::$routes[$method], $routes);

        // Поиск статичного маршрута
        if (array_key_exists($uri, $routes))
        {
            return static::call($routes[$uri]);
        }

        // Поиск динамичного маршрута
        foreach ($routes as $pattern => $callable)
        {
            if (preg_match("~^{$pattern}$~", $uri, $matches))
            {
                return static::call($callable, array_slice($matches, 1));
            }
        }

        // 404
        static::alert();
    }

    /**
     * Вызов действия
     *
     * @param Closure $callable
     * @param array   $arguments
     */
    private static function call(Closure $callable, array $arguments = [])
    {
        foreach (static::$filters['before'] as $before)
        {
            $before();
        }

        call_user_func_array($callable, $arguments);

        foreach (static::$filters['after'] as $after)
        {
            $after();
        }
    }

    /**
     * Вызов ошибки
     */
    public static function alert()
    {
        foreach (static::$filters['error'] as $error)
        {
            $error();
        }
    }
}