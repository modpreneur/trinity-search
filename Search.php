<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Trinity\Bundle\SearchBundle\NQL\DQLConverter;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;


/**
 * Class Search
 * @package Trinity\Bundle\SearchBundle
 */
final class Search
{
    private $dqlConverter;

    public function __construct(DQLConverter $dqlConverter)
    {
        $this->dqlConverter = $dqlConverter;
    }

    /**
     * @param $tableName
     * @param $queryParams
     * @return NQLQuery
     * @throws NotFoundHttpException
     */
    public function queryTable($tableName, $queryParams)
    {
        $query = "SELECT e.".$tableName." ".$queryParams;

        if (is_null($query)) {
            throw self::createNotFoundException();
        }

        return $this->query($query);
    }

    /**
     * @param $queryParams
     * @return array
     * @throws NotFoundHttpException
     */
    public function queryGlobal($queryParams) {
        $results = array();

        foreach ($this->dqlConverter->getAvailableEntities() as $entity) {
            try {
                $result = $this->queryTable($entity, $queryParams)->getQueryBuilder()->getQuery()->getResult();

                if (count($result)) {
                    $results[$entity] = $result;
                }
            } catch (\Exception $e) {
            }
        }

        return $results;
    }

    /**
     * @param $query
     * @return NQLQuery
     * @throws Exception\SyntaxErrorException
     */
    public function query($query) {
        $nqlQuery = NQLQuery::parse(trim($query));
        $nqlQuery->setDqlConverter($this->dqlConverter);

        return $nqlQuery;
    }

    public static function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }
}