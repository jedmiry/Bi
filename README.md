# Примеры

```php
<?php

require_once '../vendor/autoload.php';

// Определяем фильтр "ошибки"
Bi::error(function () {
    echo 'Page not found!';
});

// Добавление фильтра "до"
Bi::before(function () {
    echo 'Before - ';
});

// Добавление фильтра "после"
Bi::after(function () {
    echo ' - After';
});

// Добавляем маршрут
Bi::get('/', function () {
    // Генерация ссылки
    echo '<a href="' . Bi::generate('post', ['id' => 666]) . '">post</a>'; // /post/666
}, 'index');

// Добавляем маршрут
Bi::get('/post/@id:[0-9]+', function ($id) {
    echo "post: {$id};";
}, 'post');

// Диспетчеризация
Bi::dispatch();
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

И запускаем Composer: `composer install`. После, подключаем автозагрузчик в `index.php`:

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