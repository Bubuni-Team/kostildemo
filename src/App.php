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
use App\Cookie\CookieManager;
use App\CookieSession;
use App\Data\InstallMapName;
use App\DataRegistry;
use App\PrintableException;
use Pimple\Container;
use Symfony\Component\VarDumper\VarDumper;
use App\Compression;
use App\Util\Arr;

class App
{
    /** @var Container */
    protected $container;

    /** @var string */
    public static $dir;

    /** @var App|null */
    protected static $app = null;

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

    public static function setup(string $dir): App
    {
        require_once $dir . '/vendor/autoload.php';

        $requirements = [
            'JSON' => 'json_encode',
            'PDO' => 'pdo_drivers'
        ];
        foreach ($requirements as $req => $fn)
        {
            if (!function_exists($fn))
            {
                die("$req extension is required.");
            }
        }

        if (!in_array('mysql', PDO::getAvailableDrivers()))
        {
            die('PDO MySQL driver is required');
        }

        self::$dir = $dir;
        $app = new App();

        ignore_user_abort(true);
        @ini_set('output_buffering', '0');
        @error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

        return self::$app = $app;
    }

    public function __construct()
    {
        $container = new Pimple\Container();
        $this->container = $container;
        $app = $this;

        $container['page_container.vars'] = function (Container $c): ArrayAccess
        {
            return new \ArrayObject($c['page_container.init_vars'],
                ArrayObject::ARRAY_AS_PROPS|ArrayObject::STD_PROP_LIST);
        };
        $container['page_container.init_vars'] = function (Container $c) use ($app): array
        {
            if ($this->isInstalled() && $this->getControllerName() !== 'Install')
            {
                $dataRegistry = $c['registry'];
            }
            else
            {
                $dataRegistry = new ArrayObject();
            }

            return [
                'config' => $c['config'],
                'headAdditionalCode' => [],
                'secondaryTitle' => '',
                'dataRegistry' => $dataRegistry
            ];
        };

        $container['config.default'] = function (Container $c): array
        {
            $configPath = $c['config.path'];
            $contents = file_exists($configPath) ?
                file_get_contents($configPath) :
                php_uname();
            $configHash = md5($contents);

            return [
                'db' => [
                    'host' => 'localhost', // 'database.local',
                    'port' => 3306,
                    'user' => 'autodemo',
                    'password' => 'autodemo',
                    'dbname' => 'autodemo'
                ],
                'system' => [
                    'cleanupCutOff' => 172800,
                    'cleanupCooldown' => 7200,
                    'chunkSize' => 'auto',
                    'configurePhpReporting' => true,

                    'siteName' => 'Demo System by Bubuni Team',
                    'triggerBasedCron' => true,
                    'cronKey' => '',
                    'timezone' => 'Europe/Moscow',
                    'style' => 'default',
                    'compressAlgo' => null, // Real name - "as_is"
                    'fileNameFormat' => '{ demo_id }.{ file_extension }',
                    'upgradeKey' => $configHash,
                    'administrators' => [],
                    'mapPresets' => [],
                    'cronRun' => 'activityBased'
                ],

                'cookie' => [
                    'prefix' => 'kostildemo_',
                    'path' => '/',
                    'domain' => ''
                ],

                'servers' => [],
                'mapNames' => [],
                'compressMap' => []
            ];
        };
        $container['config'] = function (Container $c): array
        {
            $path = $c['config.path'];
            $data = $c['config.default'];

            $data['config_exists'] = false;
            if (file_exists($path))
            {
                $data = Arr::mergeRecursive($data, require($path));
                $data['config_exists'] = true;
            }

            return $data;
        };
        $container['config.path'] = function (Container $c): string
        {
            return App::$dir . '/src/config.php';
        };

        $container['db'] = function (Container $c): PDO
        {
            $config = $c['config']['db'];

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
            $options += $config['pdo_options'] ?? [];

            return new PDO(
                sprintf('mysql:dbname=%s;host=%s;port=%d', $config['dbname'], $config['host'], $config['port']),
                $config['user'],
                $config['password'],
                $options
            );
        };
        $container['cookieManager'] = function (Container $c): CookieManager
        {
            $config = $c['config']['cookie'];
            return new CookieManager($config['prefix'], $config['path'], $config['domain'],
                $_COOKIE);
        };
        $container['registry'] = function () use ($app): DataRegistry
        {
            return new DataRegistry($app);
        };

        $container['mapNameDictionary'] = function (Container $c): array
        {
            $config = $c['config'];

            $presets = $config['system']['mapPresets'];
            $mapNames = $config['mapNames'];

            // First, we write own map names to array.
            $result = [];
            foreach ($presets as $presetName)
            {
                $content = InstallMapName::getDictionaryContent($presetName)['content'];
                if (empty($content))
                {
                    continue;
                }

                $result = array_merge($result, $content['content']);
            }

            // And finally we override all own map names - with user definitions and append them.
            return array_merge($result, $mapNames);
        };

        $container['compressMap'] = function (Container $c): array
        {
            $builtInMap = $c['compressMap.builtIn'];
            return array_merge($builtInMap, $c['config']['compressMap']);
        };
        $container['compressMap.builtIn'] = function (Container $c): array
        {
            return [
                'zip' => Compression\ZipCompressor::class,
                'bzip' => Compression\BzipCompressor::class,
                'gzip' => Compression\GzipCompressor::class,
                'as_is' => Compression\SimpleIoCompressor::class // also known as `null`
            ];
        };
    }

