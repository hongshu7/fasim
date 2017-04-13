<?php
// Enable Composer autoloader
/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloader = require dirname(dirname(__DIR__)) . '/vendor/autoload.php';
// Register test classes
$autoloader->addPsr4('Fasim\\Tests\\', __DIR__);