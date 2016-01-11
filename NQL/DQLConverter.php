<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;


/**
 * Class DQLConverter
 * @package Trinity\SearchBundle\NQL
 */
class DQLConverter
{

    private static $ignoredEntities = [
        'AccessLog',
        'ExceptionLog',
        'Client',
    ];


    private $em;
    private $entities;
    private $bundleName;


    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->bundleName = $container->getParameter('trinity.search.bundle');
        $this->fetchAvailableEntities();
    }


    /**
     * @param NQLQuery $nqlQuery
     * @return QueryBuilder
     * @throws SyntaxErrorException
     */
    public function convert(NQLQuery $nqlQuery, $skipSelection)
    {

        $this->checkTables($nqlQuery->getFrom());

        $query = $this->em->createQueryBuilder();

        $columnDefaultAlias = count($nqlQuery->getFrom()->getTables()) === 1 ? $nqlQuery->getFrom()->getTables(
        )[0]->getAlias() : "";

        if(!$skipSelection) {
            if ($nqlQuery->getSelect()->getColumns()) {
                foreach ($nqlQuery->getSelect()->getColumns() as $column) {
                    $query->addSelect((($column->getAlias()) ?? $columnDefaultAlias) . '.' . $column->getName());
                }
            } else {
                $query->select($columnDefaultAlias);
            }
        }

        foreach ($nqlQuery->getFrom()->getTables() as $table) {
            $query->from($this->bundleName.':'.$table->getName(), $table->getAlias());
        }

        if ($nqlQuery->getWhere()->getConditions()) {
            $paramWhere = $this->getParametrizedWhere($nqlQuery->getWhere()->getConditions(), $columnDefaultAlias);
            $query->where($paramWhere['clause'])->setParameters($paramWhere['params']);
        }

        $columns = $this->getAllColumnsFromWhere($nqlQuery->getWhere()->getConditions());

        foreach ($columns as $column) {
            if (!is_null($column->getJoinWith())) {
                $query->innerJoin($column->getAlias().'.'.$column->getJoinWith(), $column->getJoinWith());
            }
        }

        if (!is_null($nqlQuery->getLimit())) {
            $query->setMaxResults($nqlQuery->getLimit());
        }

        if (!is_null($nqlQuery->getOffset())) {
            $query->setFirstResult($nqlQuery->getOffset());
        }

        return $query;
    }


    /**
     * @param From $from
     * @throws SyntaxErrorException
     */
    private function checkTables(From $from)
    {
        $tables = $from->getTables();

        foreach ($tables as $table) {
            if (!in_array(strtolower($table->getName()), $this->entities)) {
                throw new SyntaxErrorException("Unknown table \"".$table->getName()."\"");
            }
        }
    }


    private function fetchAvailableEntities()
    {
        $this->entities = array();
        $meta = $this->em->getMetadataFactory()->getAllMetadata();

        /* @var $m ClassMetadata */
        foreach ($meta as $m) {
            $entityName = substr(strrchr($m->getName(), "\\"), 1);

            if (StringUtils::startsWith(str_replace("\\", "", $m->getName()), $this->bundleName) && !in_array(
                    $entityName,
                    self::$ignoredEntities
                )
            ) {
                $this->entities[] = strtolower($entityName);
            }
        }
    }


    public function getAvailableEntities()
    {
        return $this->entities;
    }


    /**
     * @param WherePart[] $conditions
     * @param string $columnDefaultAlias
     * @param int $paramCounter
     * @return array
     */

    private function getParametrizedWhere($conditions, $columnDefaultAlias = "", &$paramCounter = 0)
    {
        $whereClause = "";
        $whereParams = array();

        foreach ($conditions as $cond) {
            switch ($cond->type) {
                case WherePartType::OPERATOR:
                    $whereClause .= " ".$cond->value;
                    break;
                case WherePartType::CONDITION:
                    $whereClause .= " ".(is_null($cond->key->getJoinWith()) ? ($cond->key->getAlias(
                            ) ?? $columnDefaultAlias) : $cond->key->getJoinWith()).".".$cond->key->getName(
                        ).$cond->operator."?".$paramCounter;
                    $whereParams[] = $cond->value;
                    $paramCounter++;
                    break;
                case WherePartType::SUBCONDITION:
                    $parametrizedSubWhere = $this->getParametrizedWhere(
                        $cond->subTree,
                        $columnDefaultAlias,
                        $paramCounter
                    );
                    $subWhereClause = $parametrizedSubWhere['clause'];
                    $subWhereParams = $parametrizedSubWhere['params'];

                    $whereClause .= " (".$subWhereClause.")";
                    $whereParams = array_merge($whereParams, $subWhereParams);
                    break;
            }
        }

        return array("clause" => $whereClause, "params" => $whereParams);
    }


    /**
     * @param Select $select
     * @param Where $where
     * @return Column[]
     */
    private function getAllColumns(Select $select, Where $where)
    {
        $columns = array();
        foreach ($select->getColumns() as $column) {
            $columns[] = $column;
        }
        $whereColumns = $this->getAllColumnsFromWhere($where->getConditions());
        foreach ($whereColumns as $column) {
            $columns[] = $column;
        }

        return $columns;
    }


    /**
     * @param $conditions
     * @return Column[]
     */
    private function getAllColumnsFromWhere($conditions)
    {
        $columns = array();

        /** @var WherePart $cond */
        foreach ($conditions as $cond) {
            if ($cond->type === WherePartType::CONDITION) {
                $columns[] = $cond->key;
            } else {
                if ($cond->type == WherePartType::SUBCONDITION) {
                    $subColumns = $this->getAllColumnsFromWhere($conditions);

                    foreach ($subColumns as $subColumn) {
                        $columns[] = $subColumn;
                    }
                }
            }
        }

        return $columns;
    }
}