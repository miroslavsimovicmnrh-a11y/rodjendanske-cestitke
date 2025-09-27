<?php

use Dotenv\Dotenv;

function load_env($path)
{
    if (file_exists($path)) {
        $dotenv = Dotenv::createImmutable(dirname($path));
        $dotenv->load();
    }
}
