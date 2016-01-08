<?php

namespace Trinity\SearchBundle;


use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Trinity\SearchBundle\NQL\DQLConverter;
use Trinity\SearchBundle\NQL\NQLQuery;

final class Search
{
    private $dqlConverter;

    public function __construct(DQLConverter $dqlConverter)
    {
        $this->dqlConverter = $dqlConverter;
    }

    public function queryTable($tableName, $queryParams)
    {
        $query = "SELECT e.".$tableName." ".$queryParams;

        if (is_null($query)) {
            throw self::createNotFoundException();
        }

        return $this->query($query);
    }

    public function queryGlobal($queryParams) {
        $results = array();

        foreach ($this->dqlConverter->getAvailableEntities() as $entity) {
            try {
                $result = $this->queryTable($entity, $queryParams);

                if (count($result)) {
                    $results[$entity] = $result;
                }
            } catch (\Exception $e) {
            }
        }

        return $results;
    }

    public function query($nqlQuery) {
        $nqlQuery = NQLQuery::parse(trim($nqlQuery));

        return $this->dqlConverter->convert($nqlQuery)->getQuery()->getResult();
    }

    public static function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }
}