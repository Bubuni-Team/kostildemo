<?php /** @noinspection PhpUnused */

/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 18.06.2021
 * Time: 19:55
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


use App\PrintableException;
use PDO;

class Demo extends AbstractController
{
    public function actionIndex(): string
    {
        $db = $this->db();
        $rawDemoList = $db->query("SELECT * FROM `record` ORDER BY `uploaded_at` DESC", PDO::FETCH_ASSOC);

        $demoList = [];
        foreach ($rawDemoList as $demo)
        {
            $recordId = (int) $demo['record_id'];
            $demoList[$recordId] = $demo;
            $demoList[$recordId]['players'] = [];
        }

        if (!empty($demoList))
        {
            $playerStmt = $db->prepare(
                sprintf("SELECT * FROM `record_player` WHERE `record_id` IN (%s)", implode(',', array_keys($demoList)))
            );
            $playerStmt->execute();

            foreach ($playerStmt->fetchAll(PDO::FETCH_ASSOC) as $player)
            {
                $demoList[(int) $player['record_id']]['players'][$player['account_id']] = $player;
            }

            $playerId = $this->getFromRequest('find');
            // TODO: refactor, we can fetch necessary data directly from DB, w/o filtering
            if ($playerId)
            {
                $demoList = array_filter($demoList, function ($demo) use ($playerId)
                {
                    return in_array($playerId, array_keys($demo['players']));
                });
            }
        }

        return $this->template('demo/index', [
            'secondaryTitle' => 'Demo index',
            'demoList' => $demoList,
            'playerId' => $playerId ?? null
        ]);
    }

    /**
     * @throws PrintableException
     */
    public function actionCleanup(): string
    {
        $registry = $this->app()->dataRegistry();
        $hash = $this->getFromRequest('hash');
        if (!$hash || $registry['cleanupRunHash'] !== $hash)
        {
            throw $this->exception('Not found', 404);
        }
        @set_time_limit(0);

        $systemConfig = $this->app()->config()['system'];
        $registry['cleanupRunTime'] = time() + ($systemConfig['cleanupCooldown'] ?? 7200);

        $stmt = $this->db()->prepare('SELECT * FROM `record` WHERE `uploaded_at` < :time');
        $stmt->bindValue(':time', time() - ($systemConfig['cleanupCutOff'] ?? 172800));
        $stmt->execute();

        $demoIds = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $demo)
        {
            $demoId = $demo['demo_id'];
            $demoIds[] = "'$demoId'";
            $demoFn = \App\Util\Demo::getDemoFileNameByDemoId($demoId);
            if (file_exists($demoFn))
            {
                @unlink($demoFn);
            }
        }

        if (!empty($demoIds))
        {
            $this->db()->query(sprintf('DELETE FROM `record` WHERE `demo_id` IN (%s)', implode(',', $demoIds)));
        }

        return $this->json([
            'success' => true
        ]);
    }
}