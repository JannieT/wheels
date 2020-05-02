<?php

/**
 * @author      Jannie Theunissen <jannie@onesheep.org>
 * @copyright   Copyright (c), 2020 Jannie Theunissen
 * @license     MIT public license
 */

namespace Skateboard\Wheels;

use Dotenv\Dotenv;

class Application extends \Bramus\Router\Router
{
    public function __construct($dir)
    {
        if (file_exists("$dir/../.env")) {
            $dotenv = Dotenv::createImmutable($dir.'/..');
            $dotenv->load();
        }
        $this->setBasePath('/');
    }
}
