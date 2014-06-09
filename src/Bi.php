<?php

/**
 * Bi - простой маршрутизатор
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
     * Названные маршруты
     *
     * @var array
     */
    private static $namedRoutes = [];

    /**
     * Текущий префикс маршрутов
     *
     * @var string
     */
    private static $prefix;

    /**
     * Обработчик ошибки
     *
     * @var Closure
     */
    private static $error;

    /**
     * Добавление маршрута
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
     * Добавление маршрута для GET-запроса
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
     * Добавление маршрута для POST-запроса
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
     * Добавление маршрутов с префиксом к шаблону
     *
     * @param string  $prefix
     * @param Closure $callable
     */
    public static function prefix($prefix, Closure $callable)
    {
        // Сохранение старого префикса
        $previous = self::$prefix;

        // Добавление нового
        self::$prefix .= $prefix;

        // Добавление маршрутов
        $callable();

        // Возврат к предыдушему префиксу
        self::$prefix = $previous;
    }

    /**
     * Определение обработчика ошибок
     *
     * @param mixed $callable
     */
    public static function error($callable)
    {
        self::$error = $callable;
    }

    /**
     * Вызов обработчика ошибок
     */
    public static function alert()
    {
        if (!self::$error)
        {
            self::$error = function ()
            {
                // Статус
                http_response_code(404);

                // Сообщение
                echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>That page doesn\'t exist!</p></body></html>';
            };
        }

        self::call(self::$error);
    }

    /**
     * Получение сгенерированной ссылки основываясь на названном маршруте
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

        // Подготовка регулярных выражений к подмене параметров
        foreach ($params as $key => $value)
        {
            $search[] = '~@' . preg_quote($key, '~') . '(:([^/\(\)]*))\+?(?!\w)~';
        }

        // Подменяем параметры
        $pattern = preg_replace($search, $params, self::$namedRoutes[$name]);

        // Remove remnants of unpopulated, trailing optional pattern segments, escaped special characters
        return preg_replace('#\(/?@.+\)|\(|\)|\\\\#', '', $pattern);
    }

    /**
     * Диспетчеризация
     *
     * @return mixed
     */
    public static function dispatch(array $routes = [])
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Подбор маршрутов с подходящим методом запроса и их объединение
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

            // Маски
            $pattern = str_replace(array(')','*'), array(')?','.*?'), $pattern);

            // Замена параметров на регулярные выражения
            $pattern = preg_replace_callback('~@([\w]+)(:([^/\(\)]*))?~', function ($matches) use (&$keys)
            {
                $keys[] = $matches[1];

                if (isset($matches[3]))
                {
                    return "(?P<{$matches[1]}>{$matches[3]})";
                }

                return "(?P<{$matches[1]}>[^/\?]+)";
            }, $pattern);

            // Проверка
            if (preg_match("~^{$pattern}$~", $uri, $matches))
            {
                $params = [];

                // Занесение параметров в массив
                foreach ($keys as $key)
                {
                    if (array_key_exists($key, $matches))
                    {
                        $params[$key] = urldecode($matches[$key]);
                    }
                }

                // Вызов действия
                return self::call($callable, $params);
            }
        }

        // Ошибка
        self::alert();
    }

    /**
     * Вызов действия
     *
     * @param mixed $callable
     * @param array $arguments
     */
    private static function call($callable, array $arguments = [])
    {
        call_user_func_array($callable, $arguments);
    }
}