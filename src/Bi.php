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
     * Названые мршруты
     *
     * @var array
     */
    private static $namedRoutes = [];

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
    public static function bind($method, $pattern, Closure $callable, $name = null)
    {
        self::$routes[$method][self::$namespace . $pattern] = $callable;

        if ($name)
        {
            self::$namedRoutes[$name] = $pattern;
        }
    }

    /**
     * Добавление GET-маршрута
     *
     * @param string  $pattern
     * @param Closure $callable
     */
    public static function get($pattern, Closure $callable, $name = null)
    {
        self::bind('GET', $pattern, $callable, $name);
    }

    /**
     * Добавление POST-маршрута
     *
     * @param string  $pattern
     * @param Closure $callable
     */
    public static function post($pattern, Closure $callable, $name = null)
    {
        self::bind('POST', $pattern, $callable, $name);
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
        $previous = self::$namespace;

        // Добавление нового
        self::$namespace .= $namespace;

        // Добавление маршрутов
        $callable();

        // Возврат к предыдушему пространству
        self::$namespace = $previous;
    }

    /**
     * Добавление фильтра
     *
     * @param string  $key
     * @param Closure $callable
     */
    public static function filter($key, Closure $callable)
    {
        self::$filters[$key][] = $callable;
    }

    /**
     * Добавление фильтра "до"
     *
     * @param Closure $callable
     */
    public static function before(Closure $callable)
    {
        self::filter('before', $callable);
    }

    /**
     * Добавление фильтра "после"
     *
     * @param Closure $callable
     */
    public static function after(Closure $callable)
    {
        self::filter('after', $callable);
    }

    /**
     * Добавление фильтра "ошибки"
     *
     * @param Closure $callable
     */
    public static function error(Closure $callable)
    {
        self::filter('error', $callable);
    }

    /**
     * Возвращение сгенерированной ссылки основываясь на названном маршруте
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
            throw new RuntimeException(sprintf('Named route not found for name: %s', $name));
        }

        $search = [];

        // Готовим регулярные выражения для подставления параметров
        foreach ($params as $key => $value)
        {
            $search[] = '~@' . preg_quote($key, '~') . '(:([^/\(\)]*))\+?(?!\w)~';
        }

        // Подставляем параметры
        $pattern = preg_replace($search, $params, self::$namedRoutes[$name]);

        //Remove remnants of unpopulated, trailing optional pattern segments, escaped special characters
        return preg_replace('#\(/?@.+\)|\(|\)|\\\\#', '', $pattern);
    }

    /**
     * Диспетчеризация
     */
    public static function dispatch(array $routes = [])
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Подбор маршрутов с подходящим методом запроса и объединение маршрутов
        $routes = array_merge(self::$routes[$method], $routes);

        // Поиск статичного маршрута
        if (array_key_exists($uri, $routes))
        {
            return self::call($routes[$uri]);
        }

        // Поиск динамичного маршрута
        foreach ($routes as $pattern => $callable)
        {
            // Ключи в шаблоне
            $keys = [];

            // Плюшки
            $pattern = str_replace(array(')','*'), array(')?','.*?'), $pattern);

            // Заменяем параметры на регулярные выражения
            $pattern = preg_replace_callback('#@([\w]+)(:([^/\(\)]*))?#', function ($matches) use (&$keys)
            {
                $keys[] = $matches[1];

                if (isset($matches[3]))
                {
                    return "(?P<{$matches[1]}>{$matches[3]})";
                }

                return "(?P<{$matches[1]}>[^/\?]+)";
            }, $pattern);

            // Проверяем
            if (preg_match("~^{$pattern}$~", $uri, $matches))
            {
                $params = [];

                // Заносим параметры в массив
                foreach ($keys as $key)
                {
                    if (array_key_exists($key, $matches))
                    {
                        $params[$key] = urldecode($matches[$key]);
                    }
                }

                // Вызываем действие
                return self::call($callable, $params);
            }
        }

        // 404
        self::alert();
    }

    /**
     * Вызов действия
     *
     * @param Closure $callable
     * @param array   $arguments
     */
    private static function call(Closure $callable, array $arguments = [])
    {
        foreach (self::$filters['before'] as $before)
        {
            $before();
        }

        call_user_func_array($callable, $arguments);

        foreach (self::$filters['after'] as $after)
        {
            $after();
        }
    }

    /**
     * Вызов ошибки
     */
    public static function alert()
    {
        foreach (self::$filters['error'] as $error)
        {
            $error();
        }
    }
}