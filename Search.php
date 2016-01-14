<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Trinity\Bundle\SearchBundle\NQL\DQLConverter;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\FrameworkBundle\Utils\ObjectMixin;


/**
 * Class Search
 * @package Trinity\Bundle\SearchBundle
 */
final class Search
{
    /** @var DQLConverter  */
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
    public function queryTable($tableName, $queryParams) : NQLQuery
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
    public function queryGlobal($queryParams) : array {
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
    public function query($query) : NQLQuery {
        $nqlQuery = NQLQuery::parse(trim($query));
        $nqlQuery->setDqlConverter($this->dqlConverter);

        return $nqlQuery;
    }

    /**
     * @param string $message
     * @param \Exception|null $previous
     * @return NotFoundHttpException
     */
    public static function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * @param object $entity
     * @param string $value
     * @return array|mixed|string
     */
    public static function getValue($entity, $value) {
        $values = explode(".", $value);

        return self::getObject($entity, $values, 0);
    }

    /**
     * @param object $entity
     * @param string[] $values
     * @param int $curValueIndex
     * @return array|mixed|string
     */
    private static function getObject($entity, $values, $curValueIndex) {
        try {
            $obj = ObjectMixin::get($entity, $values[$curValueIndex]);
            if ($curValueIndex == count($values) - 1) {
                return $obj;
            } else if ($obj instanceof PersistentCollection) {
                $items = [];
                foreach ($obj as $item) {
                    $items[] = array($values[$curValueIndex + 1] => self::getObject($item, $values, $curValueIndex + 1));
                }
                return $items;
            } else if (is_object($obj)) {
                return self::getObject($obj, $values, $curValueIndex + 1);
            } else {
                return $obj;
            }
        } catch (\Exception $ex) {
            return "";
        }
    }
}