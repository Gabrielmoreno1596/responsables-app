<?php
// Carga tu env/config si ahí defines APP_ENV/DB/etc.
// Si no tienes un include central, al menos calcula BASE_PATH aquí.
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($script)), '/');
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath === '/' ? '' : $basePath);
}
header('Content-Type: text/html; charset=UTF-8');
