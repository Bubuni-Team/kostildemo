<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 18.06.2021
 * Time: 19:41
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


use App;

class AbstractController
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    protected function app(): App
    {
        return $this->app;
    }

    protected function template($templateName, $params = [])
    {
        return $this->app()->renderTemplate($templateName, $params);
    }
}