<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;


/**
 * Class Column
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class Column
{
    private static $regFuncColumn = '/(?J)(^((?P<function>[^\s]+)\(((?P<alias>[^\s\.]+)\.)?((?P<joinWith>[^\s\.]+)\.)?(?P<column>[^\s]+)\))$)|(^((?P<alias>[^\s\.]+)\.)?((?P<joinWith>[^\s\.]+):)?(?P<column>[^\s]+)$)/';

    private $name;
    private $wrappingFunction;
    private $alias;
    private $joinWith;


    function __construct($name, $alias = null, $wrappingFunction = null, $joinWith = null)
    {
        $this->name = $name;
        $this->wrappingFunction = StringUtils::isEmpty($wrappingFunction) ? null : $wrappingFunction;
        $this->alias = StringUtils::isEmpty($alias) ? null : $alias;
        $this->joinWith = StringUtils::isEmpty($joinWith) ? null : $joinWith;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return string|null
     */
    public function getWrappingFunction()
    {
        return $this->wrappingFunction;
    }


    public function getAlias()
    {
        return $this->alias;
    }


    /**
     * @return string|null
     */
    public function getJoinWith()
    {
        return $this->joinWith;
    }


    /**
     * @param string|null $alias
     */
    function setAlias($alias)
    {
        $this->alias = $alias;
    }


    /**
     * @param string|null $joinWith
     */
    function setJoinWith($joinWith)
    {
        $this->joinWith = $joinWith;
    }


    /**
     * Alias = null - parsed alias is used, otherwise parsed alias is used as join field
     * @param $str
     * @param null $alias
     * @return Column
     * @throws SyntaxErrorException
     */
    public static function parse($str, $alias = null)
    {
        $match = array();
        $column = trim($str);
        $wasFound = preg_match(self::$regFuncColumn, $column, $match);

        if ($wasFound) {
            $name = $match['column'];
            $alias = is_null($alias) ? $match['alias'] : $alias;
            $function = $match['function'];
            $joinWith = is_null($alias) ? $match['alias'] : $match['joinWith'];

            return new Column($name, $alias, $function, $joinWith);
        } else {
            throw new SyntaxErrorException("Invalid column name '$column'");
        }
    }
}