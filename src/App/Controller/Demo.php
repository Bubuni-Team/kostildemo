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
        $app = $this->app();

        $hash = $this->getFromRequest('hash');
        $validHashes = [$app->dataRegistry()['cleanupRunHash'],
            $this->app()->config()['system']['upgradeKey']];

        if (!in_array($hash, $validHashes))
        {
            throw $this->exception('Not found', 404);
        }
        @set_time_limit(0);

        return $this->json([
            'success' => true,
            'entries' => \App\Util\Demo::cleanup($app)
        ]);
    }

    public function actionDelete(): string
    {
        $this->assertIsAdmin();
        $demoId = $this->getFromRequest('id');

        $stmt = $this->app()->db()->prepare('SELECT * FROM `record` WHERE `demo_id` = ?');
        $stmt->execute([$demoId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record)
        {
            return $this->json([
                'success' => false
            ]);
        }

        return $this->json([
            'success' => \App\Util\Demo::delete($record)
        ]);
    }
}
