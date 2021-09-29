<?php
declare(strict_types=1);

namespace App\Compression;


class GzipCompressor extends SimpleIoCompressor
{
    protected static $writeFn = 'gzwrite';
    protected static $closeFn = 'gzclose';
    protected static $openFn = 'gzopen';

    protected static $fileExtension = '.gz';
}
