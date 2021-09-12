<?php
declare(strict_types=1);

namespace App\Cookie;

use App\Collection\SimpleCollection;
use LogicException;

class CookieManager extends SimpleCollection
{
    /**
     * @var string $cookiesPrefix
     * Prefix for cookies.
     */
    protected $cookiesPrefix = '';

    /**
     * @var string $defaultPath
     * Default path for all cookies.
     */
    protected $defaultPath = '/';

    /**
     * @var string $defaultDomain
     * Default domain for all cookies.
     */
    protected $defaultDomain = '';

    /**
     * CookieManager constructor.
     * @param string $cookiesPrefix
     * @param string $defaultPath
     * @param string $defaultDomain
     * @param array $cookies
     */
    public function __construct(string $cookiesPrefix, string $defaultPath, string $defaultDomain, array $cookies = [])
    {
        $this->cookiesPrefix = $cookiesPrefix;
        $this->defaultPath = $defaultPath;
        $this->defaultDomain = $defaultDomain;

        $_data = [];
        foreach ($cookies as $name => $value)
        {
            $cookie = $this->create($name);
            $cookie->setValue($value);
            $cookie->markAsLoaded();
            $_data[$name] = $cookie;
        }

        parent::__construct($_data);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @throws LogicException
     */
    public function set($key, $value): void
    {
        throw $this->generateErrorException('change cookie referer');
    }

    /**
     * @throws LogicException
     */
    public function clear(): void
    {
        throw $this->generateErrorException('drop all cookies');
    }

    /**
     * @param array $array
     * @throws LogicException
     */
    public function replace(array $array): void
    {
        throw $this->generateErrorException('change cookies referer');
    }

    /**
     * @param string $text
     * @return LogicException
     */
    protected function generateErrorException(string $text): LogicException
    {
        return new LogicException("You can't {$text}");
    }

    /**
     * @param string $name
     *
     * @return Cookie
     */
    public function create(string $name): Cookie
    {
        return new Cookie($this, $name);
    }

    /**
     * Creates a cookie, if not exists.
     * @param string $name
     * @return Cookie
     * @psalm-suppress InvalidNullableReturnType
     */
    public function createIfNotExists(string $name): Cookie
    {
        /** @psalm-suppress NullableReturnStatement */
        return ($this->has($name)) ? $this->get($name) : $this->create($name);
    }

    /**
     * Sends the cookie on browser.
     *
     * @param Cookie $cookie
     */
    public function save(Cookie $cookie): void
    {
        $cookieName = $cookie->getName();
        if ($this->has($cookieName))
        {
            $this->_values[$cookieName] = $cookie;
        }

        $this->sendCookie($cookie);
    }

    public function get($key): ?Cookie
    {
        $cookiesPrefix = $this->getCookiesPrefix();
        if ($this->has($cookiesPrefix . $key))
        {
            $key = $cookiesPrefix . $key;
        }

        return parent::get($key);
    }

    public function has($key): bool
    {
        return (array_key_exists($key, $this->_values) || array_key_exists($this->getCookiesPrefix() . $key, $this->_values));
    }

    /**
     * Deletes the cookie from browser.
     *
     * @param Cookie $cookie
     */
    public function delete(Cookie $cookie): void
    {
        $cookieName = $cookie->getName();
        if (!$this->has($cookieName))
            throw new LogicException("This cookie isn't saved on client.");

        if (!array_key_exists($cookieName, $this->_values))
            $cookieName = $this->getCookiesPrefix() . $cookieName;

        unset($this->_values[$cookieName]);
        $this->sendCookie(
            (clone $cookie)
                ->setExpiryTime(0)
                ->setValue('')
        );
    }

    /**
     * Sends cookie.
     *
     * @param Cookie $cookie
     */
    protected function sendCookie(Cookie $cookie): void
    {
        // Apply prefix.
        $cookieHeader = $cookie->getHeaderString();
        header("Set-Cookie: {$cookieHeader}");
    }

    /**
     * @return string
     */
    public function getCookiesPrefix(): string
    {
        return $this->cookiesPrefix;
    }

    /**
     * @param string $cookiesPrefix
     * @return CookieManager
     */
    public function setCookiesPrefix(string $cookiesPrefix): CookieManager
    {
        $this->cookiesPrefix = $cookiesPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultPath(): string
    {
        return $this->defaultPath;
    }

    /**
     * @param string $defaultPath
     * @return CookieManager
     */
    public function setDefaultPath(string $defaultPath): CookieManager
    {
        $this->defaultPath = $defaultPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultDomain(): string
    {
        return $this->defaultDomain;
    }

    /**
     * @param string $defaultDomain
     * @return CookieManager
     */
    public function setDefaultDomain(string $defaultDomain): CookieManager
    {
        $this->defaultDomain = $defaultDomain;
        return $this;
    }
}
