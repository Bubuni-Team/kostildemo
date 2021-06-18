<?php /** @noinspection PhpIncludeInspection */

/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.06.2021
 * Time: 22:00
 * Made with <3 by West from Bubuni Team
 */


use App\PrintableException;
use Symfony\Component\VarDumper\VarDumper;

class App
{
    public static $dir;
    protected static $app = null;
    protected static $config = [];

    public static function run($dir)
    {
        self::$dir = $dir;
        self::$app = new App();
        self::$config = require_once self::$dir . '/src/config.php';

        require_once $dir . '/vendor/autoload.php';

        self::$app->handleRequest();
    }

    private function handleRequest()
    {
        $controllerClass = 'App\Controller\\' . ucfirst(strtolower($_REQUEST['controller']));

        if (!class_exists($controllerClass))
        {
            $this->sendNotFound();
        }

        $controller = new $controllerClass(self::$app);
        $actionMethod = 'action' . ucfirst(strtolower($_REQUEST['action']));

        if (!is_callable([$controller, $actionMethod]))
        {
            $this->sendNotFound();
        }

        try {
            $body = $controller->$actionMethod();
            $this->sendResponse(200, $body);
        }
        catch (PrintableException $e)
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function sendResponse($httpCode, $body, $contentType = 'text/html')
    {
        header('Content-Type: ' . $contentType . '; charset=utf8', false, $httpCode);
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit();
    }

    public function renderTemplate($templateName, $params = [])
    {
        $templateFileName = $this->getTemplateFileName($templateName);
        if (!file_exists($templateFileName))
        {
            $this->sendNotFound();
        }

        ob_start();
        extract($params);
        require_once $templateFileName;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $pageContent = ob_get_clean();

        ob_start();
        extract(self::$config['system']);
        require_once $this->getTemplateFileName('PAGE_CONTAINER');

        return ob_get_clean();
    }

    public function getTemplateFileName($templateName): string
    {
        return self::$dir . '/templates/' . $templateName . '.php';
    }

    public function sendNotFound()
    {
        $this->sendError(404, 'Requested page not found');
    }

    public function sendError($httpCode, $errorText)
    {
        // TODO: make error html template
        $this->sendResponse($httpCode, $errorText);
    }
    
    public static function dump($var)
    {
        return VarDumper::dump($var);
    }
}