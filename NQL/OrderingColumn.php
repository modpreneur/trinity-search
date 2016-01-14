<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;


/**
 * Class OrderingColumn
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class OrderingColumn extends Column
{
    /** @var string  */
    private $ordering = "ASC";


    /**
     * @return string
     */
    public function getOrdering() {
        return $this->ordering;
    }


    /**
     * @param string $ordering
     */
    private function setOrdering($ordering) {
        $this->ordering = $ordering;
    }


    /**
     * @param Column $column
     * @return OrderingColumn
     */
    private static function wrap(Column $column) : OrderingColumn {
        return new OrderingColumn($column->getName(), $column->getAlias(), $column->getWrappingFunction(), $column->getJoinWith());
    }


    /**
     * @param string $str
     * @param string $ordering
     * @param string|null $alias
     * @return OrderingColumn
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     */
    public static function parse($str, $ordering = "ASC", $alias = null) : OrderingColumn
    {
        $column = self::wrap(parent::parse($str, $alias));
        $column->setOrdering($ordering);

        return $column;
    }
}