<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

use Doctrine\ORM\QueryBuilder;
use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;

/**
 * Class NQLQuery
 * @package Trinity\SearchBundle\NQL
 */
class NQLQuery
{
    private static $regSearchQuery = '/^SELECT\s(?<from>[^({\s]+)(\s(\((?<select>.*)\))?\s*({(?<where>.*)})?)?(\s((?i)LIMIT)\s*=\s*(?<limit>\d+)(\s((?i)OFFSET)\s*=\s*(?<offset>\d+))?)?(\s((?i)ORDERBY)\s(?<orderby>.+))?$/';

    private $select;
    private $from;
    private $where;
    private $limit;
    private $offset;
    private $orderBy;

    /**
     * @var DQLConverter
     */
    private $dqlConverter;


    /**
     * NQLQuery constructor.
     */
    private function __construct()
    {
    }


    /**
     * @param string $str
     * @return NQLQuery
     * @throws SyntaxErrorException
     */
    public static function parse(string $str) : NQLQuery
    {
        /** @var NQLQuery $query */
        $query = new NQLQuery();

        $match = [];

        $wasFound = preg_match(self::$regSearchQuery, $str, $match);

        if ($wasFound) {
            if (array_key_exists('select', $match) && !empty($match['select'])) {
                $query->select = Select::parse($match['select']);
            } else {
                $query->select = Select::getBlank();
            }

            if (array_key_exists('where', $match)) {
                $query->where = Where::parse($match['where']);
            } else {
                $query->where = Where::getBlank();
            }

            if (array_key_exists('limit', $match) && !empty($match['limit'])) {
                $query->limit = $match['limit'];
            }

            if (array_key_exists('offset', $match) && !empty($match['offset'])) {
                $query->offset = $match['offset'];
            }

            $query->from = From::parse($match['from']);

            if (array_key_exists('orderby', $match) && !empty($match['orderby'])) {
                $query->orderBy = OrderBy::parse($match['orderby']);
            } else {
                $query->orderBy = OrderBy::getBlank();
            }
        } else {
            throw new SyntaxErrorException('Incorrect query');
        }

        return $query;
    }


    /**
     * @return Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }


    /**
     * @return From
     */
    public function getFrom(): ?From
    {
        return $this->from;
    }


    /**
     * @return Where
     */
    public function getWhere(): ?Where
    {
        return $this->where;
    }


    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }


    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }


    /**
     * @return OrderBy
     */
    public function getOrderBy(): ?OrderBy
    {
        return $this->orderBy;
    }


    /**
     * @param bool $skipSelection
     * @return \Doctrine\ORM\QueryBuilder|null
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     */
    public function getQueryBuilder(bool $skipSelection = false): ?QueryBuilder
    {
        if (null !== $this->dqlConverter) {
            return $this->dqlConverter->convert($this, $skipSelection);
        }
        return null;
    }

    /**
     * @param DQLConverter $converter
     */
    public function setDqlConverter(DQLConverter $converter): void
    {
        $this->dqlConverter = $converter;
    }
}
