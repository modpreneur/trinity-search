<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\Choice;
use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;
use Trinity\Bundle\SearchBundle\NQL\Column;
use Trinity\Bundle\SearchBundle\NQL\DQLConverter;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\Bundle\SearchBundle\NQL\Select;
use Trinity\Bundle\SearchBundle\Serialization\ObjectNormalizer;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;
use Trinity\Component\Utils\Exception\MemberAccessException;
use Trinity\Component\Utils\Utils\ObjectMixin;

/**
 * Class Search
 * @package Trinity\Bundle\SearchBundle
 */
final class Search
{
    /** @var DQLConverter */
    private $dqlConverter;

    /** @var EntityManager */
    private $em;

    /** @var string */
    private $namespace;

    /** @var DetailUrlProvider */
    private $detailUrlProvider;

    /**
     * Search constructor.p
     * @param EntityManager $em
     * @param DQLConverter $dqlConverter
     * @param string $namespace
     * @param ContainerInterface $container
     * @param string $detailUrlProviderServiceName
     * @internal param $detailUrlProvider
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function __construct(
        EntityManager $em,
        DQLConverter $dqlConverter,
        $namespace,
        ContainerInterface $container,
        $detailUrlProviderServiceName
    )
    {
        $this->dqlConverter = $dqlConverter;
        $this->em = $em;
        $this->namespace = $namespace;
        $this->detailUrlProvider = $container->get($detailUrlProviderServiceName);
    }

    /**
     * @param string $tableName
     * @param string $queryParams
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
     * @param string $str
     * @param bool $addDetailUrls
     * @return array
     */
    public function queryGlobal($str, $addDetailUrls = true) : array
    {
        $results = [];

        foreach ($this->dqlConverter->getAvailableEntities() as $entityName => $entityClass) {
            try {
                $nqlQuery = $this->queryEntity($entityName, null, $entityClass, $str, 10);

                if ($nqlQuery !== null) {
                    $queryResult = $nqlQuery->getQueryBuilder(true)->getQuery()->getResult();

                    if (count($queryResult)) {
                        $results[$entityName] = $queryResult;

                        if ($addDetailUrls) {
                            foreach ($results[$entityName] as &$result) {
                                $result->{'_detail'} = $this->detailUrlProvider->getUrl($result);
                            }
                        }
                    }
                }

            } catch (ORMException $e) {

            } catch (NotFoundHttpException $e) {

            } catch (SyntaxErrorException $e) {

            } catch (InvalidFieldNameException $e) {

            }
        }

        return $results;
    }

    /**
     * @param $entityName
     * @param $queryColumns
     * @param $entityClass
     * @param $str
     * @param null|string $limit
     * @param null|string $offset
     * @param null|string $orderBy
     * @return NQLQuery | null
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\ORM\ORMException
     */
    public function queryEntity($entityName, $queryColumns, $entityClass, $str, $limit = null, $offset = null, $orderBy = null)
    {
        if ($entityClass === null) {
            $entityClass = $this->dqlConverter->getAvailableEntities()[$entityName];
        }

        $columns = $this->getEntityStringColumns($entityClass);

        $query = '';

        if ($queryColumns !== null) {
            $query .= '(' . $queryColumns . ')';
        }

        if (count($columns)) {
            $query .= '{';

            $count = count($columns);

            foreach ($columns as $i => $column) {
                $query .= $column . ' LIKE "%' . $str . '%"';
                if ($i + 1 < $count) {
                    $query .= ' OR ';
                }
            }

            $query .= '}';
        }

        if ($queryColumns === null && !count($columns)) {
            return null;
        }

        if ($limit !== null) {
            $query .= ' LIMIT=' . $limit;

            if ($offset !== null) {
                $query .= ' OFFSET=' . $offset;
            }
        }

        if ($orderBy !== null) {
            $query .= ' ORDERBY ' . $orderBy;
        }

        return $this->queryTable($entityName, $query);
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

    /** @noinspection GenericObjectTypeUsageInspection */
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

    /** @noinspection GenericObjectTypeUsageInspection */
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
        } catch (MemberAccessException $ex) {
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

        $context = new SerializationContext();
        $context->setSerializeNull(true);
        return SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
        )->build()->serialize($result, 'json', $context);
    }


    /**
     * @param array $entities
     * @return mixed|string
     */
    public function convertArrayToJson(array $entities)
    {
        $encoders = [new JsonEncoder()];
        $objNormalizer = new ObjectNormalizer();
        $objNormalizer->setCircularReferenceLimit(0);
        $objNormalizer->setCircularReferenceHandler(function () {
            return '';
        });
        $normalizers = [$objNormalizer];

        return (new Serializer($normalizers, $encoders))->serialize($entities, 'json');
    }


    /** @noinspection GenericObjectTypeUsageInspection */
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
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $attributes[$key] = array_replace_recursive($attributes[$key], $value);
                }
            } else {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }


    /**
     * @param $entityName
     * @return array Entity class name, null if not found
     * @throws \Doctrine\ORM\ORMException
     * @internal param string $table Table name
     * @internal param EntityManager $em Entity manager
     */
    protected function getEntityStringColumns($entityName)
    {
        // Go through all the classes
        $classNames = $this->dqlConverter->getAvailableEntities();

        $annotationReader = new AnnotationReader();
        $searchingEntityName = strtolower($entityName);

        foreach ($classNames as $i => $className) {
            $className = $classNames[$i];
            $classMetaData = $this->em->getClassMetadata($className);

            $currentEntityName = strtolower($classMetaData->getReflectionClass()->getName());

            if ($currentEntityName === $searchingEntityName) {
                /** @var array $allColumnNames */
                $allColumnNames = $classMetaData->getFieldNames();
                $columnNames = [];

                foreach ($allColumnNames as $columnName) {
                    try {
                        $annotations = $annotationReader->getPropertyAnnotations(
                            new \ReflectionProperty($classMetaData->getName(), $columnName)
                        );

                        if ($classMetaData->getTypeOfField($columnName) === 'string') {
                            $isEnum = false;

                            foreach ($annotations as $annotation) {
                                if ($annotation instanceof Choice) {
                                    $isEnum = true;
                                    break;
                                }
                            }
                            if (!$isEnum) {
                                $columnNames[] = $columnName;
                            }
                        }
                    } catch (\ReflectionException $e) {

                    }
                }

                return $columnNames;
            }
        }
        return [];
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return \ReflectionProperty[]
     */
    private function getClassProperties($reflectionClass)
    {
        if ($reflectionClass === null || !$reflectionClass) {
            return [];
        }

        $thisClassProperties = $reflectionClass->getProperties();
        $parentClassProperties = $this->getClassProperties($reflectionClass->getParentClass());

        return array_merge($thisClassProperties, $parentClassProperties);
    }
}
