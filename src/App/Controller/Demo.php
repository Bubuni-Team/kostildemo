<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 18.06.2021
 * Time: 19:55
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


class Demo extends AbstractController
{
    public function actionIndex()
    {
        return $this->template('demo_index', ['secondaryTitle' => 'Demo index']);
    }
}