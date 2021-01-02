<?php
/**
 * Configure PATH Constants and setup the application
 * namespace so that integration testing can work
 */

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/src');
define('DS', DIRECTORY_SEPARATOR); // Required for debugging

require ROOT . '/vendor/autoload.php';

use Origin\Core\Config;
use Origin\Console\ErrorHandler;

(new ErrorHandler())->register();

Config::write('App.namespace', 'Updater');
Config::write('App.debug', false);
