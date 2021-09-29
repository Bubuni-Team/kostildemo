<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 05.07.2021
 * Time: 21:13
 * Made with <3 by West from Bubuni Team
 */

namespace App\Util;


use App;
use PDO;
use function json_decode;

class Demo
{
    public static function getDirectoryWithChunks(): string
    {
        return sprintf('%s/data/chunks', App::$dir);
    }

    public static function getDirectoryWithRecords(): string
    {
        return sprintf('%s/data/demos', App::$dir);
    }

    public static function getChunkDirByDemoId(string $demoId): string
    {
        return sprintf('%s/%s', self::getDirectoryWithChunks(), $demoId);
    }

    public static function getLocalPath(string $demoId, string $algoName, array $data): string
    {
        /** @var App\Compression\AbstractCompressor $compressor */
        $compressor = Compress::getCompressor($algoName) ?? Compress::getCompressor('as_is');

        return sprintf('%s/data/demos/%s', App::$dir, $compressor->buildRelativePath($demoId, $data));
    }

    public static function cleanup(App $app): array
    {
        $registry = $app->dataRegistry();
        $systemConfig = $app->config()['system'];

        $registry['cleanupRunTime'] = time() + ($systemConfig['cleanupCooldown'] ?? 7200);

        $db = $app->db();
        $stmt = $db->prepare('SELECT `demo_id`, `algo`, `algo_data` FROM `record` WHERE `uploaded_at` < :time FOR UPDATE');
        $stmt->bindValue(':time', time() - ($systemConfig['cleanupCutOff'] ?? 172800));
        $stmt->execute();

        $demoIds = [];
        while ($record = $stmt->fetchAll(PDO::FETCH_ASSOC))
        {
            if (!is_string($record['demo_id']) || !self::delete($record, false))
            {
                continue;
            }

            $demoIds[] = $record['demo_id'];
        }

        if (!empty($demoIds))
        {
            $escapedDemoIds = array_map(function ($el) { return "'$el'"; }, $demoIds);
            $escapedDemoIdsWithSeparator = implode(', ', $escapedDemoIds);

            $db->query(sprintf('DELETE FROM `record` WHERE `demo_id` IN (%s)', $escapedDemoIdsWithSeparator));
            $db->query(sprintf('DELETE FROM `record` WHERE `demo_id` IN (%s)', $escapedDemoIdsWithSeparator));
        }

        return $demoIds;
    }

    public static function delete(array $record, bool $pruneFromDb = true): bool
    {
        list($demoId, $algo, $algoUnserializedData) = [$record['demo_id'], $record['algo'],
            @json_decode($record['algo_data'])];

        $demoFn = self::getLocalPath($demoId, $algo, $algoUnserializedData);
        $success = (@file_exists($demoFn) && @unlink($demoFn));
        if ($success && $pruneFromDb)
        {
            $db = \App::app()->db();

            $db->prepare('DELETE FROM `record` WHERE `demo_id` = ?')
                ->execute([$demoId]);
            $db->prepare('DELETE FROM `record_player` WHERE `demo_id` = ?')
                ->execute([$demoId]);
        }

        return $success;
    }
}
