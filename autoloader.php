<?php
require_once 'vendor/Symfony/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Thekot' => __DIR__,
    'Bcserv\SourcepawnIncParser' => __DIR__ . '/vendor',
));
$loader->register();
