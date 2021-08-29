<?php
declare(strict_types=1);

namespace App;


use App;
use App\Cookie\Cookie;
use App\Cookie\CookieSameSiteRestriction;
use function sha1;

class CookieSession implements \SessionHandlerInterface
{
    /** @var App */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId)
    {
        $cookieManager = $this->app->cookieManager();

        /** @var Cookie[]|null[] $cookies */
        $cookies = [$cookieManager->get('session_content'), $cookieManager->get('session_signature')];
        foreach ($cookies as $cookie)
        {
            if (!$cookie)
            {
                continue;
            }

            $cookie->delete();
        }

        return true;
    }

    /**
     * @param int $maxLifeTime
     * @return int|false
     */
    public function gc($maxLifeTime)
    {
        return 1;
    }

    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId)
    {
        $cookieManager = $this->app->cookieManager();
        $content = $cookieManager->get('session_content');
        if (!$content)
        {
            return '';
        }

        $signature = $cookieManager->get('session_signature');
        if (!$signature || $this->signatureFor($content->getValue()) !== $signature->getValue())
        {
            return '';
        }

        return $content->getValue();
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    public function write($sessionId, $sessionData): bool
    {
        $cookieManager = $this->app->cookieManager();
        $cookieManager->createIfNotExists('session_content')
            ->setValue($sessionData)->setExpiryTime($this->expireTime())
            ->setSameSiteRestriction(CookieSameSiteRestriction::STRICT)
            ->save();

        $cookieManager->createIfNotExists('session_signature')
            ->setValue($this->signatureFor($sessionData))
            ->setSameSiteRestriction(CookieSameSiteRestriction::STRICT)
            ->setExpiryTime($this->expireTime())->save();

        return true;
    }

    protected function salt(): string
    {
        $config = App::app()->config();
        return ($config['system'] ?? [])['salt'] ?? sha1($config['db']['password'] . $config['db']['user']);
    }

    protected function signatureFor(string $data): string
    {
        return sha1($data . $this->salt());
    }

    protected function expireTime(): int
    {
        return time() + 86400;
    }
}
