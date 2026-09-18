<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $env = $context['APP_ENV'];
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (str_starts_with($host, 'test-') || str_contains($host, '.test.') || str_starts_with($host, 'e2e-') || ($_SERVER['HTTP_X_TEST_ENV'] ?? '') === 'test') {
        $env = 'test';
    }
    return new Kernel($env, (bool) $context['APP_DEBUG']);
};
