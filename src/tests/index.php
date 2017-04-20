<?php
define('APP_PATH', dirname(__file__) . DIRECTORY_SEPARATOR);
require_once './bootstrap.php';

use Fasim\Core\Application;

Application::getInstance()->run();