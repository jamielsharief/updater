<?php
/**
 * Configure PATH Constants and setup the application
 * namespace so that integration testing can work
 */

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/src');

/**
 * Work with copy to bin and in phar files. Only use working directory
 * if local copy not exists since this will cause problems with PHAR
 */
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
} elseif (file_exists(getcwd() . '/vendor/autoload.php')) {
    require getcwd() . '/vendor/autoload.php';
}

use Origin\Core\Config;
use Origin\Console\ErrorHandler;

(new ErrorHandler())->register();

Config::write('App.namespace', 'Updater');
Config::write('App.debug', false);
