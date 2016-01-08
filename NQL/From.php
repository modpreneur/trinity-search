<?php

namespace Trinity\SearchBundle\NQL;

use Trinity\SearchBundle\Exception\SyntaxErrorException;
use Trinity\SearchBundle\Utils\StringUtils;

class From
{
    private static $regFromTable = '/^(?<prefix>[eg])\.(?<name>\w+)(\s(?<alias>\w+))?$/';

    private $tables = [];

    /**
     * @return Table[]
     */
    public function getTables() {
        return $this->tables;
    }

    /**
     * @param $str
     * @return From
     * @throws SyntaxErrorException
     */
    public static function parse($str) {
        $from = new From();

        $tableStrings = StringUtils::trimStringArray(preg_split("/,/", $str));

        $tables = [];

        foreach($tableStrings as $tableStr) {

            $match = array();
            $wasFound = preg_match(self::$regFromTable, $tableStr, $match);

            if($wasFound) {
                $tables[] = new Table($match['prefix'], $match['name'], array_key_exists('alias', $match) ? $match['alias'] : null);
            } else {
                throw new SyntaxErrorException("Invalid column \"$tableStr\"");
            }
        }

        $from->tables = $tables;

        return $from;
    }
}