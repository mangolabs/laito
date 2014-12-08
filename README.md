APIFramework
============

A simple API framework for PHP

by **[MangoLabs](http://www.mangolabs.com.ar/ "Mangolabs")**

---

# Installation

Require ApiFramework in your `composer.json` file:

```
#!json

    "require": {
      "mangolabs/apiframework": "dev-master"
    }

```

And run `composer install`. This will fetch and save the package and its dependencies into the `vendor` folder.

Once you installed it, create an `index.php` file in the route of your project and include the Composer's autoload file:

```
#!php

    require_once __DIR__ . '/vendor/autoload.php';

```

All requests have to be pointed to that `index.php` file. In Apache, you can do so by creating an `.htaccess` file with this rules:

```

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?route=$1 [L,QSA]

```

# Usage and configuration

Now you're ready to start using the framework. Create an app instance in your `index.php` file:

    $app = new ApiFramework\App();

You can pass an array of configuration options:

    $app = new ApiFramework\App([
        'database.name' => 'app',
        'database.username' => 'root',
        'database.password' => 'root',
    ]);

Or set them after initialization:

    $app->config('database.name', 'test');

The complete list of options is below.

    $defaultSettings = [
        'debug.queries'     => false,
        'auth.table'        => 'users',
        'auth.username'     => 'email',
        'auth.password'     => 'password',
        'sessions.folder'   => 'storage/sessions/',
        'sessions.ttl'      => 3600,
        'sessions.cookie'   => 'token',
        'reminders.folder'  => 'storage/reminders/',
        'reminders.ttl'     => 3600,
        'reminders.suffix'  => 'reminders_',
        'lang.folder'       => 'static/languages/',
        'request.emulate'   => true,
        'database.type'     => 'mysql',
        'database.server'   => 'localhost',
        'database.name'     => 'test',
        'database.username' => 'root',
        'database.password' => 'root',
        'public.url'        => 'localhost'
    ];

# Routing

With a route, you can bind an HTTP request of a certain type to a controller.

    $app->route->register('GET', '/hello/world', ['HelloController', 'world']);

The parameters are:

- The HTTP method (GET, POST, PUT or DELETE)
- The route to match (For example, '/posts')
- An array containing the controller name and method to execute

## Simple route

For example, this route will match the GET requests to `/posts` and execute the method `index` in the `PostsController` class:

    $app->router->register('GET', '/posts', ['PostsController', 'index']);

## Route placeholders

You can use wildcards to match variable pieces of the URL to your route. Define them in curly braces:

    $app->router->register('GET', '/posts/{id}', ['PostsController', 'show']);

The matched values will be passed to the executed controller's method. So, if the following URL is requested:

    GET /posts/14

The `show` method in `PostsController` can make use of the `id` value:

    function show ($id) {
        echo $id; // 14
    }

## Model injection

Many times, your controllers will make use of a model. But creating an instance of the model in the controller breaks the controller isolation. To follow the dependency injection patterns, those models can be injected into the controller just by passing the model's name as an additional parameter in the route's declaration.

For example, the following route will match the GET requests to `/posts` and execute the method `index` in the `PostsController` class, passing an instance of the Posts model.

    $app->router->register('GET', '/posts', ['PostsController', 'index'], 'Posts');

So you can make use of the injected model inside your controllers like this:

    public function test () {
        return $this->model->get();
    }

## Resources

A lot of times you'll find yourself declaring common routes for all your CRUD operations. In those cases, the resource method is a nice shortcut.

    $app->router->resource('/users', 'UsersController', 'User');

The parameters are:

- The route to match (For example, '/posts')
- The controller name
- Optionally, the model name to inject

It binds the following routes:

GET | /users | UsersController | index
GET | /users/{id} | UsersController | show
POST | /users | UsersController | store
PUT | /users/{id} | UsersController | update
DELETE | /users/{id} | UsersController | destroy

## Run

After you declared all your application routes, you can start listening for requests:

    $app->run();
