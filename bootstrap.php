<?php 
error_reporting(E_ALL);
ini_set("display_errors", 1);

define("SERVER_PATH", __DIR__); 

require_once(SERVER_PATH . '/vendor/autoload.php');
require_once(SERVER_PATH . '/config.php');

$loader = new \Twig\Loader\FilesystemLoader(SERVER_PATH.'/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => '/tmp/twig_cache',
]);

