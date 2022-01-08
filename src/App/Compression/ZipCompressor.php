<?php
declare(strict_types=1);

namespace App\Compression;


use ZipArchive;
use function class_exists;
use function extension_loaded;

class ZipCompressor extends AbstractCompressor
{
    public static function isSupportedAlgo(): bool
    {
        if (!extension_loaded('zip'))
        {
            return false;
        }

        // We can expect if extension is loaded, then required class is available,
        // but this is not always true.
        // class_exists() can trigger composer autoload code, so we use this check
        // only if extension_loaded() is finished with `true` result for reducing
        // IO operations.
        return class_exists('ZipArchive');
    }

    public static function getFileExtension(): string
    {
        return 'zip';
    }

    public function compress(string $source, string $target): ?array
    {
        $zipArchive = new ZipArchive();
        $errCode = $zipArchive->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($errCode !== true)
        {
            return null;
        }

        $packResult = $zipArchive->addFile($source, basename($source));
        $zipArchive->close();

        return $packResult ? [] : null;
    }

    public function buildRelativePath(string $demoId, array $data): string
    {
        return sprintf('%s.zip', $demoId);
    }
}
