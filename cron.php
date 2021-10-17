<?php

$phpVersion = phpversion();
if (version_compare($phpVersion, '7.1.0', '<'))
{
    die("PHP 7.1.0 or newer is required. $phpVersion does not meet this requirement. Please ask your host to upgrade PHP.");
}

if (PHP_SAPI !== 'cli')
{
    die('This script can only be run via the command line interface.');
}

$dir = __DIR__;
require_once $dir . '/src/App.php';
$app = App::setup($dir);

\App\Util\Demo::cleanup($app);
