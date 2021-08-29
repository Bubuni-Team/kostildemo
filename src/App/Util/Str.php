<?php
declare(strict_types=1);

namespace App\Util;


use Closure;
use InvalidArgumentException;
use function array_key_exists;
use function gettype;
use function is_array;
use function is_object;
use function method_exists;

class Str
{
    public static function formatWithContext(string $str, array $context, string $startToken = '{', string $endToken = '}'): string
    {
        $ctx = Arr::createContext($context);

        return self::format($str, iterator_to_array($ctx, true), $startToken, $endToken);
    }

    public static function format(string $str, array $args = [], string $startToken = '{', string $endToken = '}'): string
    {
        while (true)
        {
            $startTok = strtok($str, $startToken);
            if ($startTok === $str)
            {
                break;
            }

            $searchableStr = strtok($endToken);
            if (!$searchableStr)
            {
                throw new InvalidArgumentException('Not found ending token when start token is detected', 500);
            }

            $token = trim($searchableStr);
            if (!array_key_exists($token, $args))
            {
                throw new InvalidArgumentException('Not found token ' . $token . ' name in arguments', 501);
            }

            $arg = self::valToString($args[$token], sprintf('$args[%s]', $token));
            $str = str_replace(sprintf('%s%s%s', $startToken, $searchableStr, $endToken), $arg, $str);
        }

        return $str;
    }

    /**
     * @param mixed $val
     * @param string $type
     * @return string
     */
    protected static function valToString($val, string $type): string
    {
        $errMessage = sprintf('$type must be a object/string/int/float, array given', $type);
        switch (gettype($val))
        {
            case 'boolean':
                return $val ? 'true' : 'false';
                break;

            // Arrays and resources is too hard object. We're don't handle they.
            case 'array':
            case 'resource':
                throw new InvalidArgumentException($errMessage);
                break;

            case 'object':
                // If we're get Closure - just resolve them.
                if ($val instanceof Closure)
                {
                    return self::valToString($val(), $type);
                }

                // Any another object should strictly has method "__toString()".
                if (!method_exists($val, '__toString'))
                {
                    throw new InvalidArgumentException($errMessage);
                }

                return $val->__toString();
                break;
        }

        // Scalar values can be casted to string without any errors.
        // If we're reached here - our value is scalar.
        return (string) $val;
    }
}
