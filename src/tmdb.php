<?php

declare(strict_types=1);

const TMDB_BASE = 'https://api.themoviedb.org/3';
const TMDB_IMG  = 'https://image.tmdb.org/t/p/';

function tmdb_api_key(): string
{
  // .env is loaded in bootstrap.php via load_env(...)
  $key = $_ENV['TMDB_API_KEY'] ?? getenv('TMDB_API_KEY');
  if (!$key) {
    throw new RuntimeException('TMDB_API_KEY not set. Add it to .env');
  }
  return $key;
}

function tmdb_cache_dir(): string
{
  // Prefer a writable directory OUTSIDE src
  $dir = dirname(__DIR__) . '/cache/tmdb';
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  return $dir;
}

function tmdb_get(string $path, array $params = [], int $ttlSeconds = 0): array
{
  $params = array_merge([
    'api_key'  => tmdb_api_key(),
    'language' => 'en-US',
  ], $params);

  $url = TMDB_BASE . $path . '?' . http_build_query($params);

  // Optional file cache
  $cacheFile = '';
  if ($ttlSeconds > 0) {
    $cacheFile = tmdb_cache_dir() . '/' . sha1($url) . '.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $ttlSeconds)) {
      $cached = json_decode((string)file_get_contents($cacheFile), true);
      if (is_array($cached)) return $cached;
    }
  }

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
  ]);

  $raw  = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false) {
    return ['error' => 'Request failed', 'detail' => $err];
  }
  if ($code < 200 || $code >= 300) {
    return ['error' => 'TMDB error', 'status' => $code, 'body' => $raw];
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return ['error' => 'Invalid JSON from TMDB'];
  }

  if ($ttlSeconds > 0 && $cacheFile) {
    @file_put_contents($cacheFile, json_encode($data));
  }

  return $data;
}

function tmdb_img(?string $path, string $size = 'w342'): string
{
  return $path ? TMDB_IMG . $size . $path : '';
}

function tmdb_poster_url(?string $posterPath, string $size = 'w342'): string
{
  return tmdb_img($posterPath, $size);
}

function tmdb_profile_url(?string $profilePath, string $size = 'w185'): string
{
  return tmdb_img($profilePath, $size);
}