    public function run(): void
    {
        if ($this->isInstalled() && $this->getControllerName() !== 'Install')
        {
            $config = $this->config();
            $registry = $this->dataRegistry();

            if (time() > $registry['cleanupRunTime'] && !$this->isCleanupRequest() &&
                $config['system']['cronRun'] === 'activityBased')
            {
                $cleanupRunHash = sha1(uniqid());
                $registry['cleanupRunHash'] = $cleanupRunHash;

                $this->container['page_container.vars']['cronCleanup'] = sprintf(
                    '<meta name="job_key" content="%s" />', $cleanupRunHash);
            }
        }

        $this->setupSession();
        $this->handleRequest();
    }

    protected function setupSession(): void
    {
        $useBuiltinHandler = !$this->isInstalled() && (($this->config()['system']['useBuiltinSessionHandler'] ?? true));
        if ($useBuiltinHandler)
        {
            session_set_save_handler(new CookieSession($this));
        }

        session_start();
    }

    private function handleRequest(): void
    {
        $controllerClass = 'App\Controller\\' . $this->getControllerName();
        if (!class_exists($controllerClass))
        {
            $this->sendNotFound();
        }

        /** @var AbstractController $controller */
        $controller = new $controllerClass(self::$app);
        $actionMethod = 'action' . $this->getActionName();

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
            $this->sendError(intval($e->getCode()), $e->getMessage());
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

        session_write_close();

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

        /** @var ArrayObject $pageContainerVariables */
        $pageContainerVariables = $this->container['page_container.vars'];
        $currentContent = $pageContainerVariables->exchangeArray([]);
        $pageContainerVariables->exchangeArray(array_merge($currentContent, $params));

        return ob_get_clean();
    }

    public function getFinalResponse(string $controllerResponse): string
    {
        if ($this->responseContentType == 'text/html' && $this->includePageContainer)
        {
            return $this->renderTemplate('PAGE_CONTAINER', array_merge([
                'pageContent' => $controllerResponse,
                'options' => $this->config()['system']
            ], $this->container['page_container.vars']->getArrayCopy()));
        }

        return $controllerResponse;
    }

    public function getTemplateFileName(string $templateName): string
    {
        $templateFileName = $this->templateFileNameCache[$templateName] ?? null;
        if (!$templateFileName)
        {
            $templateFileName = $this->formatTemplateFileName($this->config()['system']['style'], $templateName);
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

    public function isCleanupRequest(): bool
    {
        return $this->getControllerName() == 'Demo'
            && $this->getActionName() == 'Cleanup';
    }

    public function getControllerName(): string
    {
        $controllerName = $this->getFromRequest('controller') ?: 'demo';
        return ucfirst(strtolower($controllerName));
    }

    public function getActionName(): string
    {
        $actionName = $this->getFromRequest('action') ?: 'index';
        return ucfirst(strtolower($actionName));
    }

    public function db(): PDO
    {
        return $this->container['db'];
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
                'path' => $_SERVER['SCRIPT_NAME']
            ];

            $url = $this->buildUrl([
                'scheme' => $urlParts['scheme'] ?? '',
                'host' => $urlParts['host'] ?? '',
                'path' => $urlParts['path'] ?? ''
            ]);
        }

        return $url;
    }

    /**
     * @see https://www.php.net/manual/en/function.parse-url.php#106731
     *
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
        return $this->container['config'];
    }

    public function dataRegistry(): DataRegistry
    {
        return $this->container['registry'];
    }

    /**
     * @psalm-suppress UndefinedClass
     * @psalm-suppress ForbiddenCode
     * @param mixed $var
     */
    public static function dump($var): void
    {
        if (class_exists(VarDumper::class))
        {
            VarDumper::dump($var);
            return;
        }

        \var_dump($var);
    }

    public function cookieManager(): CookieManager
    {
        return $this->container['cookieManager'];
    }

    /**
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    public static function app(): App
    {
        if (!self::$app)
        {
            self::setup(dirname(__DIR__));
        }

        return self::$app;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function isInstalled(): bool
    {
        $success = true;
        if ($_SESSION['is_installer_session'] ?? false)
        {
            return false;
        }

        try
        {
            $this->db();
        }
        catch (\PDOException $e)
        {
            $success = false;
        }

        return $success;
    }

    public function isAdmin(): bool
    {
        $administrators = $this->config()['system']['administrators'];
        return in_array($this->loggedUser(), $administrators);
    }

    /**
     * @return int
     */
    public function loggedUser(): int
    {
        if (@session_status() !== PHP_SESSION_ACTIVE)
        {
            @session_start();
        }

        return $_SESSION['steam_id'] ?? -1;
    }
}
