<?php

use Dotenv\Dotenv;

function load_env($path) {
  if (!file_exists($path) || !class_exists(Dotenv::class)) {
    return;
  }

  $dotenv = Dotenv::createImmutable(dirname($path));
  $dotenv->load();
}
