<?php

namespace Trinity\Bundle\SearchBundle\NQL;

use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;

/**
 * Class OrderBy
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class OrderBy
{
    /** @var OrderingColumn[] */
    private $columns = [];

    /**
     * OrderBy constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return OrderingColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param OrderingColumn[] $columns
     */
    private function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @param string $str
     * @return OrderBy
     * @throws SyntaxErrorException
     */
    public static function parse(string $str) : OrderBy
    {
        /** @var OrderBy $orderBy */
        $orderBy = new OrderBy();

        $exploded = explode(',', $str);
        $columns = [];
        foreach ($exploded as $item) {
            $item = trim($item);
            $args = explode(' ', $item);
            if (count($args) !== 2) {
                throw new SyntaxErrorException('Error in order by part');
            }

            $col = $args[0];
            /** @noinspection MultiAssignmentUsageInspection */
            $ordering = $args[1];

            if (strcasecmp($ordering, 'ASC') !== 0 && strcasecmp($ordering, 'DESC') !== 0) {
                throw new SyntaxErrorException('Unknown order by direction');
            }
            $column = OrderingColumn::parse($col, null, $ordering);
            $columns[] = $column;
        }

        $orderBy->setColumns($columns);

        return $orderBy;
    }

    /**
     * @return OrderBy
     */
    public static function getBlank(): OrderBy
    {
        return new OrderBy();
    }

    /**
     * @param string $oldColumnName
     * @param string[] $newColumnNames
     * @param array $flipSortOrders
     */
    public function replaceColumn($oldColumnName, array $newColumnNames, array $flipSortOrders = []): void
    {
        $newColumnNamesCount = count($newColumnNames);

        if ($newColumnNamesCount) {
            $columnsCount = count($this->columns);

            /** @noinspection ForeachInvariantsInspection */
            for ($i = 0; $i < $columnsCount; $i++) {
                $column = $this->columns[$i];

                if ($column->getName() === $oldColumnName) {
                    if ($newColumnNamesCount > 1) {
                        $newColumns = [];
                        foreach ($newColumnNames as $j => $newColumnName) {
                            $newColumn = new OrderingColumn(
                                $newColumnName,
                                $column->getAlias(),
                                $column->getWrappingFunction(),
                                $column->getJoinWith()
                            );
                            $newColumn->setOrdering($column->getOrdering());

                            /** @noinspection NotOptimalIfConditionsInspection */
                            if ($j < count($flipSortOrders) && $flipSortOrders[$j]) {
                                $newColumn->flipOrdering();
                            }

                            $newColumns[] = $newColumn;
                        }

                        array_splice($this->columns, $i, 1, $newColumns);

                        $i += count($newColumns) - 1;
                        $columnsCount = count($this->columns);

                    } else {
                        $column->setName($newColumnNames[0]);

                        /** @noinspection NotOptimalIfConditionsInspection */
                        if (count($flipSortOrders) && $flipSortOrders[0]) {
                            $column->flipOrdering();
                        }
                    }
                }
            }
        }
    }
}
