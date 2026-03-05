<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../src/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));

if ($q === '' || strlen($q) < 2) {
  echo json_encode([
    'results' => []
  ]);
  exit;
}

try {

  $data = tmdb_get('/search/multi', [
    'query' => $q,
    'include_adult' => 'false',
    'language' => 'en-US',
    'page' => 1,
  ], 60);

  $results = $data['results'] ?? [];
  $out = [];

  foreach ($results as $item) {

    $type = $item['media_type'] ?? '';

    if ($type === 'movie') {
      $out[] = [
        'id' => $item['id'] ?? 0,
        'media_type' => 'movie',
        'title' => $item['title'] ?? 'Untitled',
        'date' => $item['release_date'] ?? '',
        'poster_path' => $item['poster_path'] ?? null,
      ];
    }

    if ($type === 'tv') {
      $out[] = [
        'id' => $item['id'] ?? 0,
        'media_type' => 'tv',
        'title' => $item['name'] ?? 'Untitled',
        'date' => $item['first_air_date'] ?? '',
        'poster_path' => $item['poster_path'] ?? null,
      ];
    }

    if ($type === 'person') {

      $knownFor = [];
      if (!empty($item['known_for']) && is_array($item['known_for'])) {
        foreach ($item['known_for'] as $kf) {
          $kType = $kf['media_type'] ?? '';
          if ($kType === 'movie') $knownFor[] = $kf['title'] ?? '';
          if ($kType === 'tv') $knownFor[] = $kf['name'] ?? '';
          if (count($knownFor) >= 3) break;
        }
      }

      // remove empty strings
      $knownFor = array_values(array_filter($knownFor, fn($s) => (string)$s !== ''));

      $out[] = [
        'id' => $item['id'] ?? 0,
        'media_type' => 'person',
        'title' => $item['name'] ?? 'Unknown',
        'date' => '',
        'poster_path' => $item['profile_path'] ?? null,
        'known_for' => $knownFor,
      ];
    }
  }

  echo json_encode([
    'results' => $out
  ]);
} catch (Throwable $e) {

  http_response_code(500);

  echo json_encode([
    'error' => $e->getMessage()
  ]);
}
