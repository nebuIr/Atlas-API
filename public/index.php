<?php
$params = explode('/', strtok($_SERVER["REQUEST_URI"], '?'));

if (array_key_exists(1, $params)) {
    $section = $params;
} else {
    $section = '';
}

include __DIR__ . '/routes.php';