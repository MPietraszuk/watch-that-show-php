<?php

declare(strict_types=1);

if (!function_exists('e')) {
  function e(mixed $s): string
  {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('url_with')) {
  function url_with(array $params): string
  {
    $merged = array_merge($_GET, $params);
    $path = $_SERVER['PHP_SELF'] ?? strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?? '';
    return $path . '?' . http_build_query($merged);
  }
}

if (!function_exists('minutes_to_runtime')) {
  function minutes_to_runtime(?int $minutes): string
  {
    if (!$minutes || $minutes <= 0) return '—';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
  }
}

if (!function_exists('year_from_date')) {
  function year_from_date(?string $date): string
  {
    if (!$date) return '';
    return substr($date, 0, 4);
  }
}

function tmdb_profile_url(?string $path): string
{
  return $path ? 'https://image.tmdb.org/t/p/w185' . $path : '';
}
