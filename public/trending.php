<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

// movie | tv
$type = (string)($_GET['type'] ?? 'movie');
$type = $type === 'tv' ? 'tv' : 'movie';

// day | week
$timeWindow = (string)($_GET['t'] ?? 'week');
$timeWindow = $timeWindow === 'day' ? 'day' : 'week';

$results = [];
$error = '';

try {
  $data = tmdb_get("/trending/{$type}/{$timeWindow}", [], 600);
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
    Trending <?= $type === 'tv' ? 'TV Shows' : 'Movies' ?> (<?= $timeWindow === 'day' ? 'Today' : 'This Week' ?>)
  </h2>

  <!-- Movies / TV toggle -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0;">
    <a class="btn <?= $type === 'movie' ? 'disabled' : '' ?>" href="trending.php?type=movie&t=<?= e($timeWindow) ?>">Movies</a>
    <a class="btn <?= $type === 'tv' ? 'disabled' : '' ?>" href="trending.php?type=tv&t=<?= e($timeWindow) ?>">TV Shows</a>
  </div>

  <!-- Day / Week toggle -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0;">
    <a class="btn <?= $timeWindow === 'day' ? 'disabled' : '' ?>" href="trending.php?type=<?= e($type) ?>&t=day">Today</a>
    <a class="btn <?= $timeWindow === 'week' ? 'disabled' : '' ?>" href="trending.php?type=<?= e($type) ?>&t=week">This Week</a>
  </div>

  <?php if ($error !== ''): ?>
    <div class="alert">Error: <?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($results) && $error === ''): ?>
    <p class="subtle">No <?= $type === 'tv' ? 'TV shows' : 'movies' ?> found.</p>
  <?php endif; ?>

  <div class="grid">
    <?php foreach ($results as $m): ?>
      <?php
      $id = (int)($m['id'] ?? 0);

      // ✅ Movies use title/release_date; TV uses name/first_air_date
      $title = (string)(($type === 'tv' ? ($m['name'] ?? null) : ($m['title'] ?? null)) ?? 'Untitled');
      $date  = (string)(($type === 'tv' ? ($m['first_air_date'] ?? null) : ($m['release_date'] ?? null)) ?? '');
      $year  = year_from_date($date);

      $posterUrl = tmdb_poster_url($m['poster_path'] ?? null);

      // ✅ Link target depends on type
      $detailsPage = $type === 'tv' ? 'tvshow.php' : 'movie.php';
      ?>
      <a class="card" href="<?= $detailsPage ?>?id=<?= $id ?>">
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