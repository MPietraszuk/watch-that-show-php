<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);

$pageTitle = 'Watch That Show • Movie';
$active = 'search';

if ($id <= 0) {
  view('header', compact('pageTitle', 'active'));
  echo '<main class="container"><div class="alert">Missing/invalid movie id.</div></main>';
  view('footer');
  exit;
}

$error = '';
$movie = [];
$cast  = [];

$videos = [];
$trailerKey = null;

try {
  $movie = tmdb_get("/movie/{$id}", [
    'append_to_response' => 'credits',
    'include_image_language' => 'en,null',
  ], 300);

  $cast = $movie['credits']['cast'] ?? [];

  // ✅ Trailer / videos
  $videos = tmdb_get("/movie/{$id}/videos", [], 300);

  if (!empty($videos['results'])) {
    foreach ($videos['results'] as $video) {
      if (($video['type'] ?? '') === 'Trailer' && ($video['site'] ?? '') === 'YouTube') {
        $trailerKey = (string)$video['key'];
        break;
      }
    }
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
}

if (!empty($videos['results'])) {
  foreach ($videos['results'] as $video) {

    $type = $video['type'] ?? '';
    $site = $video['site'] ?? '';

    if ($site === 'YouTube' && in_array($type, ['Trailer', 'Teaser', 'Clip'], true)) {
      $trailerKey = $video['key'];
      break;
    }
  }
}

$title = (string)($movie['title'] ?? 'Untitled');
$year  = year_from_date((string)($movie['release_date'] ?? ''));
$posterUrl = tmdb_poster_url($movie['poster_path'] ?? null);

$pageTitle = 'Watch That Show • ' . ($title ?: 'Movie');

view('header', compact('pageTitle', 'active'));
?>

<main class="container">

  <p><a class="btn" href="index.php">← Back</a></p>

  <?php if ($error !== ''): ?>
    <div class="alert">Error: <?= e($error) ?></div>
  <?php endif; ?>

  <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap; text-align:left;">
    <div style="width:200px;">
      <div class="poster">
        <?php if ($posterUrl): ?>
          <img loading="lazy" src="<?= e($posterUrl) ?>" alt="<?= e($title) ?> poster">
        <?php else: ?>
          <div class="poster-fallback">No Image</div>
        <?php endif; ?>
      </div>
    </div>

    <div style="flex:1; min-width:260px;">
      <h1 style="margin:0 0 6px;">
        <?= e($title) ?> <?= $year ? '(' . e($year) . ')' : '' ?>
      </h1>

      <?php if (!empty($movie['tagline'])): ?>
        <div class="subtle" style="margin-bottom:10px;"><em><?= e((string)$movie['tagline']) ?></em></div>
      <?php endif; ?>

      <?php if (!empty($movie['overview'])): ?>
        <p><?= nl2br(e((string)$movie['overview'])) ?></p>
      <?php else: ?>
        <p class="subtle">No overview available.</p>
      <?php endif; ?>
      <div class="subtle" style="margin-top:12px;">
        <?php if (!empty($movie['release_date'])): ?>
          <div><strong>Release:</strong> <?= e((string)$movie['release_date']) ?></div>
        <?php endif; ?>
        <?php if (!empty($movie['runtime'])): ?>
          <div><strong>Runtime:</strong> <?= (int)$movie['runtime'] ?> min</div>
        <?php endif; ?>
        <?php if (!empty($movie['vote_average'])): ?>
          <div><strong>Rating:</strong> <?= e((string)$movie['vote_average']) ?> / 10</div>
        <?php endif; ?>
        <?php if ($trailerKey): ?>
          <div class="trailer">
            <h3>Watch Trailer</h3>
            <?php if ($trailerKey): ?>
              <div class="trailer" style="margin:14px 0;">
                <div class="section-title">Trailer</div>

                <div class="video-wrap">
                  <iframe
                    src="https://www.youtube.com/embed/<?= e($trailerKey) ?>"
                    title="YouTube trailer"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen>
                  </iframe>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="section-title" style="margin-top:18px;">Cast</div>

  <?php $topCast = array_slice($cast, 0, 18); ?>

  <?php if (empty($topCast)): ?>
    <p class="subtle">No cast found.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($topCast as $p): ?>
        <?php
        $pid = (int)($p['id'] ?? 0);
        $name = (string)($p['name'] ?? '');
        $character = (string)($p['character'] ?? '');
        $profileUrl = tmdb_profile_url($p['profile_path'] ?? null, 'w185');
        ?>
        <a class="card" href="person.php?id=<?= $pid ?>">
          <div class="poster">
            <?php if ($profileUrl): ?>
              <img loading="lazy" src="<?= e($profileUrl) ?>" alt="<?= e($name) ?>">
            <?php else: ?>
              <div class="poster-fallback">No Image</div>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="title"><?= e($name) ?></div>
            <div class="meta"><?= $character ? e($character) : '—' ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<?php view('footer'); ?>