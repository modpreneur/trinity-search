<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
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
    ];

    /** @var EntityManager  */
    private $em;

    /** @var string[] */
    private $entities;

    /** @var string */
    private $doctrinePrefix;

    /** @var string */
    private $namespace;


    /**
     * DQLConverter constructor.
     * @param EntityManager $entityManager
     * @param $doctrinePrefix
     * @param $namespace
     */
    public function __construct(EntityManager $entityManager, $doctrinePrefix, $namespace)
    {
        $this->em = $entityManager;
        $this->doctrinePrefix = $doctrinePrefix;
        $this->namespace = $namespace;
        $this->fetchAvailableEntities();
    }


    /**
     * @param NQLQuery $nqlQuery
     * @param bool $skipSelection
     * @return QueryBuilder
     * @throws SyntaxErrorException
     */
    public function convert(NQLQuery $nqlQuery, bool $skipSelection) : QueryBuilder
    {
        $this->checkTables($nqlQuery->getFrom());

        /** @var QueryBuilder $query */
        $query = $this->em->createQueryBuilder();

        /** @var string $columnDefaultAlias */
        $columnDefaultAlias = count($nqlQuery->getFrom()->getTables()) === 1 ? $nqlQuery->getFrom()->getTables(
        )[0]->getAlias() : '';

        if ($columnDefaultAlias === 'group') {
            $columnDefaultAlias = '_group';
        }

        if ($skipSelection) {
            $query->select($columnDefaultAlias);
        } else {
            if ($nqlQuery->getSelect()->getColumns()) {
                /** @var Column $column */
                foreach ($nqlQuery->getSelect()->getColumns() as $column) {
                    $query->addSelect(($column->getAlias() ?? $columnDefaultAlias) . '.' . $column->getName());
                }
            } else {
                $query->select($columnDefaultAlias);
            }
        }

        foreach ($nqlQuery->getFrom()->getTables() as $table) {
            $query->from($this->entities[strtolower($table->getName())], $table->getAlias());
        }

        if ($nqlQuery->getWhere()->getConditions()) {
            $paramWhere = $this->getParametrizedWhere($nqlQuery->getWhere()->getConditions(), $columnDefaultAlias);
            $query->where($paramWhere['clause'])->setParameters($paramWhere['params']);
        }

        $columns = $this->getAllColumns(
            $skipSelection ? Select::getBlank() : $nqlQuery->getSelect(),
            $nqlQuery->getWhere(),
            $nqlQuery->getOrderBy()
        );

        $alreadyJoined = [];

        foreach ($columns as $column) {
            if (count($column->getJoinWith())) {
                $joinWith = $column->getJoinWith();
                $iMax = count($joinWith);
                for ($i=0; $i<$iMax; $i++) {
                    if (!array_key_exists($joinWith[$i], $alreadyJoined)) {
                        if ($i === 0) {
                            $column = ($column->getAlias() === null ? $columnDefaultAlias : $column->getAlias()) . '.' . $column->getJoinWith()[$i];
                        } else {
                            $column = $joinWith[$i - 1] . '.' . $joinWith[$i];
                        }
                        $query->innerJoin($column, $joinWith[$i]);

                        $alreadyJoined[$joinWith[$i]] = null;
                    }
                }
            }
        }

        /** @var OrderingColumn $column */
        foreach ($nqlQuery->getOrderBy()->getColumns() as $column) {
            $query->addOrderBy(
                (
                count($column->getJoinWith()) ?
                    $column->getJoinWith()[count($column->getJoinWith()) -1] :
                    ($column->getAlias() === null ? $columnDefaultAlias : $column->getAlias())
                )
                . '.' . $column->getName(),
                $column->getOrdering()
            );
        }

        if (null !== $nqlQuery->getLimit()) {
            $query->setMaxResults($nqlQuery->getLimit());
        }

        if (null !== $nqlQuery->getOffset()) {
            $query->setFirstResult($nqlQuery->getOffset());
        }
        
        return $query;
    }


    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    public function convertToCount(QueryBuilder $query)
    {
        $selects = $query->getDQLPart('select');

        if (count($selects) === 1) {
            /** @var Query\Expr\Select $select */
            $select = $selects[0];

            $selectParts = $select->getParts();
            if (count($selectParts) === 1) {
                $selectPart = $selectParts[0];
                $query->resetDQLPart('select');
                $query->select('COUNT(' . $selectPart . ')');
                $query->resetDQLPart('orderBy');
                $query->setMaxResults(null);
                $query->setFirstResult(null);
            }
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
            if (!array_key_exists(strtolower($table->getName()), $this->entities)) {
                throw new SyntaxErrorException("Unknown table \"{$table->getName()}\"");
            }
        }
    }


    private function fetchAvailableEntities()
    {
        $this->entities = [];
        $meta = $this->em->getMetadataFactory()->getAllMetadata();

        /* @var $m ClassMetadata */
        foreach ($meta as $m) {
            $entityName = strtolower(substr(strrchr($m->getName(), "\\"), 1));

            if (!array_key_exists($entityName, $this->entities) ||
                (!in_array($entityName, self::$ignoredEntities, true) &&
                    StringUtils::startsWith($m->getName(), $this->namespace))
            ) {
                $this->entities[$entityName] = $m->getName();
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
    private function getParametrizedWhere($conditions, $columnDefaultAlias = '', &$paramCounter = 0) : array
    {
        $whereClause = '';
        $whereParams = [];

        foreach ($conditions as $cond) {
            switch ($cond->type) {
                case WherePartType::OPERATOR:
                    $whereClause .= ' '.$cond->value;
                    break;
                case WherePartType::CONDITION:
                    $whereClause .=
                        ' '.(!count($cond->key->getJoinWith()) ?
                            ($cond->key->getAlias() ?? $columnDefaultAlias) :
                            $cond->key->getJoinWith()[count($cond->key->getJoinWith())-1]).
                        '.'.$cond->key->getName().' '.
                        ($cond->operator === '!=' ?'<>' : $cond->operator).
                        ' ?'.$paramCounter
                    ;
                    if($cond->operator === Operator::LIKE && !StringUtils::startsWith($cond->value, '%') && !StringUtils::endsWith($cond->value, '%')) {
                        $whereParams[] = '%' . $cond->value . '%';
                    } else {
                        $whereParams[] = $cond->value;
                    }
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

                    $whereClause .= ' ('.$subWhereClause.')';
                    $whereParams = array_merge($whereParams, $subWhereParams);
                    break;
            }
        }

        return ['clause' => $whereClause, 'params' => $whereParams];
    }


    /**
     * @param Select $select
     * @param Where $where
     * @param OrderBy $orderBy
     * @return Column[]
     */
    private function getAllColumns(Select $select, Where $where, OrderBy $orderBy) : array
    {
        $columns = [];

        foreach ($select->getColumns() as $column) {
            $columns[$column->getFullName()] = $column;
        }
        $whereColumns = $this->getAllColumnsFromWhere($where->getConditions());
        foreach ($whereColumns as $column) {
            $columns[$column->getFullName()] = $column;
        }
        foreach ($orderBy->getColumns() as $column) {
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
        $columns = [];

        /** @var WherePart $cond */
        foreach ($conditions as $cond) {
            if ($cond->type === WherePartType::CONDITION) {
                $columns[] = $cond->key;
            } else {
                if ($cond->type === WherePartType::SUBCONDITION) {
                    $subColumns = $this->getAllColumnsFromWhere($cond->subTree);
                    foreach ($subColumns as $subColumn) {
                        $columns[] = $subColumn;
                    }
                }
            }
        }

        return $columns;
    }
}
