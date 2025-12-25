<?php

declare(strict_types=1);

function config(string $key, string $default = ''): string
{
  $val = getenv($key);
  return ($val === false || $val === '') ? $default : $val;
}
