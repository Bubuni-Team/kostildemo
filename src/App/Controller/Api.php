<?php /** @noinspection PhpUnused */
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 20.06.2021
 * Time: 15:15
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


use App;
use App\Compression\AbstractCompressor;
use App\PrintableException;
use PDOStatement;
use function file_exists;
use function json_encode;
use function unlink;

class Api extends AbstractController
{
    /**
     * @throws PrintableException
     */
    public function preAction(): void
    {
        $key = $this->getFromRequest('key');
        if (!$key)
        {
            throw $this->exception('API key must be provided in request', 400);
        }

        if ($this->getServerIdByKey($key) === null)
        {
            throw $this->exception('Invalid key', 400);
        }
    }

    public function actionConfig(): string
    {
        return $this->json([
            'chunkSize' => $this->getChunkSize()
        ]);
    }

    /**
     * @throws PrintableException
     */
    public function actionUpload(): string
    {
        $demoId = $this->getFromRequest('demo_id');
        $chunkId = $this->getFromRequest('chunk_id');
        if (!$demoId || $chunkId === null)
        {
            throw $this->exception('Required parameter is missing.', 400);
        }

        $chunkDirectory = \App\Util\Demo::getChunkDirByDemoId($demoId);
        if (!file_exists($chunkDirectory))
        {
            mkdir($chunkDirectory, 0777, true);
        }

        $chunkFileName = sprintf('%s/%d', $chunkDirectory, (int) $chunkId);
        file_put_contents($chunkFileName, file_get_contents('php://input'));

        $this->setHttpCode(201);
        return $this->json([
            'status' => 0
        ]);
    }

    /**
     * @throws PrintableException
     */
    public function actionFinish(): string
    {
        $key = $this->getFromRequest('key');
        if (!$key)
        {
            throw $this->exception('API key must be provided in request', 400);
        }

        $serverId = $this->getServerIdByKey($key);
        $demoData = @json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw $this->exception(json_last_error_msg(), 400);
        }
        $demoId = $demoData['unique_id'];

        $chunkDir = \App\Util\Demo::getChunkDirByDemoId($demoId);
        $chunkList = array_diff(scandir($chunkDir), ['.', '..']);
        sort($chunkList);

        $recordPath = \App\Util\Demo::getLocalPath($demoId, 'as_is', []);
        $demoFd = fopen($recordPath, 'wb');
        try
        {
            foreach ($chunkList as $chunkName)
            {
                $chunkFileName = $chunkDir . '/' . $chunkName;
                $chunkFd = fopen($chunkFileName, 'rb');
                do {
                    $writeRes = fwrite($demoFd, fread($chunkFd, 4096));
                    if ($writeRes === false)
                    {
                        fclose($chunkFd);
                        throw $this->exception('An error occured. Enable debug to see details.', 500);
                    }
                } while (!feof($chunkFd));

                unlink($chunkFileName);
                fclose($chunkFd);
            }
        }
        finally
        {
            fclose($demoFd);
        }
        rmdir($chunkDir);

        // Try to perform compressing operation.
        $compressAlgo = $this->app()->config()['system']['compressAlgo'] ?? 'as_is';
        $compressData = [];
        if ($compressAlgo !== 'as_is')
        {
            $compressor = App\Util\Compress::getCompressor($compressAlgo);
            if ($compressor instanceof AbstractCompressor)
            {
                $targetPath = sprintf('%s/data/demos/%s', App::$dir,
                    $compressor->buildRelativePath($demoId, []));
                $compressData = $compressor->compress($recordPath, $targetPath);

                if ($compressData === null)
                {
                    if (@file_exists($targetPath)) @unlink($targetPath);

                    $compressData = [];
                    $compressAlgo = 'as_is';
                }
                else
                {
                    @unlink($recordPath);
                }
            }
        }

        $db = $this->db();

        $db->beginTransaction();
        $demoInsertStmt = $db->prepare(
            'INSERT INTO `record` 
                        (`demo_id`, `server_id`, `map`, `uploaded_at`, `started_at`, `finished_at`, `algo`, `algo_data`) 
                 VALUES (:demoId, :serverId, :map, :uploadedAt, :startedAt, :finishedAt, :algo, :algoData)'
        );

        $this->bulkBindValue($demoInsertStmt, [
            ':demoId' => $demoId,
            ':serverId' => $serverId,
            ':map' => str_replace('\\', '/', $demoData['play_map']),
            ':uploadedAt' => time(),
            ':startedAt' => $demoData['start_time'],
            ':finishedAt' => $demoData['end_time'],
            ':algo' => $compressAlgo,
            ':algoData' => json_encode($compressData)
        ]);

        $demoInsertStmt->execute();
        $recordId = $db->lastInsertId();

        $playerInsertStmt = $db->prepare('INSERT INTO `record_player` 
                    (record_id, account_id, username) 
             VALUES (:recordId, :accountId, :username)');

        $playerInsertStmt->bindValue(':recordId', $recordId);
        foreach ($demoData['players'] as $player)
        {
            $playerInsertStmt->bindValue(':accountId', $player['account_id']);
            $playerInsertStmt->bindValue(':username', $player['name']);
            $playerInsertStmt->execute();
        }
        $db->commit();

        $this->setHttpCode(201);
        return $this->json($chunkList);
    }

    protected function bulkBindValue(PDOStatement $statement, array $data): void
    {
        foreach ($data as $key => $value)
        {
            $statement->bindValue($key, $value);
        }
    }

    protected function getChunkSize(): int
    {
        $config = $this->app()->config()['system']['chunkSize'] ?? 'auto';
        if (is_int($config))
        {
            return $config;
        }

        if ($config === 'auto')
        {
            // This method is very complicated.
            // We're trying to get all possible limitations and select the strongest (lowest) value.
            // This should work best.
            $values = [];

            // We're trying to detect CloudFlare.
            // The dumbest method, but should work.
            foreach (['CF_REQUEST_ID', 'CF_CONNECTING_IP', 'CF_VISITOR', 'CF_RAY', 'CF_IPCOUNTRY'] as $header)
            {
                if (array_key_exists('HTTP_' . $header, $_SERVER))
                {
                    $values[] = 100000000;
                    break;
                }
            }

            // Get a ini parameters and push to array too.
            $values[] = $this->sizeToBytes(ini_get('post_max_size'));
            $values[] = $this->sizeToBytes(ini_get('upload_max_filesize'));

            // Then get the lowest value.
            return min($values);
        }

        return $this->sizeToBytes($config);
    }

    /** @noinspection PhpMissingBreakStatementInspection */
    protected function sizeToBytes(string $size): int
    {
        $bytes = intval($size);
        switch (strtolower(substr($size, -1)))
        {
            case 'g':
                $bytes *= 1024;
            // fall through

            case 'm':
                $bytes *= 1024;
            // fall through

            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }

    protected function getServerIdByKey(string $key): ?int
    {
        foreach ($this->app()->config()['servers'] as $serverId => $server)
        {
            if ($server['key'] === $key)
            {
                return $serverId;
            }
        }

        return null;
    }
}