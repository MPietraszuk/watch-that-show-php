<?php

declare(strict_types=1);

require_once __DIR__ . '/tmdb.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Missing/invalid movie id.');
}

$movie = tmdb_get("/movie/{$id}", [
  'append_to_response' => 'credits',
]);

if (isset($movie['error'])) {
  http_response_code(500);
  echo "<pre>" . htmlspecialchars(print_r($movie, true)) . "</pre>";
  exit;
}

$title = (string)($movie['title'] ?? 'Untitled');
$year  = !empty($movie['release_date']) ? substr((string)$movie['release_date'], 0, 4) : '';
$posterUrl = tmdb_img($movie['poster_path'] ?? null, 'w342');

$cast = array_slice(($movie['credits']['cast'] ?? []), 0, 12);
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?><?= $year ? " ({$year})" : "" ?></title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div class="container" style="text-align:left;">
    <p><a href="index.php">&larr; Back to search</a></p>

    <h1><?= htmlspecialchars($title) ?><?= $year ? " ({$year})" : "" ?></h1>

    <?php if ($posterUrl): ?>
      <img src="<?= htmlspecialchars($posterUrl) ?>" alt="<?= htmlspecialchars($title) ?>" style="width:220px;border-radius:6px;">
    <?php endif; ?>

    <?php if (!empty($movie['overview'])): ?>
      <p><?= nl2br(htmlspecialchars((string)$movie['overview'])) ?></p>
    <?php endif; ?>

    <h2>Cast</h2>
    <?php if (!$cast): ?>
      <p>No cast found.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($cast as $p): ?>
          <?php
          $pid  = (int)($p['id'] ?? 0);
          $name = (string)($p['name'] ?? '');
          $char = (string)($p['character'] ?? '');
          ?>
          <li>
            <a href="person.php?id=<?= $pid ?>">
              <?= htmlspecialchars($name) ?>
            </a>
            <?= $char ? " — " . htmlspecialchars($char) : "" ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</body>

</html>