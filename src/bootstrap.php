<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Minimal .env loader (KEY=VALUE lines).
 * - No dependencies
 * - Good enough for portfolio projects
 */
if (!function_exists('load_env')) {
  function load_env(string $path): void
  {
    if (!is_file($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) continue;

      $pos = strpos($line, '=');
      if ($pos === false) continue;

      $key = trim(substr($line, 0, $pos));
      $val = trim(substr($line, $pos + 1));

      // strip surrounding quotes
      if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
        (str_starts_with($val, "'") && str_ends_with($val, "'"))
      ) {
        $val = substr($val, 1, -1);
      }

      if ($key !== '' && getenv($key) === false) {
        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
      }
    }
  }
}

// Load .env from project root
load_env(dirname(__DIR__) . '/.env');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/tmdb.php';

if (!function_exists('view')) {
  function view(string $name, array $vars = []): void
  {
    // allow only simple view names: header, footer, etc.
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
      throw new InvalidArgumentException('Invalid view name.');
    }

    $file = __DIR__ . "/views/{$name}.php";
    if (!is_file($file)) {
      throw new RuntimeException("View not found: {$name}");
    }

    extract($vars, EXTR_SKIP);
    require $file;
  }
}
