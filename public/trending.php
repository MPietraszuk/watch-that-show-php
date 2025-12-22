<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

// day | week
$timeWindow = (string)($_GET['t'] ?? 'week');
$timeWindow = $timeWindow === 'day' ? 'day' : 'week';

$results = [];
$error = '';

try {
  $data = tmdb_get("/trending/movie/{$timeWindow}", [], 600);
  $results = $data['results'] ?? [];
} catch (Throwable $e) {
  $error = $e->getMessage();
}

$pageTitle = 'Movie DB • Trending';
$active = 'trending';
view('header', compact('pageTitle', 'active'));
?>

<main class="container">
  <h2 class="section-title">
    Trending Movies (<?= $timeWindow === 'day' ? 'Today' : 'This Week' ?>)
  </h2>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0;">
    <a class="btn <?= $timeWindow === 'day' ? 'disabled' : '' ?>" href="trending.php?t=day">Today</a>
    <a class="btn <?= $timeWindow === 'week' ? 'disabled' : '' ?>" href="trending.php?t=week">This Week</a>
  </div>

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
</main>

<?php view('footer'); ?>