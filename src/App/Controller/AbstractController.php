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
use App\PrintableException;
use PDO;

class AbstractController
{
    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function preAction(): void {}

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

    protected function exception(string $message, int $code): PrintableException
    {
        return new PrintableException($message, $code);
    }
    
    protected function getFromRequest(string $key): ?string
    {
        return $this->app()->getFromRequest($key);
    }

    protected function setHttpCode(int $code): void
    {
        $this->app()->setResponseHttpCode($code);
    }

    protected function setHeader(string $name, string $value): void
    {
        $this->app()->setHeader($name, $value);
    }
}