<?php
/**
 * Created by PhpStorm.
 * User: martinmatejka
 * Date: 12.01.16
 * Time: 11:52
 */

namespace Trinity\Bundle\SearchBundle\NQL;


class OrderingColumn extends Column
{
    private $ordering = "ASC";

    public function getOrdering() {
        return $this->ordering;
    }

    private function setOrdering($ordering) {
        $this->ordering = $ordering;
    }

    private static function wrap(Column $column) {
        return new OrderingColumn($column->getName(), $column->getAlias(), $column->getWrappingFunction(), $column->getJoinWith());
    }

    public static function parse($str, $ordering = "ASC", $alias = null)
    {
        $column = self::wrap(parent::parse($str, $alias));
        $column->setOrdering($ordering);

        return $column;
    }
}