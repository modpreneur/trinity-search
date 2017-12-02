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

    /** @var string */
    private $name;

    /** @var string | null */
    private $alias;

    /** @var string | null  */
    private $wrappingFunction;

    /** @var array | null */
    private $joinWith;


    /** @noinspection MoreThanThreeArgumentsInspection
     *
     * Column constructor.
     * @param string $name
     * @param string|null $alias
     * @param string|null $wrappingFunction
     * @param array $joinWith
     */
    public function __construct(
        string $name,
        ?string $alias = null,
        ?string $wrappingFunction = null,
        array $joinWith = []
    ) {
        $this->name = $name;
        $this->wrappingFunction = StringUtils::isEmpty($wrappingFunction) ? null : $wrappingFunction;
        $this->alias = StringUtils::isEmpty($alias) ? null : $alias;
        $this->joinWith = $joinWith;
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
    public function getWrappingFunction(): ?string
    {
        return $this->wrappingFunction;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }


    /**
     * @return string[]|null
     */
    public function getJoinWith(): array
    {
        return $this->joinWith;
    }


    /**
     * @param string|null $alias
     */
    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;
    }


    /**
     * @param string[]|null $joinWith
     */
    public function setJoinWith(?array $joinWith): void
    {
        $this->joinWith = $joinWith;
    }


    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param null $wrappingFunction
     */
    public function setWrappingFunction($wrappingFunction)
    {
        $this->wrappingFunction = $wrappingFunction;
    }


    /**
     * @return string
     */
    public function getFullName(): string
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
     * @param string $str
     * @param null $alias
     * @return Column
     * @throws SyntaxErrorException
     */
    public static function parse(string $str, $alias = null): Column
    {
        $match = [];
        $column = trim($str);
        $wasFound = preg_match(self::$regFuncColumn, $column, $match);

        if ($wasFound) {
            $name = $match['column'];
            $alias = $alias ?? $match['alias'];
            $function = $match['function'];
            
            /** @noinspection NestedTernaryOperatorInspection */
            $joinWith =
                null === $alias ?
                    $match['alias'] :
                    StringUtils::isEmpty($match['joinWith']) ? [] : explode(':', $match['joinWith']);
            return new Column($name, $alias, $function, $joinWith);
        }

        // Invalid column name
        throw new SyntaxErrorException("Invalid column name '$column'");
    }
}
