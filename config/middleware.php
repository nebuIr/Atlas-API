<?php

use Middlewares\TrailingSlash;
use Slim\App;

return static function (App $app) {
    $app->add((new TrailingSlash(false))->redirect());
};
