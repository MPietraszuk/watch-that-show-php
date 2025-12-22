<?php

declare(strict_types=1);

const CACHE_DIR = __DIR__ . '/../cache';

function cache_get(string $key, int $ttlSeconds): ?array
{
  $file = CACHE_DIR . '/' . sha1($key) . '.json';
  if (!is_file($file)) return null;

  $age = time() - filemtime($file);
  if ($age > $ttlSeconds) return null;

  $raw = file_get_contents($file);
  if ($raw === false) return null;

  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function cache_set(string $key, array $value): void
{
  if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0777, true);
  }
  $file = CACHE_DIR . '/' . sha1($key) . '.json';
  @file_put_contents($file, json_encode($value, JSON_UNESCAPED_SLASHES));
}
