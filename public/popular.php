<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$page = (int)($_GET['page'] ?? 1);
$page = max(1, min($page, 500));

$results = [];
$totalPages = 0;
$error = '';

try {
  $data = tmdb_get('/movie/popular', [
    'page' => $page,
  ], 600);

  $results = $data['results'] ?? [];
  $totalPages = (int)($data['total_pages'] ?? 0);
} catch (Throwable $e) {
  $error = $e->getMessage();
}

$pageTitle = 'Movie DB • Popular';
$active = 'popular';
view('header', compact('pageTitle', 'active'));
?>

<main class="container">
  <h2 class="section-title">Popular Movies</h2>

  <?php if ($error !== ''): ?>
    <div class="alert">Error: <?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($results) && $error === ''): ?>
    <p class="subtle">No movies found.</p>
  <?php endif; ?>

  <div class="grid">
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

  <?php if ($totalPages > 1): ?>
    <div class="section-title"></div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <?php
      $prev = max(1, $page - 1);
      $next = min($totalPages, $page + 1);
      ?>
      <a class="btn <?= $page <= 1 ? 'disabled' : '' ?>" href="popular.php?page=<?= $prev ?>">← Prev</a>
      <span class="subtle">Page <?= $page ?> of <?= $totalPages ?></span>
      <a class="btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="popular.php?page=<?= $next ?>">Next →</a>
    </div>
  <?php endif; ?>
</main>

<?php view('footer'); ?>