<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;

/**
 * Class From
 * @package Trinity\SearchBundle\NQL
 */
class From
{
    private static $regFromTable = '/^(?<prefix>[eg])\.(?<name>\w+)(\s(?<alias>\w+))?$/';

    private $tables = [];


    /**
     * @return Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }


    /**
     * @param $str
     * @return From
     * @throws SyntaxErrorException
     */
    public static function parse($str) : From
    {
        $from = new From();

        $tableStrings = StringUtils::trimStringArray(explode(',', $str));

        $tables = [];

        foreach ($tableStrings as $tableStr) {
            $match = [];
            $wasFound = preg_match(self::$regFromTable, $tableStr, $match);

            if ($wasFound) {
                $tables[] = new Table(
                    $match['prefix'],
                    $match['name'],
                    array_key_exists('alias', $match) ? $match['alias'] : null
                );
            } else {
                throw new SyntaxErrorException("Invalid column \"$tableStr\"");
            }
        }

        $from->tables = $tables;

        return $from;
    }
}
