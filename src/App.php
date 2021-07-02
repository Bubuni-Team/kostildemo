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

class App
{
    /** @var string */
    public static $dir;

    /** @var App|null */
    protected static $app = null;

    /** @var array */
    protected static $config = [];

    /** @var PDO */
    protected $db;

    /** @var int */
    protected $responseHttpCode = 200;

    /** @var string */
    protected $responseContentType = 'text/html';

    /** @var array */
    protected $responseHeaders = [];

    /** @var bool */
    protected $includePageContainer = true;

    public static function run(string $dir): void
    {
        self::$dir = $dir;
        self::$config = require_once self::$dir . '/src/config.php';
        require_once $dir . '/vendor/autoload.php';

        self::$app = new App();

        self::$app->handleRequest();
    }

    public function __construct()
    {
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
            $body = $this->getFinalResponse($controller->$actionMethod());
            $this->sendResponse($this->responseHttpCode, $body, $this->responseContentType);
        }
        catch (PrintableException $e)
        {
            $this->sendError((int) $e->getCode(), $e->getMessage());
        }
    }

    public function sendResponse(int $httpCode, string $body, string $contentType = 'text/html'): void
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
     * @return string
     */
    public function renderTemplate(string $templateName, array $params = []): string
    {
        $templateFileName = $this->getTemplateFileName($templateName);
        if (!file_exists($templateFileName))
        {
            $this->sendNotFound();
        }

        ob_start();
        extract($params);
        require $templateFileName;

        return ob_get_clean();
    }

    public function getFinalResponse(string $controllerResponse): string
    {
        if ($this->responseContentType == 'text/html' && $this->includePageContainer)
        {
            return $this->renderTemplate('PAGE_CONTAINER', [
                'pageContent' => $controllerResponse,
                'options' => self::$config['system']
            ]);
        }

        return $controllerResponse;
    }

    public function getTemplateFileName(string $templateName): string
    {
        return self::$dir . '/templates/' . $templateName . '.php';
    }

    public function sendNotFound(): void
    {
        $this->sendError(404, 'Requested page not found');
    }

    public function sendError(int $httpCode, string $errorText): void
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

    public function db(): ?PDO
    {
        // Lazy database connection (for installer).
        if (!$this->db)
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

        return $this->db;
    }

    /**
     * @return string
     */
    public function publicUrl(): string
    {
        $url = $this->config()['system']['url'] ?? '';
        if (empty($url))
        {
            $urlParts = $_SERVER['HTTP_REFERER'] ? parse_url($_SERVER['HTTP_REFERER']) : [
                'scheme' => !empty($_SERVER['HTTPS']) ? 'https' : 'http',
                'host' => $_SERVER['HTTP_HOST'],
                'path' => $_SERVER['REQUEST_URI']
            ];

            $url = $this->buildUrl([
                'scheme' => $urlParts['scheme'],
                'host' => $urlParts['host'],
                'path' => $urlParts['path']
            ]);
        }

        return $url;
    }

    /**
     * @param array $parts
     * @return string
     */
    public function buildUrl(array $parts = []): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = isset($parts['user']) ? $parts['user'] : '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public function config(): array
    {
        return self::$config;
    }
}
