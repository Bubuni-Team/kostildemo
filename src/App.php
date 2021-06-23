<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.06.2021
 * Time: 22:00
 * Made with <3 by West from Bubuni Team
 */


use App\Controller\AbstractController;
use App\PrintableException;
use Symfony\Component\VarDumper\VarDumper;

class App
{
    public static $dir;
    protected static $app = null;
    protected static $config = [];

    /** @var PDO */
    protected $db;

    protected $responseHttpCode = 200;
    protected $responseContentType = 'text/html';
    protected $responseHeaders = [];

    public static function run($dir): void
    {
        self::$dir = $dir;
        self::$app = new App();
        self::$config = require_once self::$dir . '/src/config.php';

        require_once $dir . '/vendor/autoload.php';

        self::$app->setup();
        self::$app->handleRequest();
    }

    public function setup(): void
    {
        $dbConfig = self::$config['db'];

        $this->db = new PDO(
            sprintf('mysql:dbname=%s;host=%s;port=%d', $dbConfig['dbname'], $dbConfig['host'], $dbConfig['port']),
            $dbConfig['user'],
            $dbConfig['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    private function handleRequest(): void
    {
        $controllerName = $this->getFromRequest('controller') ?: 'demo';
        $controllerClass = 'App\Controller\\' . ucfirst(strtolower($controllerName));

        if (!class_exists($controllerClass))
        {
            $this->sendNotFound();
        }

        /** @var AbstractController $controller */
        $controller = new $controllerClass(self::$app);
        $actionName = $this->getFromRequest('action') ?: 'index';
        $actionMethod = 'action' . ucfirst(strtolower($actionName));

        if (!is_callable([$controller, $actionMethod]))
        {
            $this->sendNotFound();
        }

        try {
            $controller->preAction();
            $body = $controller->$actionMethod();
            $this->sendResponse($this->responseHttpCode, $body, $this->responseContentType);
        }
        catch (PrintableException $e)
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function sendResponse($httpCode, $body, $contentType = 'text/html'): void
    {
        header('Content-Type: ' . $contentType . '; charset=utf8', false, $httpCode);
        header('Content-Length: ' . strlen($body));
        foreach ($this->responseHeaders as $name => $value)
        {
            header(sprintf('%s: %s', $name, $value));
        }
        echo $body;
        exit();
    }

    /**
     * @param string $templateName
     * @param array $params
     * @param bool $container
     * @return false|string
     *
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function renderTemplate(string $templateName, array $params = [], bool $container = false): string
    {
        $templateFileName = $this->getTemplateFileName($templateName);
        if (!file_exists($templateFileName))
        {
            $this->sendNotFound();
        }

        ob_start();
        extract($params);
        require_once $templateFileName;

        if ($container)
        {
            $pageContent = ob_get_clean();
            ob_start();
            $options = self::$config['system'];
            require_once $this->getTemplateFileName('PAGE_CONTAINER');
        }

        return ob_get_clean();
    }

    public function getTemplateFileName($templateName): string
    {
        return self::$dir . '/templates/' . $templateName . '.php';
    }

    public function sendNotFound(): void
    {
        $this->sendError(404, 'Requested page not found');
    }

    public function sendError($httpCode, $errorText): void
    {
        // TODO: make error html template
        $this->sendResponse($httpCode, $errorText);
    }

    public function setResponseContentType(string $contentType): void
    {
        $this->responseContentType = $contentType;
    }

    public function setResponseHttpCode(int $code): void
    {
        $this->responseHttpCode = $code;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->responseHeaders[$name] = $value;
    }

    public function getFromRequest(string $key): ?string
    {
        return $_REQUEST[$key] ?? null;
    }

    public function db(): PDO
    {
        return $this->db;
    }

    public function config(): array
    {
        return self::$config;
    }
}