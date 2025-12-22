<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
// For live search, avoid browser caching; server-side cache handles it.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../../src/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
$page = max(1, min($page, 5)); // keep small for typeahead

$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

// Basic guardrails
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

// simple guard
if (mb_strlen($q) > 80) {
  http_response_code(400);
  echo json_encode(['error' => 'Query too long.'], $flags);
  exit;
}

try {
  $data = tmdb_get('/search/movie', [
    'query' => $q,
    'include_adult' => 'false',
    'page' => $page,
  ], 60); // short server cache TTL

  $results = $data['results'] ?? [];

  $out = array_map(static function (array $m): array {
    return [
      'id' => (int)($m['id'] ?? 0),
      'title' => (string)($m['title'] ?? ''),
      'release_date' => (string)($m['release_date'] ?? ''),
      'poster_path' => $m['poster_path'] ?? null,
      'vote_average' => $m['vote_average'] ?? null,
    ];
  }, is_array($results) ? $results : []);

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
