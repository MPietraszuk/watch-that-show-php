<?php

declare(strict_types=1);

const TMDB_BASE_URL   = 'https://api.themoviedb.org/3';
const TMDB_IMG_W500   = 'https://image.tmdb.org/t/p/w500';
const TMDB_IMG_W1280  = 'https://image.tmdb.org/t/p/w1280';

function config(string $key, string $default = ''): string
{
  $val = getenv($key);
  return ($val === false || $val === '') ? $default : $val;
}

function tmdb_api_key(): string
{
  $key = config('TMDB_API_KEY');
  if ($key === '') {
    throw new RuntimeException('Missing TMDB_API_KEY. Create a .env file from .env.example.');
  }
  return $key;
}

function tmdb_language(): string
{
  return config('TMDB_LANGUAGE', 'en-US');
}

function tmdb_region(): string
{
  return config('TMDB_REGION', 'US');
}
