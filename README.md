# Пример

```php
<?php

require_once '../vendor/autoload.php';

// Добавление фильтра "после"
Bi::before(function () {
    echo 'Before - ';
});

// Добавление фильтра "до"
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

// Группирование маршрутов
Bi::group('/user', function () {
    // Можно использовать регулярные выражения, для параметров
    Bi::get('/([0-9]+)', function ($id) {
        echo "User (ID = {$id})";
    });
});

// Альтернативное добавление маршрутов и диспетчеризация
Bi::dispatch(['/' => function () {
    echo 'Hello, Vasya!';
}]);
```

# .htaccess

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