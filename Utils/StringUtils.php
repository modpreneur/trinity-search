<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Utils;


/**
 * Class StringUtils
 * @package Trinity\Bundle\SearchBundle\Utils
 */
final class StringUtils
{
    const EMPTY_STR = '';


    /**
     * @param string $str
     *
     * @return bool
     */
    public static function isEmpty($str) : bool
    {
        return (self::EMPTY_STR === $str) || (null === $str);
    }


    /**
     * @param string $str
     * @return int
     */
    public static function length($str) : int
    {
        return \strlen($str);
    }


    public static function substring($str, $start, $end = null) : string
    {
        if ((0 > $start) && (0 < $end)) {
            $start = 0;
        }
        if (null === $end) {
            $end = self::length($str);
        }

        return \substr($str, $start, $end - $start);
    }


    public static function startsWith($str, $prefix) : bool
    {
        return ((null === $str) && (null === $prefix)) ? true : self::substring(
                $str,
                0,
                self::length($prefix)
            ) === $prefix;
    }


    public static function removeStart($str, $remove)
    {
        if ((true === self::isEmpty($str)) || (true === self::isEmpty($remove))) {
            return $str;
        }
        if (true === self::startsWith($str, $remove)) {
            return self::substring($str, self::length($remove));
        }

        return $str;
    }


    public static function indexOf($str, $search, $startPos = 0)
    {
        $result = self::validateIndexOf($str, $search, $startPos);
        if (true !== $result) {
            return $result;
        }
        if (true === self::isEmpty($search)) {
            return $startPos;
        }
        $pos = \strpos($str, $search, $startPos);

        return (false === $pos) ? -1 : $pos;
    }


    private static function validateIndexOf($str, $search, &$startPos)
    {
        if ((null === $str) || (null === $search)) {
            return -1;
        }
        $lengthSearch = self::length($search);
        $lengthStr = self::length($str);
        if ((0 === $lengthSearch) && ($startPos >= $lengthStr)) {
            return $lengthStr;
        }
        if ($startPos >= $lengthStr) {
            return -1;
        }
        if (0 > $startPos) {
            $startPos = 0;
        }

        return true;
    }


    /**
     * Trim array of strings
     * @param $array
     * @return array
     */
    public static function trimStringArray($array)
    {
        foreach ($array as &$item) {
            $item = trim($item);
        }

        return $array;
    }
}