```php
<?php

require_once '../vendor/autoload.php';

Bi::dispatch([
    '/' => function () {
        echo 'Hello, World!';
    }
]);
```

```php
// add a new route
Bi::bind($method, $pattern, $callable, $name = null);

// add a group of routes
Bi::prefix($prefix, Closure $callable);

// define error handler
Bi::error($callable);

// call error handler
Bi::alert();

// generate a link from route with parameters
Bi::generate($name, array $params = []);

// dispatching
Bi::dispatch(array $routes = []);
```

# Installing

## Via Composer

Write in `composer.json`:

```json
{
    "require": {
        "jedmiry/bi": "dev-master"
    }
}
```

Run Composer: `composer install`. Including autoloader, for example, to `index.php`:

```php
require_once '../vendor/autoload.php';
```

# Server settings

## Apache

Write in `.htaccess`:

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
