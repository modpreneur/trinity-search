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
    private static $regFuncColumn = '/(?J)(^((?P<function>\S+)\(((?P<alias>[^\s\.]+)\.)?((?P<joinWith>[^\s\.]+)\.)?(?P<column>\S+)\))$)|(^((?P<alias>[^\s\.]+)\.)?((?P<joinWith>[^\s\.]+):)?(?P<column>\S+)$)/';

    private $name;
    private $wrappingFunction;
    private $alias;
    private $joinWith;


    /** @noinspection MoreThanThreeArgumentsInspection
     * 
     * Column constructor.
     * @param $name
     * @param null $alias
     * @param null $wrappingFunction
     * @param null $joinWith
     */
    public function __construct($name, $alias = null, $wrappingFunction = null, $joinWith = null)
    {
        $this->name = $name;
        $this->wrappingFunction = StringUtils::isEmpty($wrappingFunction) ? null : $wrappingFunction;
        $this->alias = StringUtils::isEmpty($alias) ? null : $alias;
        $this->joinWith = StringUtils::isEmpty($joinWith) ? [] : $joinWith;
    }


    /**
     * @return string
     */
    public function getName() : string
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

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }


    /**
     * @return string[]|null
     */
    public function getJoinWith() : array
    {
        return $this->joinWith;
    }


    /**
     * @param string|null $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }


    /**
     * @param string[]|null $joinWith
     */
    public function setJoinWith($joinWith)
    {
        $this->joinWith = $joinWith;
    }


    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return string
     */
    public function getFullName() : string
    {
        $fullName = $this->getName();
        $joinCount = count($this->joinWith);
        if ($joinCount) {
            if ($joinCount > 1) {
                $fullName = implode('.', $this->joinWith) . '.' . $fullName;
            } else {
                $fullName = $this->joinWith[0] . '.' . $fullName;
            }
        }
        if (null !== $this->alias) {
            $fullName = $this->alias . '.' . $fullName;
        }
        return $fullName;
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
        $match = [];
        $column = trim($str);
        $wasFound = preg_match(self::$regFuncColumn, $column, $match);

        if ($wasFound) {
            $name = $match['column'];
            $alias = null === $alias ? $match['alias'] : $alias;
            $function = $match['function'];
            
            /** @noinspection NestedTernaryOperatorInspection */
            $joinWith =
                null === $alias ?
                    $match['alias'] :
                    StringUtils::isEmpty($match['joinWith']) ? [] : explode(':', $match['joinWith']);
            return new Column($name, $alias, $function, $joinWith);
        } else {
            throw new SyntaxErrorException("Invalid column name '$column'");
        }
    }
}
