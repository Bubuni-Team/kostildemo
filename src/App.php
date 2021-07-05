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

    /** @var array  */
    protected $templateFileNameCache = [];

    /** @var array  */
    protected $dataRegistry = [];

    public static function setup(string $dir): App
    {
        self::$dir = $dir;
        self::$config = require_once self::$dir . '/src/config.php';
        require_once $dir . '/vendor/autoload.php';

        ignore_user_abort(true);
        @ini_set('output_buffering', '0');

        self::$app = $app = new App;

        $app->loadDataRegistry();

        return $app;
    }

    public function __construct()
    {
        $dbConfig = self::$config['db'];

        $this->db = new PDO(
            sprintf('mysql:dbname=%s;host=%s;port=%d', $dbConfig['dbname'], $dbConfig['host'], $dbConfig['port']),
            $dbConfig['user'],
            $dbConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    public function run(): void
    {
        $this->handleRequest();
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
        $templateFileName = $this->templateFileNameCache[$templateName] ?? null;
        if (!$templateFileName)
        {
            $styleName = self::$config['system']['style'] ?? 'default';
            $templateFileName = $this->formatTemplateFileName($styleName, $templateName);
            if (!file_exists($templateFileName))
            {
                // fallback
                $templateFileName = $this->formatTemplateFileName('default', $templateName);
            }

            $this->templateFileNameCache[$templateName] = $templateFileName;
        }

        return $templateFileName;

    }
    public function formatTemplateFileName(string $styleName, string $templateName): string
    {
        return sprintf(
            '%s/templates/%s/%s.php',
            self::$dir,
            $styleName,
            $templateName
        );
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

    public function db(): PDO
    {
        return $this->db;
    }

    public function config(): array
    {
        return self::$config;
    }

    public function dataRegistry(): array
    {
        return $this->dataRegistry;
    }

    public static function dump($var): void
    {
        VarDumper::dump($var);
    }

    protected function loadDataRegistry(): void
    {
        $db = $this->db();
        $dr = $db->query("SELECT * FROM `data_registry`", PDO::FETCH_ASSOC)->fetchAll();
        foreach ($dr as $data)
        {
            $this->dataRegistry[$data['data_key']] = json_decode($data['data_value'], true);
        }
    }
}