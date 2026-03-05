<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../../src/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
$page = max(1, min($page, 5)); // keep small for typeahead

$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode([
    'query' => $q,
    'page' => $page,
    'results' => [],
    'total_pages' => 0,
    'total_results' => 0,
  ], $flags);
  exit;
}

if (mb_strlen($q) > 80) {
  http_response_code(400);
  echo json_encode(['error' => 'Query too long.'], $flags);
  exit;
}

try {
  $data = tmdb_get('/search/multi', [
    'query' => $q,
    'include_adult' => 'false',
    'language' => 'en-US',
    'page' => $page,
  ], 60);

  $results = $data['results'] ?? [];
  $out = [];

  foreach ($results as $item) {
    $type = (string)($item['media_type'] ?? '');

    if ($type === 'movie') {
      $out[] = [
        'id' => (int)($item['id'] ?? 0),
        'media_type' => 'movie',
        'title' => (string)($item['title'] ?? 'Untitled'),
        'date' => (string)($item['release_date'] ?? ''),
        'poster_path' => $item['poster_path'] ?? null,
        'vote_average' => $item['vote_average'] ?? null,
      ];
      continue;
    }

    if ($type === 'tv') {
      $out[] = [
        'id' => (int)($item['id'] ?? 0),
        'media_type' => 'tv',
        'title' => (string)($item['name'] ?? 'Untitled'),
        'date' => (string)($item['first_air_date'] ?? ''),
        'poster_path' => $item['poster_path'] ?? null,
        'vote_average' => $item['vote_average'] ?? null,
      ];
      continue;
    }

    if ($type === 'person') {
      $out[] = [
        'id' => (int)($item['id'] ?? 0),
        'media_type' => 'person',
        'title' => (string)($item['name'] ?? 'Unknown'),
        'date' => '',
        'poster_path' => $item['profile_path'] ?? null, // JS builds image URL
        'vote_average' => null,
      ];
      continue;
    }
  }

  echo json_encode([
    'query' => $q,
    'page' => $page,
    'results' => $out,
    'total_pages' => (int)($data['total_pages'] ?? 0),
    'total_results' => (int)($data['total_results'] ?? 0),
  ], $flags);
} catch (Throwable $e) {
  http_response_code(502);
  echo json_encode(['error' => $e->getMessage()], $flags);
}
