<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);

$pageTitle = 'Watch That Show • Person';
$active = 'search';

if ($id <= 0) {
  view('header', compact('pageTitle', 'active'));
  echo '<main class="container"><div class="alert">Missing/invalid person id.</div></main>';
  view('footer');
  exit;
}

$error = '';
$person = [];
$credits = [];
$movies = [];

try {
  $person  = tmdb_get("/person/{$id}", [], 300);
  $credits = tmdb_get("/person/{$id}/movie_credits", [], 300);

  $movies = $credits['cast'] ?? [];

  usort($movies, fn($a, $b) => strcmp(
    (string)($b['release_date'] ?? ''),
    (string)($a['release_date'] ?? '')
  ));
} catch (Throwable $e) {
  $error = $e->getMessage();
}

$name = (string)($person['name'] ?? 'Unknown');
$profileUrl = tmdb_profile_url($person['profile_path'] ?? null, 'w342');

$pageTitle = 'Watch That Show • ' . ($name ?: 'Person');

view('header', compact('pageTitle', 'active'));
?>

<main class="container" style="text-align:left;">

  <p><a class="btn" href="javascript:history.back()">← Back</a></p>

  <?php if ($error !== ''): ?>
    <div class="alert">Error: <?= e($error) ?></div>
  <?php endif; ?>

  <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
    <div style="width:220px;">
      <div class="poster">
        <?php if ($profileUrl): ?>
          <img loading="lazy" src="<?= e($profileUrl) ?>" alt="<?= e($name) ?>">
        <?php else: ?>
          <div class="poster-fallback">No Image</div>
        <?php endif; ?>
      </div>
    </div>

    <div style="flex:1; min-width:260px;">
      <h1 style="margin:0 0 6px;"><?= e($name) ?></h1>

      <div class="subtle" style="margin-bottom:10px;">
        <?php if (!empty($person['birthday'])): ?>
          <span><strong>Born:</strong> <?= e((string)$person['birthday']) ?></span>
        <?php endif; ?>
        <?php if (!empty($person['place_of_birth'])): ?>
          <span><?= !empty($person['birthday']) ? ' • ' : '' ?><?= e((string)$person['place_of_birth']) ?></span>
        <?php endif; ?>
      </div>

      <?php if (!empty($person['biography'])): ?>
        <p><?= nl2br(e((string)$person['biography'])) ?></p>
      <?php else: ?>
        <p class="subtle">No biography available.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="section-title" style="margin-top:18px;">Filmography</div>

  <?php $movies = array_slice($movies, 0, 100); ?>

  <?php if (empty($movies)): ?>
    <p class="subtle">No movie credits found.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($movies as $m): ?>
        <?php
        $mid = (int)($m['id'] ?? 0);
        $title = (string)($m['title'] ?? $m['original_title'] ?? 'Untitled');
        $year = year_from_date((string)($m['release_date'] ?? ''));
        $role = (string)($m['character'] ?? '');
        $posterUrl = tmdb_poster_url($m['poster_path'] ?? null);
        ?>
        <a class="card" href="movie.php?id=<?= $mid ?>">
          <div class="poster">
            <?php if ($posterUrl): ?>
              <img loading="lazy" src="<?= e($posterUrl) ?>" alt="<?= e($title) ?> poster">
            <?php else: ?>
              <div class="poster-fallback">No Image</div>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="title"><?= e($title) ?></div>
            <div class="meta">
              <?= $year ? e($year) : '—' ?>
              <?= $role ? ' • ' . e($role) : '' ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<?php view('footer'); ?>