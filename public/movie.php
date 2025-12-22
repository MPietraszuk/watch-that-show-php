<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
  http_response_code(400);
  $pageTitle = 'Movie DB • Error';
  $active = '';
  view('header', compact('pageTitle', 'active'));
?>
  <main class="container">
    <div class="alert">Missing or invalid movie id.</div>
    <a class="btn" href="index.php">← Back</a>
  </main>
<?php
  view('footer');
  exit;
}

$error = '';
$movie = [];

try {
  $movie = tmdb_get("/movie/{$id}", [
    'append_to_response' => 'credits,videos',
  ], 900);
} catch (Throwable $e) {
  $error = $e->getMessage();
}

$title = (string)($movie['title'] ?? 'Movie');
$pageTitle = $title . ' • Movie';
$active = '';
view('header', compact('pageTitle', 'active'));

if ($error !== ''): ?>
  <main class="container">
    <div class="alert">Error: <?= e($error) ?></div>
    <a class="btn" href="index.php">← Back</a>
  </main>
  <?php view('footer');
  exit; ?>
<?php endif; ?>

<?php
$releaseDate = (string)($movie['release_date'] ?? '');
$year = year_from_date($releaseDate);
$runtime = isset($movie['runtime']) ? (int)$movie['runtime'] : null;
$rating = isset($movie['vote_average']) ? (float)$movie['vote_average'] : null;
$overview = (string)($movie['overview'] ?? '');

$posterUrl = tmdb_poster_url($movie['poster_path'] ?? null);
$backdropUrl = tmdb_backdrop_url($movie['backdrop_path'] ?? null);

// Genres
$genreNames = [];
$genres = $movie['genres'] ?? [];
if (is_array($genres)) {
  foreach ($genres as $g) {
    if (!empty($g['name'])) $genreNames[] = (string)$g['name'];
  }
}

// Cast (top 10)
$cast = $movie['credits']['cast'] ?? [];
$cast = is_array($cast) ? array_slice($cast, 0, 10) : [];

// Trailer (YouTube)
$trailerKey = '';
$videos = $movie['videos']['results'] ?? [];
if (is_array($videos)) {
  foreach ($videos as $v) {
    if (($v['site'] ?? '') === 'YouTube' && ($v['type'] ?? '') === 'Trailer' && !empty($v['key'])) {
      $trailerKey = (string)$v['key'];
      break;
    }
  }
}
?>

<header class="hero" style="<?= $backdropUrl ? 'background-image:url(' . e($backdropUrl) . ');' : '' ?>">
  <div class="overlay">
    <div class="container">
      <a class="subtle" href="index.php">← Back to search</a>
      <h1><?= e($title) ?> <?= $year ? '<span class="subtle">(' . e($year) . ')</span>' : '' ?></h1>

      <div class="meta-row">
        <?php if ($releaseDate): ?><span><?= e($releaseDate) ?></span><?php endif; ?>
        <span>• <?= e(minutes_to_runtime($runtime)) ?></span>
        <?php if ($rating !== null): ?><span>• ⭐ <?= e(number_format($rating, 1)) ?>/10</span><?php endif; ?>
      </div>

      <?php if (!empty($genreNames)): ?>
        <div class="chips">
          <?php foreach ($genreNames as $gn): ?>
            <span class="chip"><?= e($gn) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container">
  <div class="detail">
    <aside>
      <div class="poster">
        <?php if ($posterUrl): ?>
          <img src="<?= e($posterUrl) ?>" alt="<?= e($title) ?> poster">
        <?php else: ?>
          <div class="poster-fallback">No Image</div>
        <?php endif; ?>
      </div>

      <?php if ($trailerKey): ?>
        <div style="margin-top:10px">
          <a class="btn" target="_blank" rel="noopener"
            href="https://www.youtube.com/watch?v=<?= e($trailerKey) ?>">
            Watch Trailer
          </a>
        </div>
      <?php endif; ?>
    </aside>

    <section>
      <h2 class="section-title">Synopsis</h2>
      <?php if ($overview): ?>
        <p><?= e($overview) ?></p>
      <?php else: ?>
        <p class="subtle">No synopsis available.</p>
      <?php endif; ?>

      <?php if (!empty($cast)): ?>
        <h2 class="section-title">Top Cast</h2>
        <ul class="cast">
          <?php foreach ($cast as $p): ?>
            <li>
              <div class="cast-name"><?= e((string)($p['name'] ?? '')) ?></div>
              <div class="subtle"><?= e((string)($p['character'] ?? '')) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</main>

<?php view('footer'); ?>