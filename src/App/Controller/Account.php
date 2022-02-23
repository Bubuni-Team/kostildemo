<?php
declare(strict_types=1);

namespace App\Controller;


use Kruzya\SteamIdConverter\SteamID;
use LightOpenID;

/**
 * @psalm-suppress InvalidScalarArgument
 */
class Account extends AbstractController
{
    public function preAction(): void
    {
        $this->setHttpCode(302);
        $this->setHeader('Location', './');

        $redirectTo = $this->getFromRequest('__redirect_to');
        if ($redirectTo)
        {
            $_SESSION['after_account_op_goto'] = $redirectTo;
        }
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
                $_SESSION['steam_id'] = (new SteamID($matches[1]))->accountId();
            }

            $this->rewriteReturnUrlIfSet();
        }

        return '';
    }

    public function actionLogout(): string
    {
        if (array_key_exists('steam_id', $_SESSION))
        {
            unset($_SESSION['steam_id']);
        }

        $this->rewriteReturnUrlIfSet();
        return '';
    }

    protected function rewriteReturnUrlIfSet(): void
    {
        $returnUrl = $_SESSION['after_account_op_goto'] ?? '';
        if (empty($returnUrl))
        {
            return;
        }

        $this->setHeader('Location', $returnUrl);
    }
}
