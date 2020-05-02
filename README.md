[![Latest Stable Version](https://poser.pugx.org/skateboard/framework/v/stable)](https://packagist.org/packages/skateboard/framework) [![Total Downloads](https://poser.pugx.org/skateboard/framework/downloads)](https://packagist.org/packages/skateboard/framework) [![License](https://poser.pugx.org/skateboard/framework/license)](https://packagist.org/packages/skateboard/framework)

> **Note:** This repository contains the core code of the Skateboard framework. If you want to build an application using Skateboard, visit the [Skateboard project repository](https://github.com/OneSheep/skateboard).

## About Skateboard

Minimalist PHP framework

## Features

- Easy and powerful routing
- Request input parsing
- json responses
- Template rendering with layout
- Aborts and redirects
- Environment file

## Installation

Install with Composer

```
composer require skateboard/framework
```

## Routing

See the [router docs](https://github.com/bramus/router)

## Controllers

### Properties

`viewPath` string  
The file path to the views folder relative to the controller. Defaults to "../views"

`layout` string  
The file name and extension of a template layout inside your viewPath to wrap your views.  
Defaults to "layout.php", so set this to `null` if you do not use a layout

### Methods

`view($view, $data = [], $raw = [])`  
Render a view template with some data.

- string \$view file name of the view in the viewPath folder such as 'about'
- array \$data items that will be escaped and provided to the view
- array \$raw safe items that will be rendered without escaping

`json($output, $code = 200)`  
Render a json response.

- mixed \$output that will be json encoded
- int \$code status code of the http response

`redirect($url)`  
Redirects the request to another route.

- string \$url the relative or absolute route to redirect to such as "/about"

`abort($code, $html = null)`  
Exits the request with a status code.

- int \$code the 3 digit http response code
- string \$html any markup to return in the response body

`html($output, $code = 200)`  
Respond with html markup and a response code.

- string \$output the html that should be sent in the response
- int \$code the http response code to respond with

`requestIsJson()`  
Checks if the request is a json request based on the Accept header.

Returns bool true if the Accept header specifies json format otherwise false

`getHeaderLine($key, $default = null)`  
Get the value of a request header.

- string \$key the header key to use to look up the value such as 'Accept'
- string \$default the default to return if the key is not found in the request header

Returns a string that containts the value of the specified request header

`input($key)`  
Get a request get parameter, post value or a cookie value.

- string \$key that identifies the parameter or cookie to get

Returns a string with the value that was passed with the request

## Views

PHP is already a templating language, so all we need in our views is a little self-discipline:

views/layout.php

```php
<!DOCTYPE html>
<html>
  <head>
    <title>My App</title>
  </head>
  <body>
    <nav>
      <ul>
        <li><a href="/about">About</a></li>
        ...
      </ul>
    </nav>

    <?= $content; ?>
  </body>
</html>
```

views/about.php

```php
<div>
  <h2>About</h2>
  <ul>
    <?php foreach ($headers as $key => $value): ?>
    <li><?= "$key:  $value" ?></li>
    <?php endforeach; ?>
  </ul>
</div>
```

## License

`skateboard/framework` is released under the MIT public license. See the enclosed `LICENSE` for details.
