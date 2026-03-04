<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$pageTitle = 'Watch That Show • TV Shows';
$active = 'tv';

$error = '';
$results = [];

try {
  $data = tmdb_get('/tv/popular', [
    'language' => 'en-US',
    'page' => 1,
  ], 300);

  $results = $data['results'] ?? [];
} catch (Throwable $e) {
  $error = $e->getMessage();
}

view('header', compact('pageTitle', 'active'));
?>

<main class="container">

  <h1 style="margin-top:0;">Popular TV Shows</h1>

  <?php if ($error !== ''): ?>
    <div class="alert">Error: <?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($results)): ?>
    <p class="subtle">No TV shows found.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($results as $m): ?>
        <?php
        $id = (int)($m['id'] ?? 0);
        $title = (string)($m['name'] ?? 'Untitled'); // ✅ TV uses "name"
        $year = year_from_date((string)($m['first_air_date'] ?? '')); // ✅ TV uses "first_air_date"
        $posterUrl = tmdb_poster_url($m['poster_path'] ?? null);
        ?>
        <a class="card" href="tvshow.php?id=<?= $id ?>">
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
  <?php endif; ?>

</main>

<?php view('footer'); ?>