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

    public function preAction(): void
    {
        date_default_timezone_set($this->app->config()['system']['timezone']);
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

        if ($this->isPassedKey())
        {
            return;
        }

        $status = 403;
        if ($this->loggedUser() === -1)
        {
            $this->setHeader('Location', $this->app->buildUrl([
                'query' => http_build_query([
                    'controller' => 'account',
                    'action' => 'login'
                ])
            ]));
            $status = 302;
        }

        throw $this->exception('You don\'t have permissions to do that', $status);
    }

    protected function isPassedKey(): bool
    {
        return ($this->getFromRequest('key') === $this->app()->config()['system']['upgradeKey']);
    }

    /**
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return $this->app->isAdmin();
    }

    protected function loggedUser(): int
    {
        return $this->app->loggedUser();
    }

    /**
     * @param string $method
     * @return bool
     */
    protected function isHttpMethod(string $method): bool
    {
        return strtolower($method) === strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function forbidden(): string
    {
        $this->setHttpCode(403);
        return $this->template('pages/forbidden');
    }
}
