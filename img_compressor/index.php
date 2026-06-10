<?php

declare(strict_types=1);

use ImgCompressor\Http\Request;

/** @var \ImgCompressor\Http\Router $router */
$router = require __DIR__ . '/bootstrap.php';
$router->dispatch(Request::fromGlobals());
