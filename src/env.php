<?php

declare(strict_types=1);

/**
 * Minimal .env loader for vanilla PHP projects
 * Loads key=value pairs into $_ENV and getenv()
 */

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
  // Fail fast — your app depends on env vars
  throw new RuntimeException('.env file not found in project root');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
  $line = trim($line);

  // Skip comments and blank lines
  if ($line === '' || str_starts_with($line, '#')) {
    continue;
  }

  // Split on first "=" only
  [$key, $value] = array_map('trim', explode('=', $line, 2));

  // Remove optional surrounding quotes
  $value = trim($value, "\"'");

  // Load into environment
  $_ENV[$key] = $value;
  putenv("$key=$value");
}
// --- IGNORE ---
// End of .env loader
