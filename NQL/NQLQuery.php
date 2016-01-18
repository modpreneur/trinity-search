<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

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


    private function __construct()
    {
    }


    /**
     * @param string $str
     * @return NQLQuery
     * @throws SyntaxErrorException
     */
    public static function parse($str) : NQLQuery
    {
        $query = new NQLQuery();

        $match = array();

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
            throw new SyntaxErrorException("Incorrect query");
        }

        return $query;
    }


    /**
     * @return Select
     */
    public function getSelect()
    {
        return $this->select;
    }


    /**
     * @return From
     */
    public function getFrom()
    {
        return $this->from;
    }


    /**
     * @return Where
     */
    public function getWhere()
    {
        return $this->where;
    }


    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }


    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->offset;
    }


    /**
     * @return OrderBy
     */
    public function getOrderBy() {
        return $this->orderBy;
    }


    /**
     * @param bool $skipSelection
     * @return \Doctrine\ORM\QueryBuilder|null
     */
    public function getQueryBuilder($skipSelection = false) {
        if(!is_null($this->dqlConverter)) {
            return $this->dqlConverter->convert($this, $skipSelection);
        } else {
            return null;
        }
    }

    function setDqlConverter(DQLConverter $converter) {
        $this->dqlConverter = $converter;
    }
}