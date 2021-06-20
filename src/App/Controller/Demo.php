<?php /** @noinspection PhpUnused */

/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 18.06.2021
 * Time: 19:55
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


use PDO;

class Demo extends AbstractController
{
    public function actionIndex(): string
    {
        $db = $this->db();
        $rawDemoList = $db->query("SELECT * FROM `record`");

        $demoList = [];
        foreach ($rawDemoList as $demo)
        {
            $recordId = (int) $demo['record_id'];
            $demoList[$recordId] = $demo;
            $demoList[$recordId]['players'] = [];
        }

        $playerStmt = $db->prepare("SELECT * FROM `record_player` WHERE `record_id` IN (:demoIds)");
        $playerStmt->bindValue(':demoIds', implode(', ', array_keys($demoList)));
        $playerStmt->execute();

        foreach ($playerStmt->fetchAll(PDO::FETCH_ASSOC) as $player)
        {
            $demoList[(int) $player['record_id']]['players'][] = $player;
        }

        return $this->template('demo_index', [
            'secondaryTitle' => 'Demo index',
            'demoList' => $demoList,
        ]);
    }
}