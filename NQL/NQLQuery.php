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
    private static $regSearchQuery = '/^SELECT\s(?<from>[^({]+)(\s(\((?<select>.+)\))?\s*({(?<where>.+)})?)?(\sLIMIT\s(?<limit>\d+)(\sOFFSET\s(?<offset>\d+))?)?$/';

    private $select;
    private $from;
    private $where;
    private $limit;
    private $offset;


    private function __construct()
    {
    }


    public static function parse($str)
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
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }


    /**
     * @return mixed
     */
    public function getOffset()
    {
        return $this->offset;
    }
}