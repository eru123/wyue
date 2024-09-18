<?php

namespace Wyue\Controllers;

use Wyue\Venv;

abstract class AbstractController {
    public function baseUrl(string $path = '') {
        $base = Venv::get('BASE_URL', '');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}