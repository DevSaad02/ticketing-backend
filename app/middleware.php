<?php

declare(strict_types=1);

use Slim\App;
use App\Middleware\CorsMiddleware;
use App\Application\Middleware\SessionMiddleware;

return function (App $app) {
    $app->add(CorsMiddleware::class);
    $app->add(SessionMiddleware::class);
};
