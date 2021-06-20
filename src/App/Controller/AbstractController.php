<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 18.06.2021
 * Time: 19:41
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


use App;
use PDO;

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

    protected function db(): PDO
    {
        return $this->app()->db();
    }

    protected function json(array $data): string
    {
        $this->app()->setResponseContentType('application/json');
        return json_encode($data);
    }

    protected function template(string $templateName, array $params = []): string
    {
        return $this->app()->renderTemplate($templateName, $params, true);
    }
}