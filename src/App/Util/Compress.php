<?php
declare(strict_types=1);

namespace App\Util;

use App\Compression\AbstractCompressor;
use function count;

class Compress
{
    /** @var array<string, AbstractCompressor> */
    protected static $compressors = [];

    /** @var array<string,string> */
    protected static $map = [];

    /**
     * Returns the cached compressor.
     *
     * @param string $algoName
     * @param bool $verifyUsable
     * @return AbstractCompressor|null
     */
    public static function getCompressor(string $algoName, bool $verifyUsable = true): ?AbstractCompressor
    {
        self::checkMap();

        /** @var AbstractCompressor|null $compressor */
        $compressor = self::$compressors[$algoName] ?? null;
        if ($compressor === null)
        {
            $handler = self::getHandlerClass($algoName);
            if ($handler !== null)
            {
                /** @var AbstractCompressor $compressor */
                $compressor = new $handler(\App::app());
                self::$compressors[$algoName] = $compressor;
            }
        }

        return (!$verifyUsable || ($compressor !== null && $compressor::isSupportedAlgo())) ?
            $compressor :
            null;
    }

    /**
     * Returns the compressor handler name by algo name.
     *
     * @param string $algoName
     * @return string|null
     */
    public static function getHandlerClass(string $algoName): ?string
    {
        self::checkMap();

        return self::$map[$algoName] ?? null;
    }

    protected static function isUsable(string $handler): bool
    {
        if (!in_array(AbstractCompressor::class, class_parents($handler)))
        {
            return false;
        }

        return call_user_func([$handler, 'isSupportedAlgo']);
    }

    /**
     * Fills the internal map with compressor handlers.
     */
    protected static function checkMap(): void
    {
        if (count(self::$map) !== 0)
        {
            return;
        }

        self::$map = \App::app()->container()['compressMap'];
    }
}
