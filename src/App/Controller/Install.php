<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Controller;


use App;
use App\Data\InstallMapName;
use App\Data\Migration;
use App\Migration\AbstractMigration;
use Kruzya\SteamIdConverter\Exception\InvalidSteamIdException;
use Kruzya\SteamIdConverter\SteamID;
use PDO;
use Throwable;
use function array_merge;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function var_export;

class Install extends AbstractController
{
    public function preAction(): void
    {
        if ($this->isInstalled())
        {
            $this->assertIsAdmin();
        }
    }

    public function actionIndex(): string
    {
        if ($this->isInstalled())
        {
            return $this->forbidden();
        }

        $_SESSION['is_installer_session'] = true;
        if ($this->isHttpMethod('POST'))
        {
            return $this->handleInstallationRequest();
        }

        return $this->template('install/index', [
            'secondaryTitle' => 'Install'
        ]);
    }

    public function actionMigrate(): string
    {
        if (!$this->isInstalled())
        {
            throw $this->exception('System isn\'t installed', 400);
        }

        if (!$this->canRunMigrations())
        {
            $this->setHeader('Location', '?controller=account&action=login');
            return '';
        }

        if ($this->runMigrations())
        {
            $this->setHeader('Location', '?controller=demo');
            return '';
        }

        return 'FALSE';
    }

    public function canRunMigrations(): bool
    {
        // If user has key in request from config - then it can be redirected from Installer.
        if ($this->getFromRequest('key') === $this->app()->config()['system']['upgradeKey'])
        {
            return true;
        }

        // Else just verify user identifier in our array.
        return $this->isAdmin();
    }

    public function isInstalled(): bool
    {
        return $this->app()->isInstalled();
    }

    protected function handleInstallationRequest(): string
    {
        $installData = @json_decode(file_get_contents('php://input'), true);
        $jsonErrorCode = json_last_error();
        if ($jsonErrorCode !== JSON_ERROR_NONE)
        {
            return $this->errorJson(
                -1,
                sprintf('[%d] %s', $jsonErrorCode, json_last_error_msg()),
                ['step' => 'handle_request', 'user_friendly_msg' => 'Не удается прочитать команду от клиента установки']
            );
        }

        switch ($installData['command'])
        {
            case 'verify_database_credentials':
            {
                $credentials = $installData['credentials'];
                $dsn = sprintf('mysql:dbname=%s;host=%s;port=%d', $credentials['dbname'],
                    $credentials['host'], $credentials['port']);
                $user = $credentials['user'];
                $passwd = $credentials['password'];

                // Стейджи подключения. Для более простого контроля, где произошёл обсёр именно.
                // 0 - подключение
                // 1 - проверка инсталляции
                // 2 - проверка инсталляции демок конкретно
                // -1 - "уже есть установка"
                // -2 - "всё ок"
                $stage = 0;
                try
                {
                    $db = new PDO($dsn, $user, $passwd, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    $stage = 1;
                    $db->query('SELECT * FROM migration');

                    $stage = 2;
                    $db->query('SELECT * FROM record');

                    $stage = -1;
                }
                catch (\PDOException $e)
                {
                    if (!in_array($stage, [1, 2]))
                    {
                        $messages = [
                            0 => 'Не удаётся подключиться к БД: ' . $e->getMessage(),
                            -1 => 'В этой базе уже есть установка этого веб-скрипта'
                        ];
                        return $this->errorJson(
                            1, $e->getMessage(),
                            ['step' => 'db_credentials', 'user_friendly_msg' => $messages[$stage]]
                        );
                    }
                }

                return $this->successJson(['step' => 'db_credentials']);
                break;
            }

            // Этот шаг должен быть вызван только если подключение к БД проверено успешно.
            case 'run':
            {
                // Для начала запишем конфиг.
                $configuration = [
                    'db' => $installData['db'],
                    'system' => array_merge($installData['system'], [
                        'upgradeKey' => md5(uniqid('demoRecordUpgrader.'))
                    ]),
                    'servers' => array_filter($installData['servers'], function (array $el)
                    {
                        return (!empty($el['name']));
                    }),
                    'mapNames' => $installData['mapNames']
                ];

                // Переобойдём администраторов в системе.
                $administrators = [];
                foreach ($installData['administrators'] as $adminRow)
                {
                    try
                    {
                        $administrators[] = (new SteamID($adminRow['value']))->accountId();
                    }
                    catch (InvalidSteamIdException $e)
                    {
                        // Suppress.
                    }
                }
                $configuration['system']['administrators'] = $administrators;

                // Попробуем записать конфиг.
                try
                {
                    file_put_contents(App::$dir . '/src/config.php', '<?php return ' . var_export($configuration, true) . ';');
                }
                catch (Throwable $e)
                {
                    // Похоже, прав на запись в файл нет.
                    return $this->errorJson(2, $e->getMessage(),
                        ['step' => 'config_write', 'user_friendly_msg' => 'Не удаётся записать данные в конфиг']);
                }

                // Теперь отправим юзера запускать миграции.
                return $this->successJson(['redirect' => '?controller=install&action=migrate&key=' . $configuration['system']['upgradeKey']]);
            }
        }

        return $this->errorJson(0, 'unknown state');
    }

    protected function errorJson(int $errorCode, string $errorMessage, array $additionalAttributes = []): string
    {
        return $this->json(array_merge([
            'success' => false,
            'error' => [
                'msg' => $errorMessage,
                'code' => $errorCode
            ]
        ], $additionalAttributes));
    }

    protected function successJson(array $additionalAttributes = []): string
    {
        return $this->json(array_merge([
            'success' => true,
        ], $additionalAttributes));
    }

    protected function runMigrations(): bool
    {
        $db = $this->db();
        $migrationClasses = Migration::get();

        $ranMigrations = [];
        try {
            $ranMigrationsStatement = $db->query('SELECT `name` FROM `migration`');
            while ($col = $ranMigrationsStatement->fetchColumn(0)) {
                $ranMigrations[] = $col;
            }
        } catch (\PDOException $e) {
            $db->query("
                CREATE TABLE `migration` (
                    `name` VARCHAR(128) NOT NULL COLLATE 'utf8_general_ci',
                    `runned_at` INT(10) UNSIGNED NOT NULL,
                    PRIMARY KEY (`name`) USING BTREE
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
            ");
        }

        $migrationsForRun = array_diff($migrationClasses, $ranMigrations);
        if (count($migrationsForRun) == 0)
        {
            return true;
        }

        $insertMigrationStmt = $db->prepare('INSERT INTO `migration` VALUES(?, ?)');
        foreach ($migrationsForRun as $migration)
        {
            /** @var AbstractMigration $migrationHandler */
            $migrationHandler = new $migration($db);

            try
            {
                $db->beginTransaction();
                $migrationHandler->up();
                $insertMigrationStmt->execute([$migration, time()]);
                $db->commit();
            }
            catch (\PDOException $e)
            {
                while ($db->inTransaction()) $db->rollBack();
                return false;
            }
        }
        return true;
    }
}
