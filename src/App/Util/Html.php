<?php
declare(strict_types=1);

namespace App\Util;


use function htmlspecialchars;

class Html
{
    public static function toAttributes(array $attributes = []): string
    {
        $retVal = [];

        // TODO: perform conversions like "dataDemoId" --> "data-demo-id"
        foreach ($attributes as $name => $val)
        {
            $retVal[] = sprintf('%s="%s"', $name, htmlspecialchars($val));
        }

        return implode(' ', $retVal);
    }
}
