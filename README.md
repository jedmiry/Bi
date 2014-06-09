```php
<?php

require_once '../vendor/autoload.php';

// Диспетчеризация
Bi::dispatch([
    '/' => function () {
        echo 'Hello, World!';
    }
]);
```

```php
// Добавление маршрута
Bi::bind($method, $pattern, $callable, $name = null);

// Добавление маршрута для GET-запроса
Bi::get($pattern, $callable, $name = null);

// Добавление маршрута для POST-запроса
Bi::post($pattern, $callable, $name = null);

// Добавление маршрутов с префиксом к шаблону
Bi::prefix($prefix, Closure $callable);

// Определение обработчика ошибок
Bi::error($callable);

// Вызов обработчика ошибок
Bi::alert();

// Получение сгенерированной ссылки основываясь на названном маршруте
Bi::generate($name, array $params = []);

// Диспетчеризация
Bi::dispatch(array $routes = []);
```

# Установка

## Через Composer

В файл `composer.json` записываем:

```json
{
    "require": {
        "jedmiry/bi": "dev-master"
    }
}
```

Запускаем Composer: `composer install`. Подключаем автозагрузчик, например, в `index.php`:

```php
require_once '../vendor/autoload.php';
```

# Настройки сервера

## Apache

В файл `.htaccess` записываем:

```apacheconf
AddDefaultCharset UTF-8

<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    RewriteRule ^(.*)$ index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    ErrorDocument 404 index.php
</IfModule>
```