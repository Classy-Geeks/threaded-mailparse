<?php
/**
 * Copyright 2015 Classy Geeks llc. All Rights Reserved
 * http://classygeeks.com
 * MIT License:
 * http://opensource.org/licenses/MIT
 */

/**
 * Path defines
 */
define('PATH_ROOT', realpath(dirname(__FILE__)));

/**
 * Defines
 */
define('DS', DIRECTORY_SEPARATOR);
define('EOL', PHP_EOL);

/**
 * Autoloader
 */
require(PATH_ROOT . DS . 'vendor' . DS . 'autoload.php');

/**
 * Run parser app
 */
$app = new \App\App\ParserApp();
$app->run();