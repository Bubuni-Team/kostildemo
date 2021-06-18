<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.06.2021
 * Time: 21:23
 * Made with <3 by West from TechGate Studio
 */

$dir = __DIR__;

require_once $dir . '/src/App.php';

$app = new \App();
$app->run($dir);