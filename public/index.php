<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/**
 * Server-side (non-JS) search
 * - Used for initial page load
 * - Used if JS is disabled
 */
$q = trim((string)($_GET['q'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
$page = max(1, min($page, 500));

$results = [];
$totalPages = 0;
$error = '';

if ($q !== '') {
  try {
    $data = tmdb_get('/search/movie', [
      'query' => $q,
      'include_adult' => 'false',
      'page' => $page,
    ], 300);

    $results = $data['results'] ?? [];
    $totalPages = (int)($data['total_pages'] ?? 0);
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

$pageTitle = 'Watch That Show • Search';
$active = 'search';

view('header', compact('pageTitle', 'active'));
?>

<main class="container">

  <!-- Search form -->
  <form class="search" method="get" action="index.php" novalidate>
    <label class="sr-only" for="q">Search movies</label>
    <input
      id="q"
      name="q"
      type="search"
      placeholder="Search for a movie…"
      value="<?= e($q) ?>"
      autocomplete="off">
    <button type="submit">Search</button>
  </form>

  <!-- Status line (PHP renders initial state; JS updates it later) -->
  <div id="liveStatus" class="subtle" style="min-height:18px;">
    <?php if ($q === ''): ?>
      Start typing to search.
    <?php elseif ($error === ''): ?>
      Showing results for “<?= e($q) ?>”.
    <?php endif; ?>
  </div>

  <!-- Error message -->
  <?php if ($error !== ''): ?>
    <div class="alert">Error: <?= e($error) ?></div>
  <?php endif; ?>

  <!-- Results grid (JS replaces ONLY this div) -->
  <div id="resultsGrid" class="grid">

    <?php if ($q !== '' && empty($results) && $error === ''): ?>
      <p class="subtle">No results found.</p>
    <?php endif; ?>

    <?php foreach ($results as $m): ?>
      <?php
      $id = (int)($m['id'] ?? 0);
      $title = (string)($m['title'] ?? 'Untitled');
      $year = year_from_date((string)($m['release_date'] ?? ''));
      $posterUrl = tmdb_poster_url($m['poster_path'] ?? null);
      ?>
      <a class="card" href="movie.php?id=<?= $id ?>">
        <div class="poster">
          <?php if ($posterUrl): ?>
            <img loading="lazy" src="<?= e($posterUrl) ?>" alt="<?= e($title) ?> poster">
          <?php else: ?>
            <div class="poster-fallback">No Image</div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="title"><?= e($title) ?></div>
          <div class="meta"><?= $year ? e($year) : '—' ?></div>
        </div>
      </a>
    <?php endforeach; ?>

  </div>

  <!-- Pagination (non-JS fallback only) -->
  <?php if ($q !== '' && $totalPages > 1): ?>
    <div class="section-title"></div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <?php
      $prev = max(1, $page - 1);
      $next = min($totalPages, $page + 1);
      ?>
      <a class="btn <?= $page <= 1 ? 'disabled' : '' ?>" href="index.php?q=<?= urlencode($q) ?>&page=<?= $prev ?>">← Prev</a>
      <span class="subtle">Page <?= $page ?> of <?= $totalPages ?></span>
      <a class="btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="index.php?q=<?= urlencode($q) ?>&page=<?= $next ?>">Next →</a>
    </div>
  <?php endif; ?>

</main>

<?php view('footer'); ?>