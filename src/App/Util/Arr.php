<?php
declare(strict_types=1);

namespace App\Util;


use ArrayAccess;
use Generator;
use LogicException;
use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function call_user_func_array;
use function count;
use function func_get_args;
use function function_exists;
use function get_class;
use function gettype;
use function implode;
use function is_array;
use function is_object;

class Arr
{
    /**
     * Creates a context from array.
     *
     * @param array $source
     * @param string $delimiter
     * @param string $prefix
     * @return Generator
     */
    public static function createContext(array $source, string $delimiter = '.', string $prefix = ''): Generator
    {
        foreach ($source as $key => $value)
        {
            $key = $prefix . $key;
            if (is_array($value))
            {
                yield from self::createContext($source, $delimiter, $key . $delimiter);
                continue;
            }

            yield $key => $value;
        }
    }

    /**
     * Checks if the given key or index exists in the array
     *
     * @param	mixed	$array
     * @param	mixed	$key
     * @return	boolean
     */
    public static function keyExists($array, $key): bool
    {
        if (is_array($array))
        {
            return array_key_exists($key, $array);
        }
        else if (is_object($array))
        {
            if ($array instanceof ArrayAccess)
            {
                return isset($array[$key]);
            }
        }

        $typeName = is_object($array) ? get_class($array) : gettype($array);
        $expected = ['array', ArrayAccess::class];

        throw new LogicException('Passed incorrect array object. Expected: ' . implode(' OR ', $expected) . '. Given: ' . $typeName);
    }

    /**
     * Checks if the all given keys or indexes in the array.
     *
     * @param	mixed	$array
     * @param	array	$keys
     * @return	boolean
     */
    public static function keysExists($array, array $keys): bool
    {
        foreach ($keys as $key)
        {
            if (!self::keyExists($array, $key))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Merges passed arrays.
     *
     * @return array
     */
    public static function merge(): array
    {
        return call_user_func_array('array_merge', func_get_args());
    }

    /**
     * Gets the first key of an array
     *
     * @param  array	$array
     * @return mixed
     */
    public static function firstKey(array $array)
    {
        if (function_exists('array_key_first'))
        {
            return array_key_first($array);
        }

        // some PHP versions can't handle code like "func()[0]".
        $keys = array_keys($array);
        return $keys[0] ?? null;
    }

    /**
     * Gets the last key of an array
     *
     * @param  array		$array
     * @return mixed
     */
    public static function lastKey(array $array)
    {
        if (function_exists('array_key_last'))
        {
            return array_key_last($array);
        }

        // some PHP versions can't handle code like "func()[0]".
        $keys = array_keys($array);
        $index = count($keys)-1;

        return $keys[$index] ?? null;
    }
}
