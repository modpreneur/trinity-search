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
    /** @var string */
    private $ordering = 'ASC';


    /**
     * @return string
     */
    public function getOrdering(): string
    {
        return $this->ordering;
    }


    /**
     * @param string $ordering
     */
    public function setOrdering($ordering): void
    {
        $this->ordering = $ordering;
    }


    /**
     * @param Column $column
     * @return OrderingColumn
     */
    private static function wrap(Column $column) : OrderingColumn
    {
        return new OrderingColumn(
            $column->getName(),
            $column->getAlias(),
            $column->getWrappingFunction(),
            $column->getJoinWith()
        );
    }


    /**
     * @param string $str
     * @param string $ordering
     * @param string|null $alias
     * @return Column
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     */
    public static function parse(string $str, $alias = null, $ordering = 'ASC'): Column
    {
        $column = self::wrap(parent::parse($str, $alias));
        $column->setOrdering($ordering);

        return $column;
    }


    /**
     * Flip ordering
     */
    public function flipOrdering(): void
    {
        if ($this->ordering === 'ASC') {
            $this->ordering = 'DESC';
        } /** @noinspection DefaultValueInElseBranchInspection */
        else {
            $this->ordering = 'ASC';
        }
    }
}
