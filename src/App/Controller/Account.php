<?php
declare(strict_types=1);

namespace App\Controller;


use Kruzya\SteamIdConverter\SteamID;
use LightOpenID;

class Account extends AbstractController
{
    public function preAction(): void
    {
        $this->setHttpCode(302);
        $this->setHeader('Location', './');
    }

    public function actionLogin(): string
    {
        $lightOpenId = new LightOpenID($this->app()->publicUrl());
        $lightOpenId->identity = 'https://steamcommunity.com/openid';

        if (!$lightOpenId->mode)
        {
            $this->setHeader('Location', $lightOpenId->authUrl());
        }
        else if ($lightOpenId->mode == 'id_res' && $lightOpenId->validate())
        {
            preg_match("/^https?:\/\/steamcommunity\.com\/openid\/id\/(7656[0-9]{13}+)$/", $lightOpenId->identity,
                $matches);

            if (!empty($matches[1]))
            {
                @setcookie('steam_id', (string) (new SteamID($matches[1]))->accountId(), time() + (86400 * 7));
            }
        }

        return '';
    }

    public function actionLogout(): string
    {
        if (array_key_exists('steam_id', $_COOKIE))
        {
            @setcookie('steam_id', '', time() - 1);
        }

        return '';
    }
}
