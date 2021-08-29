<?php


namespace App\Template;


use App;
use App\Template\Loader\ClosureCompiler;
use Closure;

class Templater
{
    protected $path;
    protected $app;

    public function __construct($path, App $app)
    {
        $this->path = $path;
        $this->app = $app;

        @stream_filter_register('closure', ClosureCompiler::class);

    }

    /**
     * @param $templateName
     * @return Template
     */
    public function get($templateName)
    {
        $templatePath = sprintf('%s/%s.php', $this->path, $templateName);
        if (!file_exists($templatePath))
        {
            return null;
        }

        return $this->app->container()['templater.template']($templatePath);
    }

    public function compileToClosure($path): Closure
    {
        return require('templater.closure://' . $path);
    }
}
