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
$movieResults = [];
$tvResults = [];
$personResults = [];
$totalPages = 0;

if ($q !== '') {
  try {
    $data = tmdb_get('/search/multi', [
      'query' => $q,
      'include_adult' => 'false',
      'language' => 'en-US',
      'page' => $page,
    ], 300);

    $results = $data['results'] ?? [];
    $totalPages = (int)($data['total_pages'] ?? 0);

    // Keep only movie/tv/person (TMDB can sometimes return other types)
    $results = array_values(array_filter($results, static function ($item) {
      $t = $item['media_type'] ?? '';
      return $t === 'movie' || $t === 'tv' || $t === 'person';
    }));
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
    <label class="sr-only" for="q">Search movies, TV shows, or people</label>
    <input
      id="q"
      name="q"
      type="search"
      placeholder="Search for a movie, TV show, or person…"
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
      $type = (string)($m['media_type'] ?? '');
      $id = (int)($m['id'] ?? 0);

      // Title + Date fields differ by type
      if ($type === 'tv') {
        $title = (string)($m['name'] ?? 'Untitled');
        $date = (string)($m['first_air_date'] ?? '');
        $href = "tvshow.php?id={$id}";
        $posterUrl = tmdb_poster_url($m['poster_path'] ?? null);
        $badge = 'TV';
      } elseif ($type === 'person') {
        $title = (string)($m['name'] ?? 'Unknown');
        $date = '';
        $href = "person.php?id={$id}";
        $posterUrl = tmdb_profile_url($m['profile_path'] ?? null, 'w185');
        $badge = 'Person';
      } else { // movie
        $title = (string)($m['title'] ?? 'Untitled');
        $date = (string)($m['release_date'] ?? '');
        $href = "movie.php?id={$id}";
        $posterUrl = tmdb_poster_url($m['poster_path'] ?? null);
        $badge = 'Movie';
      }

      $year = $type === 'person' ? '' : year_from_date($date);
      ?>
      <a class="card" href="<?= e($href) ?>">
        <div class="poster">
          <?php if ($posterUrl): ?>
            <img loading="lazy" src="<?= e($posterUrl) ?>" alt="<?= e($title) ?>">
          <?php else: ?>
            <div class="poster-fallback">No Image</div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="title"><?= e($title) ?></div>
          <div class="meta">
            <?= $year ? e($year) . ' • ' : '' ?><?= e($badge) ?>
          </div>
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