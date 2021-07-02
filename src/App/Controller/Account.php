<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Controller;


use Kruzya\SteamIdConverter\SteamID;

class Account extends AbstractController
{
    public function preAction(): void
    {
        @session_start();

        $this->setHttpCode(302);
        $this->setHeader('Location', './');
    }

    public function actionLogin(): string
    {
        $lightOpenId = new \LightOpenID($this->app()->publicUrl());
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
                $_SESSION['steam_id'] = (new SteamID($matches[1]))->accountId();
            }
        }

        return '';
    }

    public function actionLogout(): string
    {
        if (array_key_exists('steam_id', $_SESSION))
        {
            unset($_SESSION['steam_id']);
        }

        return '';
    }
}
