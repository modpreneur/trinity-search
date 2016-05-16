<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle;

use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Trinity\Bundle\SearchBundle\NQL\Column;
use Trinity\Bundle\SearchBundle\NQL\DQLConverter;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\Bundle\SearchBundle\NQL\Select;
use Trinity\FrameworkBundle\Utils\ObjectMixin;

/**
 * Class Search
 * @package Trinity\Bundle\SearchBundle
 */
final class Search
{
    /** @var DQLConverter  */
    private $dqlConverter;

    /**
     * Search constructor.
     * @param DQLConverter $dqlConverter
     */
    public function __construct(DQLConverter $dqlConverter)
    {
        $this->dqlConverter = $dqlConverter;
    }

    /**
     * @param string $tableName
     * @param $queryParams
     * @return NQLQuery
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     * @throws NotFoundHttpException
     */
    public function queryTable($tableName, $queryParams) : NQLQuery
    {
        $query = "SELECT e.{$tableName} {$queryParams}";

        if (null === $query) {
            throw self::createNotFoundException();
        }

        return $this->query($query);
    }


    /**
     * @param $queryParams
     * @return array
     * @throws NotFoundHttpException
     */
    public function queryGlobal($queryParams) : array
    {
        $results = [];

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
    public function query($query) : NQLQuery
    {
        $nqlQuery = NQLQuery::parse(trim($query));
        $nqlQuery->setDqlConverter($this->dqlConverter);
        return $nqlQuery;
    }

    /**
     * @param string $message
     * @param \Exception|null $previous
     * @return NotFoundHttpException
     */
    private static function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * @param object $entity
     * @param string $value
     * @return array|mixed|string
     */
    public static function getValue($entity, $value)
    {
        $values = explode('.', $value);

        return self::getObject($entity, $values, 0);
    }

    /**
     * @param object $entity
     * @param string[] $values
     * @param int $curValueIndex
     * @return array|mixed|string
     */
    private static function getObject($entity, $values, $curValueIndex)
    {
        try {
            $obj = ObjectMixin::get($entity, $values[$curValueIndex]);
        } catch (\Exception $ex) {
            $obj = '';
        }

        if ($curValueIndex === count($values) - 1) {
            return $curValueIndex ? [$values[$curValueIndex] => $obj] : $obj;
        } elseif ($obj instanceof PersistentCollection) {
            $items = [];
            foreach ($obj as $item) {
                if ($curValueIndex === 0) {
                    $items[] = self::getObject($item, $values, $curValueIndex + 1);
                } else {
                    $items[$values[$curValueIndex]][] = self::getObject($item, $values, $curValueIndex + 1);
                }
            }
            return $items;
        } elseif (is_object($obj)) {
            if ($curValueIndex === 0) {
                return self::getObject($obj, $values, $curValueIndex + 1);
            } else {
                return [$values[$curValueIndex] => self::getObject($obj, $values, $curValueIndex + 1)];
            }
        } else {
            if ($curValueIndex === 0) {
                return self::getObject($obj, $values, $curValueIndex + 1);
            } else {
                return [$values[$curValueIndex] => self::getObject($obj, $values, $curValueIndex + 1)];
            }
        }

    }


    /**
     * @param NQLQuery $nqlQuery
     * @param bool $skipSelection
     * @return mixed|string
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     */
    public function convertToJson(NQLQuery $nqlQuery, bool $skipSelection)
    {
        $entities = $nqlQuery->getQueryBuilder($skipSelection)->getQuery()->getResult();

        if (!$skipSelection) {
            return SerializerBuilder::create()->setPropertyNamingStrategy(
                new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
            )->build()->serialize($entities, 'json');
        }

        $result = [];

        /** @var Select $select */
        $select = $nqlQuery->getSelect();

        foreach ($entities as $entity) {
            $result[] = $this->select($select->getColumns(), $entity);
        }

        return SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
        )->build()->serialize($result, 'json');
    }


    /**
     * @param array $entities
     * @return mixed|string
     */
    public function convertArrayToJson(array $entities)
    {
        return SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
        )->build()->serialize($entities, 'json');
    }

    /**
     * @param  Column[] $columns
     * @param  object $entity
     * @return array
     */
    private function select($columns, $entity) : array
    {
        $attributes = [];
        foreach ($columns as $column) {
            $fullName = $column->getFullName();
            $value = static::getValue($entity, $fullName);

            $key = count($column->getJoinWith()) ? $column->getJoinWith()[0] : $column->getName();

            if (array_key_exists($key, $attributes)) {
                if (is_array($value) && is_array($attributes[$key])) {
                    $attributes[$key] = array_replace_recursive($attributes[$key], $value);
                }
            } else {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }
}
