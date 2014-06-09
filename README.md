# Установка через Composer

## composer.json
```json
{
    "require": {
        "jedmiry/bi": "dev-master"
    }
}
```

## index.php

```php
require_once '../vendor/autoload.php';
```

# Настройки сервера

## .htaccess

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

# Примеры

```php
// Диспетчеризация
Bi::dispatch(['/' => function () {
    echo 'Hello, World!';
}]);
```

## Методы
```php
Bi::get('/', function () {
    echo 'Hello, World!';
});

Bi::dispatch();
```

## Регулярные выражения

Позаимствовано у [flight](https://github.com/mikecao/flight).

```php
Bi::get('/post/@id:[0-9]+', function ($id) {
    echo "post: {$id}";
});

Bi::get('/user/@id:[0-9]+(/@action:update|delete)', function ($id, $action = 'update') {
    echo "user: {$id}; (action: {$action})";
});

Bi::dispatch();
```

## Генерация ссылок основываясь на названных маршрутах

Позаимствовано у [slim](https://github.com/codeguy/slim).

```php
Bi::get('/post/@id:[0-9]+', function ($id) {
    echo "post: {$id}";
}, 'post');

echo Bi::generate('post', ['id' => 666]); // /post/666
```

## Фильтры
```php
// Добавление фильтра "до"
Bi::before(function () {
    echo 'Before - ';
});

// Добавление фильтра "после"
Bi::after(function () {
    echo ' - After';
});

// Добавление фильтра "ошибки"
Bi::error(function () {
    echo 'Page not found';
});

// Добавление маршрута
Bi::get('/', function () {
    echo 'Hello, World!';
});

// Диспетчеризация
Bi::dispatch();
```