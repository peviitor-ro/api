<?php
header('Content-Type: image/svg+xml');

$svgPath = __DIR__ . '/no-logo.svg';
readfile($svgPath);
