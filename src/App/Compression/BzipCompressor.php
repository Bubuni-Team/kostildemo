<?php
declare(strict_types=1);

namespace App\Compression;


class BzipCompressor extends SimpleIoCompressor
{
    protected static $writeFn = 'bzwrite';
    protected static $closeFn = 'bzclose';
    protected static $openFn = 'bzopen';

    protected static $fileExtension = '.bz2';
}
