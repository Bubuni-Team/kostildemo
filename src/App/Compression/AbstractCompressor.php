<?php
declare(strict_types=1);

namespace App\Compression;


abstract class AbstractCompressor
{
    /** @var \App */
    protected $app;

    public function __construct(\App $app)
    {
        $this->app = $app;
    }

    /**
     * Checks the ability use this compression algo.
     * @return bool
     */
    abstract public static function isSupportedAlgo(): bool;

    /**
     * @return string
     */
    abstract public static function getFileExtension(): string;

    /**
     * Performs compression operation.
     *
     * @param string $source
     * @param string $target
     * @return array|null       Array if compress is finished with success.
     */
    abstract public function compress(string $source, string $target): ?array;

    /**
     * Builds relative path for demo record.
     * @param string $demoId    Demo identifier.
     * @param array $data       Data from compressor (can be empty, if called from API for performing compression operation).
     * @return string
     */
    abstract public function buildRelativePath(string $demoId, array $data): string;
}
