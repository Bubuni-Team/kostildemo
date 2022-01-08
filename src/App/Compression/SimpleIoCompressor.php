<?php
declare(strict_types=1);

namespace App\Compression;


use function fclose;
use function feof;
use function fopen;
use function fread;
use function function_exists;
use function is_resource;
use function sprintf;
use function strlen;

class SimpleIoCompressor extends AbstractCompressor
{
    protected static $writeFn = 'fwrite';
    protected static $closeFn = 'fclose';
    protected static $openFn = 'fopen';
    protected static $validResourceFn = 'is_resource';

    protected static $fileExtension = '';

    public static function isSupportedAlgo(): bool
    {
        return function_exists(static::$openFn) &&
            function_exists(static::$closeFn) &&
            function_exists(static::$writeFn);
    }

    public static function getFileExtension(): string
    {
        return sprintf('dem%s', static::$fileExtension);
    }

    public function compress(string $source, string $target): ?array
    {
        $recordHandle = fopen($source, 'rb');
        if (!is_resource($recordHandle))
        {
            return null;
        }

        $archiveHandle = $this->open($target, 'wb');
        if (!$this->isValid($archiveHandle))
        {
            fclose($recordHandle);
            return null;
        }

        // Я всего лишь хочу defer, как в Голанге...
        try
        {
            do
            {
                if (!$this->write($archiveHandle, fread($recordHandle, 4096)))
                {
                    return null;
                }
            } while (!feof($recordHandle));

            return [];
        }
        finally
        {
            fclose($recordHandle);
            $this->close($archiveHandle);
        }
    }

    public function buildRelativePath(string $demoId, array $data): string
    {
        return sprintf('%s.%s', $demoId, static::getFileExtension());
    }

    protected function open(string $path, string $mode)
    {
        return call_user_func(static::$openFn, $path, $mode);
    }

    protected function write($handle, string $content): bool
    {
        $writeResult = call_user_func(static::$writeFn, $handle, $content);
        if ($writeResult === false)
        {
            return false;
        }

        return $writeResult === strlen($content);
    }

    protected function close($handle): void
    {
        call_user_func(static::$closeFn, $handle);
    }

    protected function isValid($handle): bool
    {
        return call_user_func(static::$validResourceFn, $handle);
    }
}
