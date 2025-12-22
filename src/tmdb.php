<?php

declare(strict_types=1);

/**
 * Basic TMDB GET request with:
 * - API key injection
 * - caching
 * - error handling
 */
if (!function_exists('tmdb_get')) {
  function tmdb_get(string $path, array $query = [], int $cacheTtlSeconds = 900): array
  {
    $baseQuery = [
      'api_key'  => tmdb_api_key(),
      'language' => tmdb_language(),
    ];

    // Only include region if present (keeps requests cleaner)
    $region = tmdb_region();
    if ($region !== '') {
      $baseQuery['region'] = $region;
    }

    $query = array_merge($query, $baseQuery);

    // Ensure consistent cache keys by sorting query params
    ksort($query);

    $url = rtrim(TMDB_BASE_URL, '/') . '/' . ltrim($path, '/');
    $url .= '?' . http_build_query($query);

    // Cache
    $cached = cache_get($url, $cacheTtlSeconds);
    if (is_array($cached)) return $cached;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: MovieDB-VanillaPHP/1.0',
      ],
    ]);

    $raw = curl_exec($ch);
    if ($raw === false) {
      $err = curl_error($ch);
      curl_close($ch);
      throw new RuntimeException("TMDB request failed: {$err}");
    }

    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);
    if (!is_array($data)) {
      throw new RuntimeException("TMDB returned invalid JSON (HTTP {$status}).");
    }

    if ($status < 200 || $status >= 300) {
      $msg = $data['status_message'] ?? 'Unknown TMDB error';
      throw new RuntimeException("TMDB error (HTTP {$status}): {$msg}");
    }

    cache_set($url, $data);
    return $data;
  }
}

if (!function_exists('tmdb_poster_url')) {
  function tmdb_poster_url(?string $posterPath): string
  {
    return $posterPath ? (TMDB_IMG_W500 . $posterPath) : '';
  }
}

if (!function_exists('tmdb_backdrop_url')) {
  function tmdb_backdrop_url(?string $backdropPath): string
  {
    return $backdropPath ? (TMDB_IMG_W1280 . $backdropPath) : '';
  }
}
