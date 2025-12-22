<?php

declare(strict_types=1);

/**
 * Expected variables:
 * - $pageTitle (string)
 * - $active (string): search | trending | popular
 */
$pageTitle = $pageTitle ?? 'Movie DB';
$active = $active ?? '';

function nav_active(string $key, string $active): string
{
  return $key === $active ? 'active' : '';
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" type="text/css" href="//code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" />
</head>

<body>

  <header class="nav">
    <div class="container">
      <div class="row">
        <a class="brand" href="index.php">Watch That Show</a>
        <nav class="links">
          <a class="<?= nav_active('search', $active) ?>" href="index.php">Search</a>
          <a class="<?= nav_active('trending', $active) ?>" href="trending.php">Trending</a>
          <a class="<?= nav_active('popular', $active) ?>" href="popular.php">Popular</a>
        </nav>
      </div>
      <p class="subtle">Vanilla PHP • TMDB API • No database</p>
    </div>
  </header>