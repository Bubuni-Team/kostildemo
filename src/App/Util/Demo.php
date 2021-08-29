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

class Demo
{
    public static function getChunkDirByDemoId(string $demoId): string
    {
        return sprintf('%s/data/chunks/%s', App::$dir, $demoId);
    }

    public static function getDemoFileNameByDemoId(string $demoId): string
    {
        return sprintf('%s/data/demos/%s.dem', App::$dir, $demoId);
    }

    public static function cleanup(App $app): array
    {
        $registry = $app->dataRegistry();
        $systemConfig = $app->config()['system'];

        $registry['cleanupRunTime'] = time() + ($systemConfig['cleanupCooldown'] ?? 7200);

        $db = $app->db();
        $stmt = $db->prepare('SELECT `demo_id` FROM `record` WHERE `uploaded_at` < :time FOR UPDATE');
        $stmt->bindValue(':time', time() - ($systemConfig['cleanupCutOff'] ?? 172800));
        $stmt->execute();

        $demoIds = [];
        while ($demoId = $stmt->fetchColumn()) if (is_string($demoId))
        {
            $demoFn = self::getDemoFileNameByDemoId($demoId);
            if (file_exists($demoFn) && unlink($demoFn))
            {
                $demoIds[] = $demoId;
            }
        }

        if (!empty($demoIds))
        {
            $escapedDemoIds = array_map(function ($el) { return "'$el'"; }, $demoIds);
            $db->query(sprintf('DELETE FROM `record` WHERE `demo_id` IN (%s)', implode(', ', $escapedDemoIds)));
        }

        return $demoIds;
    }
}