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
        return $this->app()->renderTemplate($templateName, $params);
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

    /**
     * @throws PrintableException
     */
    protected function assertIsAdmin(): void
    {
        if ($this->isAdmin())
        {
            return;
        }

        $status = 403;
        if ($this->loggedUser() === -1)
        {
            $this->setHeader('Location', $this->app->buildUrl([
                'path' => './',
                'query' => http_build_query([
                    'controller' => 'account',
                    'action' => 'login'
                ])
            ]));
            $status = 302;
        }

        throw $this->exception('You don\'t have permissions to do that', $status);
    }

    /**
     * @return bool
     */
    protected function isAdmin()
    {
        $administrators = $this->app->config()['system']['administrators'] ?? [];
        return in_array($this->loggedUser(), $administrators);
    }

    /**
     * @return int
     */
    protected function loggedUser()
    {
        if (@session_status() !== PHP_SESSION_ACTIVE)
        {
            @session_start();
        }

        return $_SESSION['steam_id'] ?? -1;
    }
}