<?php

declare(strict_types=1);

/**
 * Escape HTML safely
 */
function e(?string $value): string
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Extract year from YYYY-MM-DD
 */
function year_from_date(?string $date): string
{
  if (!$date) return '';
  return substr($date, 0, 4);
}
