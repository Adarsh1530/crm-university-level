<?php
require_once __DIR__ . '/../config/helpers.php';
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
redirect('leads/index.php' . $qs);
