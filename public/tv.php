<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$pageTitle = 'Watch That Show • TV Shows';
$active = 'tv';

$error = '';
$results = [];

try {
  $page = (int)($_GET['page'] ?? 1);
  $page = max(1, min($page, 500));

  $data = tmdb_get('/tv/popular', [
    'language' => 'en-US',
    'page' => $page,
  ], 300);

  $results = $data['results'] ?? [];
  $totalPages = (int)($data['total_pages'] ?? 0);
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
  <?php if (!empty($results) && ($totalPages ?? 0) > 1): ?>
    <?php
    $prev = max(1, $page - 1);
    $next = min($totalPages, $page + 1);
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:16px;">
      <a class="btn <?= $page <= 1 ? 'disabled' : '' ?>" href="tv.php?page=<?= $prev ?>">← Prev</a>
      <span class="subtle">Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
      <a class="btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="tv.php?page=<?= $next ?>">Next →</a>
    </div>
  <?php endif; ?>
</main>

<?php view('footer'); ?>