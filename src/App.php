<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.06.2021
 * Time: 22:00
 * Made with <3 by West from TechGate Studio
 */


use Symfony\Component\VarDumper\VarDumper;

class App
{
    protected $dir = null;

    public function run($dir)
    {
        $this->dir = $dir;

        require_once $dir . '/vendor/autoload.php';
    }

    public static function dump($var)
    {
        return VarDumper::dump($var);
    }
}