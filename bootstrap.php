<?php 
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once(__DIR__ . '/lib/config.php');

// Twig-Template-Engine
require_once(SERVER_PATH . '/vendor/autoload.php');
$loader = new \Twig\Loader\FilesystemLoader(SERVER_PATH.'/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => '/tmp/twig_cache',
]);

