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

    /** @var EntityManager  */
    private $em;

    /** @var string[] */
    private $entities;

    /** @var string */
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
    public function convert(NQLQuery $nqlQuery, $skipSelection) : QueryBuilder
    {
        $this->checkTables($nqlQuery->getFrom());

        $query = $this->em->createQueryBuilder();

        $columnDefaultAlias = count($nqlQuery->getFrom()->getTables()) === 1 ? $nqlQuery->getFrom()->getTables(
        )[0]->getAlias() : "";

        if($skipSelection) {
            $query->select($columnDefaultAlias);
        }
        else {
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

        $columns = $this->getAllColumns($skipSelection ? Select::getBlank() : $nqlQuery->getSelect(), $nqlQuery->getWhere(), $nqlQuery->getOrderBy());

        $alreadyJoined = [];

        foreach ($columns as $column) {
            if (count($column->getJoinWith())) {
                $joinWith = $column->getJoinWith();
                for($i=0;$i<count($joinWith);$i++) {
                    if(!array_key_exists($joinWith[$i], $alreadyJoined)) {
                        if ($i == 0) {
                            $column = (is_null($column->getAlias()) ? $columnDefaultAlias : $column->getAlias()) . '.' . $column->getJoinWith()[$i];
                        } else {
                            $column = $joinWith[$i - 1] . "." . $joinWith[$i];
                        }
                        $query->innerJoin($column, $joinWith[$i]);

                        $alreadyJoined[$joinWith[$i]] = null;
                    }
                }
            }
        }


        foreach($nqlQuery->getOrderBy()->getColumns() as $column) {
            $query->addOrderBy((count($column->getJoinWith()) ? $column->getJoinWith()[count($column->getJoinWith()) -1] : (is_null($column->getAlias()) ? $columnDefaultAlias : $column->getAlias())) . "." . $column->getName(), $column->getOrdering());
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


    /**
     * @return string[]
     */
    public function getAvailableEntities() : array
    {
        return $this->entities;
    }


    /**
     * @param WherePart[] $conditions
     * @param string $columnDefaultAlias
     * @param int $paramCounter
     * @return array
     */

    private function getParametrizedWhere($conditions, $columnDefaultAlias = "", &$paramCounter = 0) : array
    {
        $whereClause = "";
        $whereParams = array();

        foreach ($conditions as $cond) {
            switch ($cond->type) {
                case WherePartType::OPERATOR:
                    $whereClause .= " ".$cond->value;
                    break;
                case WherePartType::CONDITION:
                    $whereClause .= " ".(!count($cond->key->getJoinWith()) ? ($cond->key->getAlias(
                            ) ?? $columnDefaultAlias) : $cond->key->getJoinWith()[count($cond->key->getJoinWith())-1]).".".$cond->key->getName(
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
     * @param OrderBy $orderBy
     * @return Column[]
     */
    private function getAllColumns(Select $select, Where $where, OrderBy $orderBy) : array
    {
        $columns = array();

        foreach ($select->getColumns() as $column) {
            $columns[$column->getFullName()] = $column;
        }
        $whereColumns = $this->getAllColumnsFromWhere($where->getConditions());
        foreach ($whereColumns as $column) {
            $columns[$column->getFullName()] = $column;
        }
        foreach($orderBy->getColumns() as $column) {
            $columns[$column->getFullName()] = $column;
        }

        return $columns;
    }


    /**
     * @param $conditions
     * @return Column[]
     */
    private function getAllColumnsFromWhere($conditions) : array
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