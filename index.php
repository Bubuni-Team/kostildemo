<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.06.2021
 * Time: 21:23
 * Made with <3 by West from TechGate Studio
 */

$phpVersion = phpversion();
if (version_compare($phpVersion, '7.1.0', '<'))
{
    die("PHP 7.1.0 or newer is required. $phpVersion does not meet this requirement. Please ask your host to upgrade PHP.");
}

$requirements = [
    'JSON' => 'json_encode',
    'PDO' => 'pdo_drivers'
];
foreach ($requirements as $req => $fn)
{
    if (!function_exists($fn))
    {
        die("$req extension is required.");
    }
}

if (!in_array('mysql', PDO::getAvailableDrivers()))
{
    die('PDO MySQL driver is required');
}

$dir = __DIR__;
require_once $dir . '/src/App.php';
App::setup($dir)->run();